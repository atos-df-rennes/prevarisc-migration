<?php

namespace App\Service;

use App\Dto\IgnApi;
use App\Dto\IgnCapabilities;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CouchesCarto
{
    public const WMTS = 'wmts';

    public const WMS_RASTER = 'wms raster';

    public const WMS_VECTEUR = 'wms vecteur';

    public const FORMATS = [self::WMTS, self::WMS_RASTER, self::WMS_VECTEUR];

    public function __construct(private readonly HttpClientInterface $client, private readonly LoggerInterface $logger, private readonly CacheItemPoolInterface $cache)
    {
    }

    // @todo: A refacto
    private function getCapabilities(string $format): IgnCapabilities
    {
        $baseFormat = $this->getBaseFormat($format);
        $url = 'https://data.geopf.fr/{formatUrl}?SERVICE='.$baseFormat.'&REQUEST=GetCapabilities{options}';
        $capabilitiesUrl = null;
        $capabilitiesError = null;

        switch ($format) {
            case self::WMTS:
                $url = str_replace('{formatUrl}', 'wmts', $url);
                $capabilitiesUrl = str_replace('{options}', '&VERSION=1.0.0', $url);
                break;
            case self::WMS_RASTER:
                $url = str_replace('{formatUrl}', 'wms-r/wms', $url);
                $capabilitiesUrl = str_replace('{options}', '&VERSION=1.3.0', $url);
                break;
            case self::WMS_VECTEUR:
                $url = str_replace('{formatUrl}', 'wms-v/ows', $url);
                $capabilitiesUrl = str_replace('{options}', '&VERSION=1.3.0', $url);
                break;
            default:
                $capabilitiesError = 'Format non supporté : '.$format.'\nLes formats supportés sont : '.implode(' / ', self::FORMATS);
                break;
        }

        return new IgnCapabilities($capabilitiesUrl, $capabilitiesError);
    }

    /**
     * @return IgnApi[]
     */
    private function getCapabilitiesLayers(IgnCapabilities $capabilities, string $format): array
    {
        $baseFormat = $this->getBaseFormat($format);

        $crawler = $this->getCrawler($capabilities, $format);

        if (null === $crawler) {
            return [];
        }

        if (self::WMTS === $format) {
            $contents = $crawler->filterXPath('//default:Contents');
            $url = $crawler->filterXPath('//ows:OperationsMetadata/ows:Operation[@name="GetCapabilities"]/ows:DCP/ows:HTTP/ows:Get')->attr('xlink:href');

            if (null === $url) {
                $this->logger->error(sprintf("L'URL du service de cartographie pour le format %s n'est pas valide.", $format));

                return [];
            }

            $contentLayers = $contents->filterXPath('//default:Layer');
        } else {
            $contents = $crawler->filterXPath('//default:Capability');
            $url = $contents->filterXPath('//default:Request/default:GetCapabilities/default:DCPType/default:HTTP/default:Get/default:OnlineResource')->attr('xlink:href');

            if (null === $url) {
                $this->logger->error(sprintf("L'URL du service de cartographie pour le format %s n'est pas valide.", $format));

                return [];
            }

            $contentLayers = $contents->filterXPath('//default:Layer[@queryable="1"]');
        }

        $ignLayers = [];
        foreach ($contentLayers as $layer) {
            $nom = '';
            $layers = '';
            $formatCouche = '';

            /** @var \DOMElement $property */
            foreach ($layer->childNodes as $property) {
                if ('Title' === $property->localName) {
                    $nom = $this->getFirstNodeTextContent($property);
                }

                if (self::WMTS === $format) {
                    if ('Identifier' === $property->localName) {
                        $layers = $this->getFirstNodeTextContent($property);
                    }

                    if ('Format' === $property->localName) {
                        $formatCouche = $this->getFirstNodeTextContent($property);
                    }
                } else {
                    if ('Name' === $property->localName) {
                        $layers = $this->getFirstNodeTextContent($property);
                    }

                    if ('Style' === $property->localName) {
                        /** @var \DOMElement $styleProperty */
                        foreach ($property->childNodes as $styleProperty) {
                            if ('LegendURL' === $styleProperty->localName) {
                                /** @var \DOMElement $legendProperty */
                                foreach ($styleProperty->childNodes as $legendProperty) {
                                    if ('Format' === $legendProperty->localName) {
                                        $formatCouche = $this->getFirstNodeTextContent($legendProperty);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ('' === $formatCouche && 'wms vecteur' === $format) {
                $formatCouche = 'image/png';
            }

            $type = strtoupper($baseFormat);

            $ignLayer = new IgnApi($nom, $url, $type, $layers, $formatCouche);
            $ignLayers[] = $ignLayer;
        }

        return $ignLayers;
    }

    private function getFirstNodeTextContent(\DOMElement $property): string
    {
        if (null === ($firstItem = $property->childNodes->item(0))) {
            return '';
        }

        return $firstItem->textContent;
    }

    private function getBaseFormat(string $format): string
    {
        $index = strpos($format, ' ');

        if (false !== $index) {
            return explode(' ', $format)[0];
        }

        return $format;
    }

    /**
     * @return array<string, array<IgnApi>|string>
     */
    public function getCouchesIGN(): array
    {
        $couchesIGN = [];

        foreach (self::FORMATS as $format) {
            $capabilities = $this->getCapabilities($format);

            // @todo: A refacto
            /*
             * On return direct et on skip donc des formats qui peuvent par la suite être valides.
             * Il faudrait plutôt mettre la clé 'error' sous la clé 'format' et traiter format par format.
             */
            if (null !== ($error = $capabilities->error())) {
                $couchesIGN['error'] = $error;

                return $couchesIGN;
            }

            try {
                $couchesIGN[$format] = $this->getCapabilitiesLayers($capabilities, $format);
            } catch (\Exception $exception) {
                $this->logger->error(sprintf('Erreur lors du parsing des capabilities IGN pour le format %s : %s', $format, $exception->getMessage()));
                $couchesIGN[$format] = [];
            }
        }

        return $couchesIGN;
    }

    private function getCrawler(IgnCapabilities $capabilities, string $format): ?Crawler
    {
        /** @var CacheItemInterface $cacheIgn */
        $cacheIgn = $this->cache->getItem(str_replace(' ', '_', $format));

        if ($cacheIgn->isHit()) {
            /** @var string $cacheContent */
            $cacheContent = $cacheIgn->get();

            return new Crawler($cacheContent);
        }

        $url = $capabilities->url();
        try {
            if (null === $url) {
                throw new \DomainException(sprintf("L'URL pour le format %s est null.", $format));
            }

            $ignApiResponse = $this->client->request('GET', $url)->getContent();

            $cacheIgn->set($ignApiResponse);
            $cacheIgn->expiresAfter(\DateInterval::createFromDateString('1 week'));
            $this->cache->save($cacheIgn);
        } catch (\Exception $exception) {
            $this->logger->error("Problème lors de la connexion a l'API IGN : ".$exception->getMessage());

            return null;
        }

        return new Crawler($ignApiResponse);
    }
}

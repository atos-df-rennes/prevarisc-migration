<?php

namespace App\Service;

use App\Dto\IgnApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CouchesCarto
{
    public const FORMATS = ['wmts', 'wms raster', 'wms vecteur'];

    /** @var HttpClientInterface */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    // @todo: A refacto
    private function getCapabilities(string $format): array
    {
        $baseFormat = $this->getBaseFormat($format);
        $url = 'https://data.geopf.fr/{formatUrl}?SERVICE='.$baseFormat.'&REQUEST=GetCapabilities{options}';
        $capabilities = [];
        $capabilities['parser'] = '';
        $capabilities['url'] = '';

        switch ($format) {
            case 'wmts':
                $url = str_replace('{formatUrl}', 'wmts', $url);
                $capabilities['url'] = str_replace('{options}', '&VERSION=1.0.0', $url);
                break;
            case 'wms raster':
                $url = str_replace('{formatUrl}', 'wms-r/wms', $url);
                $capabilities['url'] = str_replace('{options}', '&VERSION=1.3.0', $url);
                break;
            case 'wms vecteur':
                $url = str_replace('{formatUrl}', 'wms-v/ows', $url);
                $capabilities['url'] = str_replace('{options}', '&VERSION=1.3.0', $url);
                break;
            default:
                $capabilities['error'] = 'Format non supporté: '.$format.'\nLes formats supportés sont: WMTS / WMS Raster / WMS Vecteur';
                break;
        }

        return $capabilities;
    }

    private function getCapabilitiesLayers(array $capabilities, string $format): array
    {
        $baseFormat = $this->getBaseFormat($format);

        $crawler = $this->getCrawler($capabilities, $format);

        if (null === $crawler) {
            return [];
        }

        if ('wmts' === $format) {
            $contents = $crawler->filterXPath('//default:Contents');
            $url = $crawler->filterXPath('//ows:OperationsMetadata/ows:Operation[@name="GetCapabilities"]/ows:DCP/ows:HTTP/ows:Get')->attr('xlink:href');
            $contentLayers = $contents->filterXPath('//default:Layer');
        } else {
            $contents = $crawler->filterXPath('//default:Capability');
            $url = $contents->filterXPath('//default:Request/default:GetCapabilities/default:DCPType/default:HTTP/default:Get/default:OnlineResource')->attr('xlink:href');
            $contentLayers = $contents->filterXPath('//default:Layer[@queryable="1"]');
        }

        $ignLayers = [];
        foreach ($contentLayers as $layer) {
            $nom = '';
            $layers = '';
            $formatCouche = '';
            foreach ($layer->childNodes as $property) {
                if ('Title' === $property->localName) {
                    $nom = $property->childNodes->item(0)->textContent;
                }

                if ('wmts' === $format) {
                    if ('Identifier' === $property->localName) {
                        $layers = $property->childNodes->item(0)->textContent;
                    }

                    if ('Format' === $property->localName) {
                        $formatCouche = $property->childNodes->item(0)->textContent;
                    }
                } else {
                    if ('Name' === $property->localName) {
                        $layers = $property->childNodes->item(0)->textContent;
                    }

                    if ('Style' === $property->localName) {
                        foreach ($property->childNodes as $Styleproperty) {
                            if ('LegendURL' === $Styleproperty->localName) {
                                foreach ($Styleproperty->childNodes as $Legendproperty) {
                                    if ('Format' === $Legendproperty->localName) {
                                        $formatCouche = $Legendproperty->childNodes->item(0)->textContent;
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

    private function getBaseFormat(string $format): string
    {
        $index = strpos($format, ' ');
        if (false !== $index) {
            return explode(' ', $format)[0];
        }

        return $format;
    }

    public function getCouchesIGN(): array
    {
        $couchesIGN = [];

        foreach (self::FORMATS as $format) {
            $capabilities = $this->getCapabilities($format);
            if (isset($capabilities['error'])) {
                $couchesIGN['error'] = $capabilities['error'];

                return $couchesIGN;
            }

            $couchesIGN[$format] = $this->getCapabilitiesLayers($capabilities, $format);
        }

        return $couchesIGN;
    }

    private function getCrawler(array $capabilities, string $format): ?Crawler
    {
        $cache = new FilesystemAdapter('app.cache');

        $cacheIgn = $cache->getItem(str_replace(' ', '_', $format));
        if ($cacheIgn->isHit()) {
            return new Crawler($cacheIgn->get());
        }

        try {
            $ignApiResponse = $this->client->request('GET', $capabilities['url'])->getContent();

            $cacheIgn->set($ignApiResponse);
            $cacheIgn->expiresAfter(\DateInterval::createFromDateString('1 week'));
            $cache->save($cacheIgn);
        } catch (\Exception $exception) {
            $this->logger->alert("Problème lors de la connexion a l'API IGN : ".$exception->getMessage());

            return null;
        }

        return new Crawler($ignApiResponse);
    }
}

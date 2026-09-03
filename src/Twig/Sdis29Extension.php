<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Etablissement;
use App\Repository\CoucheCartoRepository;
use App\Service\Sdis29\GeobretagneUrlBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Fonctions Twig portant l'intégration du visualiseur Géobretagne (sviewer)
 * spécifique à SDIS29.
 *
 * Portage de Plugin_SDIS29::changeViewer() / changeViewerAdmin()
 * (legacy Zend, application/plugins/SDIS29.php).
 *
 * Le service est toujours enregistré (sans effet si non appelé) ; c'est
 * l'appel depuis les templates qui est conditionné par `sdis29_custom_enabled`
 * et par la présence de l'URL Géobretagne (`geobretagne_url`).
 */
class Sdis29Extension
{
    public function __construct(private readonly GeobretagneUrlBuilder $geobretagneUrlBuilder, private readonly CoucheCartoRepository $coucheCartoRepository, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    #[AsTwigFunction(name: 'sdis29_geobretagne_etablissement_url')]
    public function buildEtablissementViewerUrl(string $geobretagneBaseUrl, Etablissement $etablissement): ?string
    {
        $kmlUrl = $this->urlGenerator->generate(
            'sdis29_etablissement_kml',
            ['id' => $etablissement->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->geobretagneUrlBuilder->buildEtablissementViewerUrl(
            $geobretagneBaseUrl,
            $etablissement,
            $this->coucheCartoRepository->findAll(),
            $kmlUrl
        );
    }

    #[AsTwigFunction(name: 'sdis29_geobretagne_admin_url')]
    public function buildAdminViewerUrl(string $geobretagneBaseUrl): ?string
    {
        return $this->geobretagneUrlBuilder->buildAdminViewerUrl($geobretagneBaseUrl, $this->coucheCartoRepository->findAll());
    }
}

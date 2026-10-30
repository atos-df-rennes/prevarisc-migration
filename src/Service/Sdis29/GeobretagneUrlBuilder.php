<?php

declare(strict_types=1);

namespace App\Service\Sdis29;

use App\Entity\EtablissementInformations;
use App\Entity\CoucheCarto;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;

/**
 * Construit les URLs du visualiseur cartographique Géobretagne (sviewer),
 * embarqué dans un iframe sur la fiche établissement et sur la page
 * d'administration des couches cartographiques.
 *
 * Portage de Plugin_SDIS29::changeViewer() / changeViewerAdmin() /
 * wgs84ToWebMercator() (legacy Zend, application/plugins/SDIS29.php).
 */
class GeobretagneUrlBuilder
{
    /**
     * Coordonnées d'exemple utilisées par l'admin des couches cartographiques
     * (centre approximatif du département du Finistère), identiques au legacy.
     */
    private const float ADMIN_SAMPLE_LONGITUDE = -4.080290;

    private const float ADMIN_SAMPLE_LATITUDE = 47.976387;

    /**
     * Construit l'URL de l'iframe Géobretagne pour la fiche d'un établissement.
     *
     * Retourne null si l'établissement n'a pas d'adresse géolocalisée
     * (mêmes conditions de garde que le legacy).
     *
     * @param CoucheCarto[] $couchesCartographiques
     */
    public function buildEtablissementViewerUrl(
        string $geobretagneBaseUrl,
        Etablissement $etablissement,
        array $couchesCartographiques,
        string $kmlUrl
    ): ?string {
        $premiereAdresse = $etablissement->getAdresses()->first();

        if (!$premiereAdresse instanceof EtablissementAdresse) {
            return null;
        }

        $longitude = $premiereAdresse->getLongitude();
        $latitude = $premiereAdresse->getLatitude();

        if (null === $longitude || null === $latitude) {
            return null;
        }

        $coordinates = $this->wgs84ToWebMercator($longitude, $latitude);

        if (null === $coordinates) {
            return null;
        }

        $latestInformations = $etablissement->getLatestInformationsOrNull();
        $libelle = $latestInformations instanceof EtablissementInformations ? (string) $latestInformations->getLibelle() : '';

        return $this->buildUrl($geobretagneBaseUrl, $coordinates, $couchesCartographiques, $libelle, $kmlUrl);
    }

    /**
     * Construit l'URL de l'iframe Géobretagne pour la page d'administration
     * des couches cartographiques (coordonnées d'exemple fixes, pas de KML).
     *
     * @param CoucheCarto[] $couchesCartographiques
     */
    public function buildAdminViewerUrl(string $geobretagneBaseUrl, array $couchesCartographiques): ?string
    {
        $coordinates = $this->wgs84ToWebMercator(self::ADMIN_SAMPLE_LONGITUDE, self::ADMIN_SAMPLE_LATITUDE);

        if (null === $coordinates) {
            return null;
        }

        return $this->buildUrl($geobretagneBaseUrl, $coordinates, $couchesCartographiques, 'Exemple de carte géobretagne', null);
    }

    /**
     * @param array{longitude: float, latitude: float} $coordinates
     * @param CoucheCarto[]                            $couchesCartographiques
     */
    private function buildUrl(string $geobretagneBaseUrl, array $coordinates, array $couchesCartographiques, string $title, ?string $kmlUrl): string
    {
        $layers = $this->extractLayerNames($couchesCartographiques);

        $tokens = [
            '%t%' => (string) time(),
            '%x%' => (string) $coordinates['longitude'],
            '%y%' => (string) $coordinates['latitude'],
            '%z%' => '16',
            '%lb%' => '0',
            '%title%' => urlencode($title),
            '%layers%' => urlencode(implode(',', $layers)),
            '%kml%' => urlencode($kmlUrl ?? ''),
        ];

        $template = rtrim($geobretagneBaseUrl, '?&')
            .'?t=%t%&x=%x%&y=%y%&z=%z%&lb=%lb%&title=%title%&layers=%layers%'
            .(null !== $kmlUrl ? '&kml=%kml%' : '');

        return strtr($template, $tokens);
    }

    /**
     * @param CoucheCarto[] $couchesCartographiques
     *
     * @return string[]
     */
    private function extractLayerNames(array $couchesCartographiques): array
    {
        $layers = [];

        foreach ($couchesCartographiques as $coucheCartographique) {
            if (!$coucheCartographique instanceof CoucheCarto) {
                continue;
            }

            $url = (string) $coucheCartographique->getUrl();
            $layer = (string) $coucheCartographique->getLayer();

            if (1 === preg_match('@//geobretagne\.fr/geoserver/([^/]+)/wms$@i', $url, $matches)) {
                $layers[] = $matches[1].':'.$layer;
            } else {
                $layers[] = $layer;
            }
        }

        return $layers;
    }

    /**
     * @return array{longitude: float, latitude: float}|null
     */
    private function wgs84ToWebMercator(float $longitudeWGS84, float $latitudeWGS84): ?array
    {
        if (abs($longitudeWGS84) > 180 || abs($latitudeWGS84) > 90) {
            return null;
        }

        $num = $longitudeWGS84 * 0.017453292519943295;
        $longitude = 6378137.0 * $num;
        $a = $latitudeWGS84 * 0.017453292519943295;
        $latitude = 3189068.5 * log((1.0 + sin($a)) / (1.0 - sin($a)));

        return ['longitude' => $longitude, 'latitude' => $latitude];
    }
}

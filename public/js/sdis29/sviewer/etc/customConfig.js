customConfig = {
    title: 'GeoBretagne Prevarisc',

    /**
     * default language, see etc/i18n.js
     */
    lang: 'fr',

    /**
     * base url of the geOrchetra SDI. Layers coming from this SDI
     * will have enhanced features.
     */
    geOrchestraBaseUrl: 'http://geobretagne.fr/',

    /**
     * map bounds
     */
    initialExtent: [-531000,6012000,-180000,6259000],
    maxExtent: [-20037508.34, -20037508.34, 20037508.34, 20037508.34],
    restrictedExtent: [-20037508.34, -20037508.34, 20037508.34, 20037508.34],

    /**
     * getFeatureInfo control
     */
    maxFeatures: 10,
    nodata: '<!--nodatadetect-->\n<!--nodatadetect-->',

    /**
     * openLS control
     */
    openLSGeocodeUrl: "//geobretagne.fr/openls?",



    /**
     * background layers (EPSG:3857)
     */
    layersBackground: [
        // carte simple
        new ol.layer.Tile({
            source: new ol.source.XYZ({
                attributions: [new ol.Attribution({ html: 'carte <a href="http://geobretagne.fr/geonetwork/apps/georchestra/?uuid=3303a14b-44af-4950-b067-f43ddc0f483f">GéoBretagne</a>,'
                + 'données contributeurs <a href="http://www.openstreetmap.org/">OpenStreetMap</a> <a href="http://www.openstreetmap.org/copyright">ODbL</a>' })],
                url: 'https://tile.geobretagne.fr/osm/tms/1.0.0/osm:default/EPSG3857/{z}/{x}/{-y}.png',
            })
        }),

        // routes
        // NOTE: the legacy "satellite" background layer (NASA/PlanetObserver imagery
        // via tile.geobretagne.fr/service/wmts) has been decommissioned by GeoBretagne
        // and has no direct modern replacement (the only remaining "satellite" layer,
        // photo:satellite-ancien, is 1949-52 B&W historical aerial photography limited
        // to Brittany) — removed rather than pointing at broken/inappropriate content.
        new ol.layer.Tile({
            source: new ol.source.XYZ({
                attributions: [new ol.Attribution({ html: 'carte <a href="http://geobretagne.fr/geonetwork/apps/georchestra/?uuid=3303a14b-44af-4950-b067-f43ddc0f483f">GéoBretagne</a>,'
                + 'données contributeurs <a href="http://www.openstreetmap.org/">OpenStreetMap</a> <a href="http://www.openstreetmap.org/copyright">ODbL</a>' })],
                url: 'https://tile.geobretagne.fr/osm/tms/1.0.0/osm:roads/EPSG3857/{z}/{x}/{-y}.png',
            })
        }),

        // mapquest
        new ol.layer.Tile({
            preload: 2,
            source: new ol.source.MapQuest({layer: 'osm'})
        })
    ],

    /**
     * social media links (prefixes)
     */
    socialMedia: {
        'Twitter' : 'https://twitter.com/intent/tweet?text=',
        'Google+' : 'https://plus.google.com/share?url=',
        'Facebook': 'http://www.facebook.com/sharer/sharer.php?u='
    }
};
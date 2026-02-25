<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(PREVARISC_PATH)', '/home/prv/current/prevarisc');

    $parameters->set('app.prevarisc_path', '%env(string:PREVARISC_PATH)%');

    $parameters->set('env(PREVARISC_DEBUG_ENABLED)', 0);

    $parameters->set('app.debug', '%env(bool:PREVARISC_DEBUG_ENABLED)%');

    $parameters->set('env(PREVARISC_SECURITY_SALT)', '');

    $parameters->set('app.security_salt', '%env(string:PREVARISC_SECURITY_SALT)%');

    $parameters->set('env(PREVARISC_ENFORCE_SECURITY)', 0);

    $parameters->set('app.enforce_security', '%env(bool:PREVARISC_ENFORCE_SECURITY)%');

    $parameters->set('env(PREVARISC_SESSION_MAX_LIFETIME)', 3600);

    $parameters->set('app.session_max_lifetime', '%env(int:PREVARISC_SESSION_MAX_LIFETIME)%');

    $parameters->set('env(PREVARISC_DB_ADAPTER)', 'mysql');

    $parameters->set('app.db_adapter', '%env(string:PREVARISC_DB_ADAPTER)%');

    $parameters->set('env(PREVARISC_DB_CHARSET)', 'utf8');

    $parameters->set('app.db_charset', '%env(string:PREVARISC_DB_CHARSET)%');

    $parameters->set('env(PREVARISC_DB_DBNAME)', 'PRV_prevarisc_v2');

    $parameters->set('app.db_name', '%env(string:PREVARISC_DB_DBNAME)%');

    $parameters->set('env(PREVARISC_DB_HOST)', 'localhost');

    $parameters->set('app.db_host', '%env(string:PREVARISC_DB_HOST)%');

    $parameters->set('env(PREVARISC_DB_PORT)', 3306);

    $parameters->set('app.db_port', '%env(int:PREVARISC_DB_PORT)%');

    $parameters->set('env(PREVARISC_DB_USERNAME)', '');

    $parameters->set('app.db_username', '%env(string:PREVARISC_DB_USERNAME)%');

    $parameters->set('env(PREVARISC_DB_PASSWORD)', '');

    $parameters->set('app.db_password', '%env(string:PREVARISC_DB_PASSWORD)%');

    $parameters->set('env(PREVARISC_CACHE_LIFETIME)', 3600);

    $parameters->set('app.cache_lifetime', '%env(int:PREVARISC_CACHE_LIFETIME)%');

    $parameters->set('env(PREVARISC_CACHE_ADAPTER)', 'File');

    $parameters->set('app.cache_adapter', '%env(string:PREVARISC_CACHE_ADAPTER)%');

    $parameters->set('env(PREVARISC_CACHE_DIR)', '');

    $parameters->set('app.cache_dir', '%env(string:PREVARISC_CACHE_DIR)%');

    $parameters->set('env(PREVARISC_CACHE_HOST)', '');

    $parameters->set('app.cache_host', '%env(string:PREVARISC_CACHE_HOST)%');

    $parameters->set('env(PREVARISC_CACHE_PORT)', 0);

    $parameters->set('app.cache_port', '%env(int:PREVARISC_CACHE_PORT)%');

    $parameters->set('env(PREVARISC_LDAP_ENABLED)', 0);

    $parameters->set('app.ldap_enabled', '%env(bool:PREVARISC_LDAP_ENABLED)%');

    $parameters->set('env(PREVARISC_LDAP_HOST)', '');

    $parameters->set('app.ldap_host', '%env(string:PREVARISC_LDAP_HOST)%');

    $parameters->set('env(PREVARISC_LDAP_PORT)', 389);

    $parameters->set('app.ldap_port', '%env(int:PREVARISC_LDAP_PORT)%');

    $parameters->set('env(PREVARISC_LDAP_USERNAME)', '');

    $parameters->set('app.ldap_username', '%env(string:PREVARISC_LDAP_USERNAME)%');

    $parameters->set('env(PREVARISC_LDAP_PASSWORD)', '');

    $parameters->set('app.ldap_password', '%env(string:PREVARISC_LDAP_PASSWORD)%');

    $parameters->set('env(PREVARISC_LDAP_BASEDN)', '');

    $parameters->set('app.ldap_basedn', '%env(string:PREVARISC_LDAP_BASEDN)%');

    $parameters->set('env(PREVARISC_SECURITY_KEY)', '');

    $parameters->set('app.security_key', '%env(string:PREVARISC_SECURITY_KEY)%');

    $parameters->set('env(PREVARISC_MAIL_ENABLED)', 0);

    $parameters->set('app.mail_enabled', '%env(bool:PREVARISC_MAIL_ENABLED)%');

    $parameters->set('env(PREVARISC_MAIL_TRANSPORT)', 'smtp');

    $parameters->set('app.mail_transport', '%env(string:PREVARISC_MAIL_TRANSPORT)%');

    $parameters->set('env(PREVARISC_MAIL_HOST)', 'localhost');

    $parameters->set('app.mail_host', '%env(string:PREVARISC_MAIL_HOST)%');

    $parameters->set('env(PREVARISC_MAIL_PORT)', '');

    $parameters->set('app.mail_port', '%env(string:PREVARISC_MAIL_PORT)%');

    $parameters->set('env(PREVARISC_MAIL_USERNAME)', '');

    $parameters->set('app.mail_username', '%env(string:PREVARISC_MAIL_USERNAME)%');

    $parameters->set('env(PREVARISC_MAIL_PASSWORD)', '');

    $parameters->set('app.mail_password', '%env(string:PREVARISC_MAIL_PASSWORD)%');

    $parameters->set('env(PREVARISC_MAIL_SENDER)', 'noreply@prevarisc.local');

    $parameters->set('app.mail_sender', '%env(string:PREVARISC_MAIL_SENDER)%');

    $parameters->set('env(PREVARISC_MAIL_SENDER_NAME)', '');

    $parameters->set('app.mail_sender_name', '%env(string:PREVARISC_MAIL_SENDER_NAME)%');

    $parameters->set('env(PREVARISC_DATASTORE_ADAPTER)', 'Plugin_SimpleFileDataStore');

    $parameters->set('app.datastore_adapter', '%env(string:PREVARISC_DATASTORE_ADAPTER)%');

    $parameters->set('env(PREVARISC_DATA_PATH)', '/data');

    $parameters->set('app.data_path', '%env(string:PREVARISC_DATA_PATH)%');

    $parameters->set('env(PREVARISC_REAL_DATA_PATH)', '%app.prevarisc_path%/public/data');

    $parameters->set('app.files_path', '%env(resolve:PREVARISC_REAL_DATA_PATH)%');

    $parameters->set('env(PREVARISC_PLUGIN_IGNKEY)', '');

    $parameters->set('app.ign_key', '%env(string:PREVARISC_PLUGIN_IGNKEY)%');

    $parameters->set('env(PREVARISC_CARTO_DEFAULT_LON)', '2.71490430425517');

    $parameters->set('app.carto_default_lon', '%env(float:PREVARISC_CARTO_DEFAULT_LON)%');

    $parameters->set('env(PREVARISC_CARTO_DEFAULT_LAT)', '50.4727273438818');

    $parameters->set('app.carto_default_lat', '%env(float:PREVARISC_CARTO_DEFAULT_LAT)%');

    $parameters->set('env(PREVARISC_CARTO_AUTOCONF_PATH)', '');

    $parameters->set('app.carto_autoconf_path', '%env(string:PREVARISC_CARTO_AUTOCONF_PATH)%');

    $parameters->set('env(PREVARISC_PLUGIN_GOOGLEMAPKEY)', '');

    $parameters->set('app.google_map_key', '%env(string:PREVARISC_PLUGIN_GOOGLEMAPKEY)%');

    $parameters->set('env(PREVARISC_PLUGIN_GEOCONCEPT_URL)', '');

    $parameters->set('app.geoconcept_url', '%env(string:PREVARISC_PLUGIN_GEOCONCEPT_URL)%');

    $parameters->set('env(PREVARISC_PLUGIN_GEOCONCEPT_LAYER)', '');

    $parameters->set('app.geoconcept_layer', '%env(string:PREVARISC_PLUGIN_GEOCONCEPT_LAYER)%');

    $parameters->set('env(PREVARISC_PLUGIN_GEOCONCEPT_APP_ID)', '');

    $parameters->set('app.geoconcept_app_id', '%env(string:PREVARISC_PLUGIN_GEOCONCEPT_APP_ID)%');

    $parameters->set('env(PREVARISC_PLUGIN_GEOCONCEPT_PROJECTION)', '');

    $parameters->set('app.geoconcept_projection', '%env(string:PREVARISC_PLUGIN_GEOCONCEPT_PROJECTION)%');

    $parameters->set('env(PREVARISC_PLUGIN_GEOCONCEPT_TOKEN)', '');

    $parameters->set('app.geoconcept_token', '%env(string:PREVARISC_PLUGIN_GEOCONCEPT_TOKEN)%');

    $parameters->set('env(PREVARISC_PLUGIN_GEOCONCEPT_GEOCODER)', '');

    $parameters->set('app.geoconcept_geocoder', '%env(string:PREVARISC_PLUGIN_GEOCONCEPT_GEOCODER)%');

    $parameters->set('env(PREVARISC_THIRDPARTY_PLUGINS)', '');

    $parameters->set('app.third_party_plugins', '%env(string:PREVARISC_THIRDPARTY_PLUGINS)%');

    $parameters->set('env(PREVARISC_CAS_ENABLED)', 0);

    $parameters->set('app.cas_enabled', '%env(bool:PREVARISC_CAS_ENABLED)%');

    $parameters->set('env(PREVARISC_CAS_HOST)', '');

    $parameters->set('app.cas_host', '%env(string:PREVARISC_CAS_HOST)%');

    $parameters->set('env(PREVARISC_CAS_PORT)', 0);

    $parameters->set('app.cas_port', '%env(int:PREVARISC_CAS_PORT)%');

    $parameters->set('env(PREVARISC_CAS_CONTEXT)', '');

    $parameters->set('app.cas_context', '%env(string:PREVARISC_CAS_CONTEXT)%');

    $parameters->set('env(PREVARISC_CAS_NO_SERVER_VALIDATION)', '');

    $parameters->set('app.cas_no_server_validation', '%env(bool:PREVARISC_CAS_NO_SERVER_VALIDATION)%');

    $parameters->set('env(PREVARISC_CAS_VERSION)', '');

    $parameters->set('app.cas_version', '%env(string:PREVARISC_CAS_VERSION)%');

    $parameters->set('env(PREVARISC_NTLM_ENABLED)', 0);

    $parameters->set('app.ntlm_enabled', '%env(bool:PREVARISC_NTLM_ENABLED)%');

    $parameters->set('env(PREVARISC_LOCAL_SOMMEIL_TYPES)', '7;11');

    $parameters->set('app.local_sommeil_types', '%env(PREVARISC_LOCAL_SOMMEIL_TYPES)%');

    $parameters->set('env(PREVARISC_PROXY_ENABLED)', 0);

    $parameters->set('app.proxy_enabled', '%env(bool:PREVARISC_PROXY_ENABLED)%');

    $parameters->set('env(PREVARISC_PROXY_PROTOCOL)', '');

    $parameters->set('app.proxy_protocol', '%env(string:PREVARISC_PROXY_PROTOCOL)%');

    $parameters->set('env(PREVARISC_PROXY_PORT)', 0);

    $parameters->set('app.proxy_port', '%env(int:PREVARISC_PROXY_PORT)%');

    $parameters->set('env(PREVARISC_PROXY_HOST)', '');

    $parameters->set('app.proxy_host', '%env(string:PREVARISC_PROXY_HOST)%');

    $parameters->set('env(PREVARISC_PROXY_USERNAME)', '');

    $parameters->set('app.proxy_username', '%env(string:PREVARISC_PROXY_USERNAME)%');

    $parameters->set('env(PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE)', 0);

    $parameters->set('app.relance_periodicite_commission', '%env(bool:PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE)%');

    $parameters->set('env(PREVARISC_UNITE_PERIODICITE_ANNEES)', 0);

    $parameters->set('app.periodicite_annees', '%env(bool:PREVARISC_UNITE_PERIODICITE_ANNEES)%');

    $parameters->set('env(PREVARISC_DOSSIERS_MAX_A_AFFICHER)', 5);

    $parameters->set('app.nb_dossiers_a_afficher', '%env(int:PREVARISC_DOSSIERS_MAX_A_AFFICHER)%');

    $parameters->set('env(PREVARISC_COMMISSION_COMMUNALE_TYPE)', 2);

    $parameters->set('app.commission_communale_type', '%env(int:PREVARISC_COMMISSION_COMMUNALE_TYPE)%');
};

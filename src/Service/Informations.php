<?php

namespace App\Service;

use App\Dto\Ldap;

final class Informations
{
    public static function dbUrl(string $dbHost, int $dbPort): string
    {
        return $dbHost.':'.$dbPort;
    }

    public static function cacheUrl(string $cacheHost, int $cachePort): string
    {
        if ('' === $cacheHost) {
            return '';
        }

        if (0 === $cachePort) {
            return $cacheHost;
        }

        return $cacheHost.':'.$cachePort;
    }

    public static function ignKeys(string $ignKeys): array
    {
        return array_filter(explode(',', $ignKeys));
    }

    public static function thirdPartyPlugins(string $thirdPartyPlugins): string
    {
        return implode(', ', explode(';', $thirdPartyPlugins));
    }

    public static function authentification(bool $casEnabled, bool $ntlmEnabled, Ldap $ldapInformations): string
    {
        if ($casEnabled) {
            return 'CAS';
        }

        if ($ntlmEnabled) {
            return 'NTLM + BDD';
        }

        if ($ldapInformations->enabled()) {
            return \sprintf(
                'LDAP + BDD : %s:%d/%s',
                $ldapInformations->host(),
                $ldapInformations->port(),
                $ldapInformations->baseDn()
            );
        }

        return 'BDD';
    }
}

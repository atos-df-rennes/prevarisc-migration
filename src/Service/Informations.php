<?php

namespace App\Service;

use App\Dto\Ldap;

final class Informations
{
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
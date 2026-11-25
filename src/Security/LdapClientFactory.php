<?php

declare(strict_types=1);

namespace App\Security;

use App\Enum\LdapEncryption;
use Symfony\Component\Ldap\Ldap;

final class LdapClientFactory
{
    public static function create(string $host, int $port, LdapEncryption $encryption): Ldap
    {
        $options = ['connection_string' => $encryption->uri($host, $port)];

        if (LdapEncryption::Ssl === $encryption) {
            $options['options'] = ['x_tls_require_cert' => \LDAP_OPT_X_TLS_DEMAND];
        }

        return Ldap::create('ext_ldap', $options);
    }
}

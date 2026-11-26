<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Enum\LdapEncryption;
use App\Security\LdapClientFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Ldap;

final class LdapClientFactoryTest extends TestCase
{
    #[DataProvider('provideCaConfiguration')]
    public function testCaConfiguration(LdapEncryption $encryption, string $caCertFile, bool $expectCa): void
    {
        if (!extension_loaded('ldap')) {
            $this->markTestSkipped('The LDAP extension is required.');
        }

        $ldap = LdapClientFactory::create('ldap.example', 0, $encryption, $caCertFile);
        $adapter = new \ReflectionProperty(Ldap::class, 'adapter')->getValue($ldap);
        $config = new \ReflectionProperty(Adapter::class, 'config')->getValue($adapter);

        $expectedOptions = LdapEncryption::Ssl === $encryption
            ? ['x_tls_require_cert' => \LDAP_OPT_X_TLS_DEMAND]
            : [];
        if ($expectCa) {
            $expectedOptions['x_tls_cacertfile'] = $caCertFile;
        }

        $this->assertSame($expectedOptions, $config['options'] ?? []);
    }

    public static function provideCaConfiguration(): iterable
    {
        yield 'system trust' => [LdapEncryption::Ssl, '', false];
        yield 'specific CA' => [LdapEncryption::Ssl, '/etc/ssl/certs/ldap-ca.crt', true];
        yield 'plaintext ignores CA' => [LdapEncryption::None, '/etc/ssl/certs/ldap-ca.crt', false];
    }
}

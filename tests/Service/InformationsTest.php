<?php

namespace App\Tests\Service;

use App\Dto\Ldap;
use App\Service\Informations;
use PHPUnit\Framework\TestCase;

final class InformationsTest extends TestCase
{
    /** @dataProvider thirdPartyPluginsProvider */
    public function testThirdPartyPlugins(string $thirdPartyPlugins, string $expected): void
    {
        $this->assertSame($expected, Informations::thirdPartyPlugins($thirdPartyPlugins));
    }

    public function thirdPartyPluginsProvider(): array
    {
        return [
            'no third party plugin' => [
                '',
                ''
            ],
            'one third party plugin' => [
                'toto',
                'toto'
            ],
            'multiple third party plugins' => [
                'toto;test',
                'toto, test'
            ]
        ];
    }

    /** @dataProvider authentificationProvider */
    public function testAuthentification(bool $casEnabled, bool $ntlmEnabled, Ldap $ldapInformations, string $expected): void
    {
        $this->assertSame($expected, Informations::authentification($casEnabled, $ntlmEnabled, $ldapInformations));
    }

    public function authentificationProvider(): array
    {
        return [
            'CAS enabled' => [
                true,
                false,
                new Ldap(false, '', 389, ''),
                'CAS'
            ],
            'CAS enabled with NTLM enabled' => [
                true,
                true,
                new Ldap(false, '', 389, ''),
                'CAS'
            ],
            'CAS enabled with LDAP enabled' => [
                true,
                false,
                new Ldap(true, '', 389, ''),
                'CAS'
            ],
            'NTLM enabled' => [
                false,
                true,
                new Ldap(false, '', 389, ''),
                'NTLM + BDD'
            ],
            'NTLM enabled with LDAP enabled' => [
                false,
                true,
                new Ldap(true, '', 389, ''),
                'NTLM + BDD'
            ],
            'LDAP enabled' => [
                false,
                false,
                new Ldap(true, 'ldap://prevarisc.test', 389, 'user={username}'),
                'LDAP + BDD : ldap://prevarisc.test:389/user={username}'
            ],
            'BDD enabled' => [
                false,
                false,
                new Ldap(false, '', 389, ''),
                'BDD'
            ]
        ];
    }
}

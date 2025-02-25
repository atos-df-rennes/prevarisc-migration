<?php

namespace App\Tests\Service;

use App\Dto\Ldap;
use App\Service\Informations;
use PHPUnit\Framework\TestCase;

final class InformationsTest extends TestCase
{
    /** @dataProvider dbProvider */
    public function testDbUrl(string $dbHost, int $dbPort, string $expected): void
    {
        $this->assertSame($expected, Informations::dbUrl($dbHost, $dbPort));
    }

    public function dbProvider(): array
    {
        return [
            [
                'localhost',
                3306,
                'localhost:3306'
            ]
        ];
    }

    /** @dataProvider cacheProvider */
    public function testCacheUrl(string $cacheHost, int $cachePort, string $expected): void
    {
        $this->assertSame($expected, Informations::cacheUrl($cacheHost, $cachePort));
    }

    public function cacheProvider(): array
    {
        return [
            'no cache' => [
                '',
                0,
                ''
            ],
            'cache host' => [
                'localhost',
                0,
                'localhost'
            ],
            'cache port' => [
                '',
                350,
                ''
            ],
            'cache host and port' => [
                'localhost',
                350,
                'localhost:350'
            ]
        ];
    }

    /** @dataProvider ignKeysProvider */
    public function testIgnKeys(string $ignKeys, array $expected): void
    {
        $this->assertSame($expected, Informations::ignKeys($ignKeys));
    }

    public function ignKeysProvider(): array
    {
        return [
            'no ign key' => [
                '',
                []
            ],
            'one ign key' =>  [
                'essentiels',
                ['essentiels']
            ],
            'multiple ign keys' => [
                'essentiels,adresse',
                ['essentiels', 'adresse']
            ]
        ];
    }

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

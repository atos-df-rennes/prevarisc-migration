<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Ldap;
use App\Enum\LdapEncryption;
use App\Service\Informations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InformationsTest extends TestCase
{
    #[DataProvider('dbProvider')]
    public function testDbUrl(string $dbHost, int $dbPort, string $expected): void
    {
        $this->assertSame($expected, Informations::dbUrl($dbHost, $dbPort));
    }

    public static function dbProvider(): \Iterator
    {
        yield [
            'localhost',
            3306,
            'localhost:3306',
        ];
    }

    #[DataProvider('cacheProvider')]
    public function testCacheUrl(string $cacheHost, int $cachePort, string $expected): void
    {
        $this->assertSame($expected, Informations::cacheUrl($cacheHost, $cachePort));
    }

    public static function cacheProvider(): \Iterator
    {
        yield 'no cache' => [
            '',
            0,
            '',
        ];
        yield 'cache host' => [
            'localhost',
            0,
            'localhost',
        ];
        yield 'cache port' => [
            '',
            350,
            '',
        ];
        yield 'cache host and port' => [
            'localhost',
            350,
            'localhost:350',
        ];
    }

    #[DataProvider('ignKeysProvider')]
    public function testIgnKeys(string $ignKeys, array $expected): void
    {
        $this->assertSame($expected, Informations::ignKeys($ignKeys));
    }

    public static function ignKeysProvider(): \Iterator
    {
        yield 'no ign key' => [
            '',
            [],
        ];
        yield 'one ign key' => [
            'essentiels',
            ['essentiels'],
        ];
        yield 'multiple ign keys' => [
            'essentiels,adresse',
            ['essentiels', 'adresse'],
        ];
    }

    #[DataProvider('thirdPartyPluginsProvider')]
    public function testThirdPartyPlugins(string $thirdPartyPlugins, string $expected): void
    {
        $this->assertSame($expected, Informations::thirdPartyPlugins($thirdPartyPlugins));
    }

    public static function thirdPartyPluginsProvider(): \Iterator
    {
        yield 'no third party plugin' => [
            '',
            '',
        ];
        yield 'one third party plugin' => [
            'toto',
            'toto',
        ];
        yield 'multiple third party plugins' => [
            'toto;test',
            'toto, test',
        ];
    }

    #[DataProvider('authentificationProvider')]
    public function testAuthentification(bool $casEnabled, bool $ntlmEnabled, Ldap $ldapInformations, string $expected): void
    {
        $this->assertSame($expected, Informations::authentification($casEnabled, $ntlmEnabled, $ldapInformations));
    }

    public static function authentificationProvider(): \Iterator
    {
        yield 'CAS enabled' => [
            true,
            false,
            new Ldap(false, '', 389, '', LdapEncryption::None),
            'CAS',
        ];
        yield 'CAS enabled with NTLM enabled' => [
            true,
            true,
            new Ldap(false, '', 389, '', LdapEncryption::None),
            'CAS',
        ];
        yield 'CAS enabled with LDAP enabled' => [
            true,
            false,
            new Ldap(true, '', 389, '', LdapEncryption::None),
            'CAS',
        ];
        yield 'NTLM enabled' => [
            false,
            true,
            new Ldap(false, '', 389, '', LdapEncryption::None),
            'NTLM + BDD',
        ];
        yield 'NTLM enabled with LDAP enabled' => [
            false,
            true,
            new Ldap(true, '', 389, '', LdapEncryption::None),
            'NTLM + BDD',
        ];
        yield 'LDAP enabled' => [
            false,
            false,
            new Ldap(true, 'prevarisc.test', 0, 'user={username}', LdapEncryption::None),
            'LDAP + BDD : ldap://prevarisc.test:389/user={username}',
        ];
        yield 'LDAPS enabled' => [
            false,
            false,
            new Ldap(true, 'prevarisc.test', 0, 'dc=prevarisc', LdapEncryption::Ssl),
            'LDAP + BDD : ldaps://prevarisc.test:636/dc=prevarisc',
        ];
        yield 'LDAPS custom port' => [
            false,
            false,
            new Ldap(true, 'prevarisc.test', 1636, 'dc=prevarisc', LdapEncryption::Ssl),
            'LDAP + BDD : ldaps://prevarisc.test:1636/dc=prevarisc',
        ];
        yield 'LDAP custom port' => [
            false,
            false,
            new Ldap(true, 'prevarisc.test', 1389, 'dc=prevarisc', LdapEncryption::None),
            'LDAP + BDD : ldap://prevarisc.test:1389/dc=prevarisc',
        ];
        yield 'BDD enabled' => [
            false,
            false,
            new Ldap(false, '', 389, '', LdapEncryption::None),
            'BDD',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\ValueObject;

use App\ValueObject\RubriqueEtat;
use App\ValueObject\RubriquePreferences;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Teste les deux Value Objects gérant les préférences display/opened d'une rubrique :
 * - RubriquePreferences : préférence utilisateur telle que stockée en base (nullable)
 * - RubriqueEtat : état résolu (toujours défini), soumis via le formulaire d'édition
 *
 * Les deux exposent une API symétrique entre `display` et `opened`.
 */
#[CoversClass(RubriquePreferences::class)]
#[CoversClass(RubriqueEtat::class)]
final class RubriquePreferencesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // RubriquePreferences — préférence stockée, résolution avec fallback
    // -------------------------------------------------------------------------

    public function testAucuneNeContientAucunePreference(): void
    {
        $prefs = RubriquePreferences::aucune();

        $this->assertNull($prefs->getDisplay());
        $this->assertNull($prefs->getOpened());
    }

    #[DataProvider('resolveDisplayProvider')]
    public function testResolveDisplay(?bool $display, bool $defaut, bool $attendu): void
    {
        $prefs = new RubriquePreferences($display, null);

        $this->assertSame($attendu, $prefs->resolveDisplay($defaut));
    }

    /**
     * @return \Iterator<string, array{(bool|null), bool, bool}>
     */
    public static function resolveDisplayProvider(): \Iterator
    {
        yield 'preference true surpasse le defaut false' => [true, false, true];
        yield 'preference false surpasse le defaut true' => [false, true, false];
        yield 'aucune preference, utilise le defaut true' => [null, true, true];
        yield 'aucune preference, utilise le defaut false' => [null, false, false];
    }

    #[DataProvider('resolveOpenedProvider')]
    public function testResolveOpened(?bool $opened, bool $defaut, bool $attendu): void
    {
        $prefs = new RubriquePreferences(null, $opened);

        $this->assertSame($attendu, $prefs->resolveOpened($defaut));
    }

    /**
     * @return \Iterator<string, array{(bool|null), bool, bool}>
     */
    public static function resolveOpenedProvider(): \Iterator
    {
        yield 'preference true surpasse le defaut false' => [true, false, true];
        yield 'preference false surpasse le defaut true' => [false, true, false];
        yield 'aucune preference, utilise le defaut true' => [null, true, true];
        yield 'aucune preference, utilise le defaut false' => [null, false, false];
    }

    // -------------------------------------------------------------------------
    // RubriqueEtat — état résolu, toujours défini, immuable
    // -------------------------------------------------------------------------

    public function testValeursParDefaut(): void
    {
        $etat = new RubriqueEtat();

        $this->assertTrue($etat->isDisplay());
        $this->assertFalse($etat->isOpened());
    }

    public function testWithDisplayRetourneUneNouvelleInstanceSansModifierOpened(): void
    {
        $etat = new RubriqueEtat(true, true);
        $nouvelEtat = $etat->withDisplay(false);

        $this->assertNotSame($etat, $nouvelEtat);
        $this->assertTrue($etat->isDisplay(), 'l\'instance d\'origine doit rester inchangée (immuabilité)');
        $this->assertFalse($nouvelEtat->isDisplay());
        $this->assertTrue($nouvelEtat->isOpened());
    }

    public function testWithOpenedRetourneUneNouvelleInstanceSansModifierDisplay(): void
    {
        $etat = new RubriqueEtat(true, false);
        $nouvelEtat = $etat->withOpened(true);

        $this->assertNotSame($etat, $nouvelEtat);
        $this->assertFalse($etat->isOpened(), 'l\'instance d\'origine doit rester inchangée (immuabilité)');
        $this->assertTrue($nouvelEtat->isOpened());
        $this->assertTrue($nouvelEtat->isDisplay());
    }
}

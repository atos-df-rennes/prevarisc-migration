<?php

declare(strict_types=1);

namespace App\Tests\Service\Search;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Service\Search\SearchQueryHelper;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchQueryHelper::class)]
#[AllowMockObjectsWithoutExpectations]
final class SearchQueryHelperTest extends TestCase
{
    /** @var MockObject&Connection */
    private MockObject $connection;

    private SearchQueryHelper $helper;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->helper = new SearchQueryHelper($this->connection);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // safeQuote
    // ─────────────────────────────────────────────────────────────────────────

    public function testSafeQuoteRetourneLeResultatDeConnectionQuote(): void
    {
        $this->connection->method('quote')->with('test')->willReturn("'test'");

        $this->assertSame("'test'", $this->helper->safeQuote('test'));
    }

    public function testSafeQuoteUtiliseFallbackSiConnectionQuoteRetourneNonString(): void
    {
        // DBAL 2.x : quote() est déclaré sans type de retour et peut retourner false
        $this->connection->method('quote')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Connection::quote() returned a non-string value');

        $this->helper->safeQuote('hello');
    }

    public function testSafeQuoteEchappeLesFallbackAvecAddslashes(): void
    {
        // Ce test n'est plus applicable : le fallback addslashes a été remplacé par une exception.
        // On vérifie que l'exception contient le bon message également pour une valeur avec apostrophe.
        $this->connection->method('quote')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Connection::quote() returned a non-string value');

        $this->helper->safeQuote("it's fine");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // splitLabelAndIdentifiant
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param array{0: string|null, 1: string|null} $expected
     */
    #[DataProvider('splitLabelAndIdentifiantProvider')]
    public function testSplitLabelAndIdentifiant(?string $label, array $expected): void
    {
        $this->assertSame($expected, $this->helper->splitLabelAndIdentifiant($label));
    }

    /**
     * @return \Iterator<string, array{(string | null), array{(string | null), (string | null)}}>
     */
    public static function splitLabelAndIdentifiantProvider(): \Iterator
    {
        yield 'label null' => [null, [null, null]];
        yield 'label vide' => ['', ['', null]];
        yield 'label sans dièse' => ['mairie', ['mairie', null]];
        yield 'label numerique sans dièse' => ['101', ['101', null]];
        yield 'label avec dièse simple' => ['#101', [null, '101']];
        yield 'label avec dièse et suffixe' => ['#101A', [null, '101A']];
        yield 'label uniquement dièse' => ['#', [null, '']];
        yield 'dièse pas en première position' => ['ab#101', ['ab#101', null]];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // buildCriteria — mode exact (=)
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * @param array<int|string, bool|int|string>|bool|float|int|string $value
     */
    #[DataProvider('buildCriteriaExactProvider')]
    public function testBuildCriteriaExactProduitConditionEgalite(string $key, string|int|bool $value, string $quoteInput, string $expected): void
    {
        $this->connection->method('quote')
            ->with($quoteInput)
            ->willReturn(sprintf("'%s'", $quoteInput));

        $this->assertSame($expected, $this->helper->buildCriteria($key, $value));
    }

    /**
     * @return \Iterator<string, array<mixed>>
     */
    public static function buildCriteriaExactProvider(): \Iterator
    {
        yield 'valeur string simple' => ['mon_champ', 'valeur', 'valeur', "(mon_champ='valeur')"];
        yield 'valeur entière' => ['id', 42, '42', "(id='42')"];
        yield 'valeur booléenne true' => ['actif', true, '1', "(actif='1')"];
        yield 'valeur booléenne false' => ['actif', false, '0', "(actif='0')"];
    }

    public function testBuildCriteriaExactAvecTableauProduitConditionOr(): void
    {
        $this->connection->method('quote')
            ->willReturnCallback(fn (string $v): string => sprintf("'%s'", $v));

        $result = $this->helper->buildCriteria('genre.ID_GENRE', ['1', '2', '3']);

        $this->assertSame("(genre.ID_GENRE='1' OR genre.ID_GENRE='2' OR genre.ID_GENRE='3')", $result);
    }

    public function testBuildCriteriaExactAvecTableauDUnElement(): void
    {
        $this->connection->method('quote')
            ->with('5')
            ->willReturn("'5'");

        $this->assertSame("(id='5')", $this->helper->buildCriteria('id', ['5']));
    }

    public function testBuildCriteriaAvecTableauVideLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('buildCriteria() requires a non-empty value.');

        $this->helper->buildCriteria('id', []);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // buildCriteria — mode LIKE (exact=false)
    // ─────────────────────────────────────────────────────────────────────────

    public function testBuildCriteriaLikeProduitConditionLike(): void
    {
        $this->connection->method('quote')
            ->with('%mairie%')
            ->willReturn("'%mairie%'");

        $result = $this->helper->buildCriteria('LIBELLE_ETABLISSEMENTINFORMATIONS', 'mairie', false);

        $this->assertSame("(LIBELLE_ETABLISSEMENTINFORMATIONS LIKE '%mairie%')", $result);
    }

    public function testBuildCriteriaLikeAvecTableauProduitMultiplesLike(): void
    {
        $this->connection->method('quote')
            ->willReturnCallback(fn (string $v): string => sprintf("'%s'", $v));

        $result = $this->helper->buildCriteria('LIBELLE', ['foo', 'bar'], false);

        $this->assertSame("(LIBELLE LIKE '%foo%' OR LIBELLE LIKE '%bar%')", $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // buildCriteria — cohérence exact vs LIKE
    // ─────────────────────────────────────────────────────────────────────────

    public function testBuildCriteriaExactVraiParDefaut(): void
    {
        $this->connection->method('quote')
            ->with('abc')
            ->willReturn("'abc'");

        // Sans passer $exact, doit utiliser = (comportement par défaut)
        $withDefault = $this->helper->buildCriteria('champ', 'abc');
        $withTrue = $this->helper->buildCriteria('champ', 'abc', true);

        $this->assertSame($withTrue, $withDefault);
        $this->assertStringContainsString('=', $withDefault);
        $this->assertStringNotContainsString('LIKE', $withDefault);
    }
}

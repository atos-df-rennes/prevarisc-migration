<?php

namespace App\Tests\Service\Search;

use App\Service\Search\SearchQueryHelper;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Service\Search\SearchQueryHelper
 */
final class SearchQueryHelperTest extends TestCase
{
    /** @var Connection|MockObject */
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

        self::assertSame("'test'", $this->helper->safeQuote('test'));
    }

    public function testSafeQuoteUtiliseFallbackSiConnectionQuoteRetourneNonString(): void
    {
        // DBAL 2.x : quote() est déclaré sans type de retour et peut retourner false
        $this->connection->method('quote')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection::quote() returned a non-string value');

        $this->helper->safeQuote('hello');
    }

    public function testSafeQuoteEchappeLesFallbackAvecAddslashes(): void
    {
        // Ce test n'est plus applicable : le fallback addslashes a été remplacé par une exception.
        // On vérifie que l'exception contient le bon message également pour une valeur avec apostrophe.
        $this->connection->method('quote')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection::quote() returned a non-string value');

        $this->helper->safeQuote("it's fine");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // buildCriteria — mode exact (=)
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * @dataProvider buildCriteriaExactProvider
     *
     * @param array<int|string, bool|int|string>|bool|float|int|string $value
     */
    public function testBuildCriteriaExactProduitConditionEgalite(string $key, $value, string $quoteInput, string $expected): void
    {
        $this->connection->method('quote')
            ->with($quoteInput)
            ->willReturn(sprintf("'%s'", $quoteInput));

        self::assertSame($expected, $this->helper->buildCriteria($key, $value));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function buildCriteriaExactProvider(): array
    {
        return [
            'valeur string simple' => ['mon_champ', 'valeur', 'valeur', "(mon_champ='valeur')"],
            'valeur entière' => ['id', 42, '42', "(id='42')"],
            'valeur booléenne true' => ['actif', true, '1', "(actif='1')"],
            'valeur booléenne false' => ['actif', false, '0', "(actif='0')"],
        ];
    }

    public function testBuildCriteriaExactAvecTableauProduitConditionOr(): void
    {
        $this->connection->method('quote')
            ->willReturnCallback(fn (string $v): string => sprintf("'%s'", $v));

        $result = $this->helper->buildCriteria('genre.ID_GENRE', ['1', '2', '3']);

        self::assertSame("(genre.ID_GENRE='1' OR genre.ID_GENRE='2' OR genre.ID_GENRE='3')", $result);
    }

    public function testBuildCriteriaExactAvecTableauDUnElement(): void
    {
        $this->connection->method('quote')
            ->with('5')
            ->willReturn("'5'");

        self::assertSame("(id='5')", $this->helper->buildCriteria('id', ['5']));
    }

    public function testBuildCriteriaAvecTableauVideLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('buildCriteria() requires a non-empty value.');

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

        self::assertSame("(LIBELLE_ETABLISSEMENTINFORMATIONS LIKE '%mairie%')", $result);
    }

    public function testBuildCriteriaLikeAvecTableauProduitMultiplesLike(): void
    {
        $this->connection->method('quote')
            ->willReturnCallback(fn (string $v): string => sprintf("'%s'", $v));

        $result = $this->helper->buildCriteria('LIBELLE', ['foo', 'bar'], false);

        self::assertSame("(LIBELLE LIKE '%foo%' OR LIBELLE LIKE '%bar%')", $result);
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

        self::assertSame($withTrue, $withDefault);
        self::assertStringContainsString('=', $withDefault);
        self::assertStringNotContainsString('LIKE', $withDefault);
    }
}

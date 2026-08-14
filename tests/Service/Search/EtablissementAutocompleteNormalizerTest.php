<?php

namespace App\Tests\Service\Search;

use App\Constant\GenreEtablissement;
use App\Service\Search\EtablissementAutocompleteNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class EtablissementAutocompleteNormalizerTest extends TestCase
{
    /** @var EtablissementAutocompleteNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new EtablissementAutocompleteNormalizer();
    }

    public function testNormalizeReturnsEmptyArrayForNoResult(): void
    {
        $this->assertSame([], $this->normalizer->normalize([]));
    }

    public function testNormalizeMapsAllExpectedFields(): void
    {
        $results = $this->normalizer->normalize([
            [
                'ID_ETABLISSEMENT' => '42',
                'NUMEROID_ETABLISSEMENT' => 'E12/34',
                'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Mairie de Rennes',
                'ID_GENRE' => (string) GenreEtablissement::ERP,
                'LIBELLE_GENRE' => 'ERP',
                'LIBELLE_COMMUNE_ADRESSE_DEFAULT' => 'RENNES',
            ],
        ]);

        $this->assertSame([
            [
                'id' => 42,
                'numeroId' => 'E12/34',
                'libelle' => 'Mairie de Rennes',
                'genre' => 'ERP',
                'genreId' => GenreEtablissement::ERP,
                'commune' => 'RENNES',
            ],
        ], $results);
    }

    /**
     * La commune affichée dépend du genre, comme dans les vues legacy
     * (nav-main.phtml, dossier/liees.phtml, etablissement/edit.phtml).
     *
     * @dataProvider communeProvider
     */
    public function testCommuneIsResolvedFromGenre(int $genreId, ?string $expectedCommune): void
    {
        $results = $this->normalizer->normalize([
            [
                'ID_ETABLISSEMENT' => 1,
                'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Établissement',
                'ID_GENRE' => $genreId,
                'LIBELLE_COMMUNE_ADRESSE_SITE' => 'COMMUNE SITE',
                'LIBELLE_COMMUNE_ADRESSE_CELLULE' => 'COMMUNE CELLULE',
                'LIBELLE_COMMUNE_ADRESSE_DEFAULT' => 'COMMUNE DEFAUT',
            ],
        ]);

        $this->assertSame($expectedCommune, $results[0]['commune']);
    }

    /**
     * @return array<string, array{0: int, 1: string|null}>
     */
    public function communeProvider(): array
    {
        return [
            'site' => [GenreEtablissement::SITE, 'COMMUNE SITE'],
            'cellule' => [GenreEtablissement::CELLULE, 'COMMUNE CELLULE'],
            'erp' => [GenreEtablissement::ERP, 'COMMUNE DEFAUT'],
            'habitation' => [GenreEtablissement::HABITATION, 'COMMUNE DEFAUT'],
        ];
    }

    public function testMissingOrEmptyValuesAreNormalizedToNull(): void
    {
        $results = $this->normalizer->normalize([
            [
                'ID_ETABLISSEMENT' => 7,
                'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Établissement non localisé',
                'ID_GENRE' => GenreEtablissement::SITE,
                'LIBELLE_COMMUNE_ADRESSE_SITE' => '   ',
            ],
        ]);

        $this->assertSame(7, $results[0]['id']);
        $this->assertNull($results[0]['commune']);
        $this->assertNull($results[0]['genre']);
        $this->assertNull($results[0]['numeroId']);
    }

    public function testLibelleIsAlwaysAString(): void
    {
        $results = $this->normalizer->normalize([
            ['ID_ETABLISSEMENT' => 3, 'ID_GENRE' => GenreEtablissement::ERP],
        ]);

        $this->assertSame('', $results[0]['libelle']);
    }
}

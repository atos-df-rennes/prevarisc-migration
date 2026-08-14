<?php

namespace App\Tests\Entity;

use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour Etablissement::getInformationsAtDate().
 *
 * Contexte : reproduction iso-legacy de la jointure etablissementinformations de Search.php,
 * qui résout la fiche établissement en vigueur à une date donnée (historique de renommage/
 * changement de raison sociale d'un établissement), au lieu de toujours prendre la fiche
 * la plus récente.
 */
class EtablissementGetInformationsAtDateTest extends TestCase
{
    private function createInformations(Etablissement $etablissement, string $date, string $libelle): EtablissementInformations
    {
        $informations = new EtablissementInformations();
        $informations->setDate(new \DateTime($date));
        $informations->setLibelle($libelle);
        $etablissement->addInformations($informations);

        return $informations;
    }

    public function testReturnsMostRecentInformationsAtOrBeforeGivenDate(): void
    {
        $etablissement = new Etablissement();
        $this->createInformations($etablissement, '2010-01-01', 'Ancien nom');
        $this->createInformations($etablissement, '2015-06-15', 'Nom intermédiaire');
        $this->createInformations($etablissement, '2022-01-01', 'Nom actuel');

        $result = $etablissement->getInformationsAtDate(new \DateTime('2016-01-01'));

        $this->assertNotNull($result);
        $this->assertSame('Nom intermédiaire', $result->getLibelle());
    }

    public function testReturnsExactDateMatch(): void
    {
        $etablissement = new Etablissement();
        $this->createInformations($etablissement, '2010-01-01', 'Ancien nom');
        $this->createInformations($etablissement, '2015-06-15', 'Nom intermédiaire');

        $result = $etablissement->getInformationsAtDate(new \DateTime('2015-06-15'));

        $this->assertNotNull($result);
        $this->assertSame('Nom intermédiaire', $result->getLibelle());
    }

    public function testFallsBackToOldestInformationsWhenDateIsBeforeAllHistory(): void
    {
        $etablissement = new Etablissement();
        $this->createInformations($etablissement, '2015-06-15', 'Nom intermédiaire');
        $this->createInformations($etablissement, '2022-01-01', 'Nom actuel');

        // Date antérieure à toute fiche existante : fallback legacy sur la fiche la plus ancienne.
        $result = $etablissement->getInformationsAtDate(new \DateTime('2000-01-01'));

        $this->assertNotNull($result);
        $this->assertSame('Nom intermédiaire', $result->getLibelle());
    }

    public function testReturnsNullWhenEtablissementHasNoInformationsAtAll(): void
    {
        $etablissement = new Etablissement();

        $result = $etablissement->getInformationsAtDate(new \DateTime('2020-01-01'));

        $this->assertNull($result);
    }

    public function testReturnsMostRecentInformationsWhenDateIsAfterAllHistory(): void
    {
        $etablissement = new Etablissement();
        $this->createInformations($etablissement, '2010-01-01', 'Ancien nom');
        $this->createInformations($etablissement, '2022-01-01', 'Nom actuel');

        $result = $etablissement->getInformationsAtDate(new \DateTime('2030-01-01'));

        $this->assertNotNull($result);
        $this->assertSame('Nom actuel', $result->getLibelle());
    }
}

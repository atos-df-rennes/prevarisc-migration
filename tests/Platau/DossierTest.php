<?php

declare(strict_types=1);

namespace App\Tests\Platau;

use App\Platau\Dto\Consultation;
use App\Platau\Dto\Dossier;
use App\Platau\Dto\Nomenclature;
use App\Platau\Dto\Personne;
use App\Platau\Dto\Role;
use PHPUnit\Framework\TestCase;

final class DossierTest extends TestCase
{
    public function testDemandeursDansLOrdreSansRepeterUnePersonnePourSesRoles(): void
    {
        $petitionnaire = new Role(new Nomenclature(1));
        $tiers = new Role(new Nomenclature(2));
        $dossier = new Dossier(new Consultation('ABC'), personnes: [
            new Personne([$tiers], libDenomination: 'Tiers'),
            new Personne([$tiers, $petitionnaire, $petitionnaire], noms: [' Le ', ' Roux'], prenoms: [' Anne ', 'Marie ']),
            new Personne([$petitionnaire], libDenomination: 'Atelier', libRaisonSociale: 'Autre'),
            new Personne([$petitionnaire], libRaisonSociale: 'Association'),
            new Personne([$petitionnaire]),
        ]);

        $this->assertSame('Le Roux Anne Marie / Atelier / Association / Inconnu', $dossier->demandeurs());
    }

    public function testNomsPresentsMaisVidesSansRepliSurLaDenomination(): void
    {
        $personne = new Personne([new Role(new Nomenclature(1))], noms: [], libDenomination: 'Atelier');

        $this->assertSame('', new Dossier(new Consultation('ABC'), personnes: [$personne])->demandeurs());
    }

    public function testSansPetitionnaireLeDemandeurEstNull(): void
    {
        $consultation = new Consultation('ABC');
        $this->assertNull(new Dossier($consultation)->demandeurs());
        $this->assertNull(new Dossier($consultation, personnes: [new Personne(), new Personne([new Role(new Nomenclature('1'))])])->demandeurs());
    }
}

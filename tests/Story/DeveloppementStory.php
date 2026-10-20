<?php

declare(strict_types=1);

namespace App\Tests\Story;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture('developpement')]
final class DeveloppementStory extends Story
{
    public function build(): void
    {
        AvisStory::load();
        CapsuleRubriqueStory::load();
        CategorieStory::load();
        ClasseStory::load();
        ClassementStory::load();
        CommissionTypeStory::load();
        CommissionTypeEvenementStory::load();
        DocmanquantStory::load();
        DossierTypeStory::load();
        DossierNatureListeStory::load();
        FamilleStory::load();
        FonctionStory::load();
        GenreStory::load();
        GroupementTypeStory::load();
        ListeTypeChampRubriqueStory::load();
        PieceJointeStatutStory::load();
        ResourcesStory::load();
        PrivilegesStory::load();
        GroupeDefautStory::load();
        StatutStory::load();
        TypeStory::load();
        TypeActiviteStory::load();
        TypePlanStory::load();
        TypeTexteApplStory::load();
        UtilisateurCiviliteStory::load();
        NavigationAdminStory::load();
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Service;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use App\Constant\TypeChamp as TypeChampConstant;
use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Dossier;
use App\Entity\DossierDisplayRubrique;
use App\Entity\Rubrique;
use App\Entity\TypeChamp;
use App\Entity\Valeur;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\DossierDisplayRubriqueRepository;
use App\Repository\EtablissementDisplayRubriqueRepository;
use App\Repository\ValeurRepository;
use App\Service\Descriptif;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Teste la logique de construction de la structure rubriques/champs/valeurs du service Descriptif.
 *
 * Couvre :
 * - Structure de retour (capsule, rubriques, champs, valeurs)
 * - Champ simple vs champ parent (tableau ou groupe de sous-champs)
 * - Tri des lignes de tableau par idx (ksort)
 * - Exclusion des enfants du niveau racine
 * - Chaîne de résolution de la préférence d'affichage (user pref > defaultDisplay > true)
 * - Préservation de l'ordre des rubriques, champs et sous-champs
 */
#[AllowMockObjectsWithoutExpectations]
final class DescriptifTest extends TestCase
{
    /** @var MockObject&CapsuleRubriqueRepository */
    private MockObject $capsuleRepo;

    /** @var MockObject&ValeurRepository */
    private MockObject $valeurRepo;

    /** @var MockObject&DossierDisplayRubriqueRepository */
    private MockObject $dossierDisplayRepo;

    private Descriptif $service;

    protected function setUp(): void
    {
        $this->capsuleRepo = $this->createMock(CapsuleRubriqueRepository::class);
        $this->valeurRepo = $this->createMock(ValeurRepository::class);
        $this->dossierDisplayRepo = $this->createMock(DossierDisplayRubriqueRepository::class);
        $etablissementDisplayRepo = $this->createStub(EtablissementDisplayRubriqueRepository::class);

        $this->service = new Descriptif(
            $this->capsuleRepo,
            $this->valeurRepo,
            $this->dossierDisplayRepo,
            $etablissementDisplayRepo
        );
    }

    // -------------------------------------------------------------------------
    // Capsule introuvable
    // -------------------------------------------------------------------------

    public function testRetourneStructureVideSiCapsuleIntrouvable(): void
    {
        $this->capsuleRepo->method('findByNomInterneWithFullData')->willReturn(null);
        $this->valeurRepo->method('findByDossier')->willReturn([]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        $result = $this->service->getForDossier(new Dossier(), 'capsuleInexistante');

        $this->assertNull($result['capsule']);
        $this->assertSame([], $result['rubriques']);
    }

    // -------------------------------------------------------------------------
    // Champ simple
    // -------------------------------------------------------------------------

    public function testChampSimpleAvecValeur(): void
    {
        // Arrange
        $champ = $this->buildSimpleChamp(10);
        $rubrique = $this->buildRubriqueWithChamps(1, $champ);
        $valeur = new Valeur()->setStr('texte libre');

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')
            ->willReturn([10 => [$valeur]]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert
        $champResult = $result['rubriques'][0]['champs'][0];
        $this->assertSame($champ, $champResult['champ']);
        $this->assertSame($valeur, $champResult['valeur']);
    }

    public function testChampSimpleSansValeurRetourneNull(): void
    {
        // Arrange
        $champ = $this->buildSimpleChamp(10);
        $rubrique = $this->buildRubriqueWithChamps(1, $champ);

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')->willReturn([]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert
        $this->assertNull($result['rubriques'][0]['champs'][0]['valeur']);
    }

    // -------------------------------------------------------------------------
    // Champ parent — groupe de sous-champs (tableau = false)
    // -------------------------------------------------------------------------

    public function testChampParentSansTableauRetourneSousChamps(): void
    {
        // Arrange
        $champParent = $this->buildParentChamp(20, false);
        $enfant1 = $this->buildSimpleChamp(21);
        $enfant2 = $this->buildSimpleChamp(22);
        $champParent->addEnfant($enfant1);
        $champParent->addEnfant($enfant2);

        $rubrique = $this->buildRubriqueWithChamps(1, $champParent);
        $valeurEnfant1 = new Valeur()->setStr('val1');
        $valeurEnfant2 = new Valeur()->setNumber(42);

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')
            ->willReturn([21 => [$valeurEnfant1], 22 => [$valeurEnfant2]]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert
        $champResult = $result['rubriques'][0]['champs'][0];
        $this->assertSame($champParent, $champResult['champ']);
        $this->assertNull($champResult['valeur']);
        $this->assertArrayHasKey('sous_champs', $champResult);
        $this->assertArrayNotHasKey('lignes', $champResult);
        $this->assertCount(2, $champResult['sous_champs']);
        $this->assertSame($enfant1, $champResult['sous_champs'][0]['champ']);
        $this->assertSame($valeurEnfant1, $champResult['sous_champs'][0]['valeur']);
        $this->assertSame($enfant2, $champResult['sous_champs'][1]['champ']);
        $this->assertSame($valeurEnfant2, $champResult['sous_champs'][1]['valeur']);
    }

    // -------------------------------------------------------------------------
    // Champ parent — tableau (tableau = true)
    // -------------------------------------------------------------------------

    public function testChampParentAvecTableauRetourneLignes(): void
    {
        // Arrange
        $champParent = $this->buildParentChamp(20, true);
        $colonne1 = $this->buildSimpleChamp(31);
        $colonne2 = $this->buildSimpleChamp(32);
        $champParent->addEnfant($colonne1);
        $champParent->addEnfant($colonne2);

        $rubrique = $this->buildRubriqueWithChamps(1, $champParent);
        $ligne1col1 = new Valeur()->setStr('A')->setIdx(1);
        $ligne1col2 = new Valeur()->setStr('B')->setIdx(1);

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')
            ->willReturn([31 => [$ligne1col1], 32 => [$ligne1col2]]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert
        $champResult = $result['rubriques'][0]['champs'][0];
        $this->assertNull($champResult['valeur']);
        $this->assertArrayHasKey('lignes', $champResult);
        $this->assertArrayNotHasKey('sous_champs', $champResult);
        $this->assertCount(1, $champResult['lignes']);
        $this->assertSame($ligne1col1, $champResult['lignes'][0][31]);
        $this->assertSame($ligne1col2, $champResult['lignes'][0][32]);
    }

    public function testLignesTableauSontOrdonneesParIdx(): void
    {
        // Arrange — valeurs fournies dans le désordre (idx=2 avant idx=1)
        // Le service doit les retrier via ksort
        $champParent = $this->buildParentChamp(20, true);
        $colonne = $this->buildSimpleChamp(31);
        $champParent->addEnfant($colonne);

        $rubrique = $this->buildRubriqueWithChamps(1, $champParent);
        $valeurIdx2 = new Valeur()->setStr('deuxieme')->setIdx(2);
        $valeurIdx1 = new Valeur()->setStr('premier')->setIdx(1);

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')
            ->willReturn([31 => [$valeurIdx2, $valeurIdx1]]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert — la ligne avec idx=1 doit être en première position
        $lignes = $result['rubriques'][0]['champs'][0]['lignes'];
        $this->assertCount(2, $lignes);
        $this->assertSame($valeurIdx1, $lignes[0][31]);
        $this->assertSame($valeurIdx2, $lignes[1][31]);
    }

    public function testTableauSansValeurRetourneLignesVides(): void
    {
        // Arrange
        $champParent = $this->buildParentChamp(20, true);
        $colonne = $this->buildSimpleChamp(31);
        $champParent->addEnfant($colonne);

        $rubrique = $this->buildRubriqueWithChamps(1, $champParent);

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')->willReturn([]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert
        $this->assertSame([], $result['rubriques'][0]['champs'][0]['lignes']);
    }

    // -------------------------------------------------------------------------
    // Exclusion des champs enfants du niveau racine
    // -------------------------------------------------------------------------

    public function testChampsEnfantsSontExclusDuNiveauRacine(): void
    {
        // Arrange — simule le comportement Doctrine où les enfants apparaissent dans
        // rubrique->champs (car ils partagent le même ID_RUBRIQUE en base)
        $champParent = $this->buildParentChamp(20, false);
        $enfant = $this->buildSimpleChamp(21);
        $enfant->setParent($champParent);

        $rubrique = $this->buildRubriqueWithChamps(1, $champParent);

        // Force l'enfant dans la collection rubrique->champs, comme Doctrine le ferait via la FK
        $champsRef = new \ReflectionProperty(Rubrique::class, 'champs');
        $champsRef->getValue($rubrique)->add($enfant);

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')->willReturn([]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert — seul le champ parent doit apparaître au niveau racine
        $this->assertCount(1, $result['rubriques'][0]['champs']);
        $this->assertSame($champParent, $result['rubriques'][0]['champs'][0]['champ']);
    }

    // -------------------------------------------------------------------------
    // Chaîne de résolution de la préférence d'affichage
    // -------------------------------------------------------------------------
    #[DataProvider('displayPreferenceProvider')]
    public function testResolutionDePreferenceAffichage(
        ?bool $prefUtilisateur,
        ?bool $defaultDisplay,
        bool $displayAttendu
    ): void {
        // Arrange
        $rubrique = new Rubrique();
        $this->setEntityId($rubrique, 5);
        $rubrique->setDefaultDisplay($defaultDisplay);

        $displayPrefs = [];
        if (null !== $prefUtilisateur) {
            $displayPrefs = [
                new DossierDisplayRubrique()
                    ->setRubrique($rubrique)
                    ->setUserDisplay($prefUtilisateur),
            ];
        }

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')->willReturn([]);
        $this->dossierDisplayRepo->method('findBy')->willReturn($displayPrefs);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert
        $this->assertSame($displayAttendu, $result['rubriques'][0]['display']);
    }

    /**
     * @return \Iterator<string, array{(bool | null), (bool | null), bool}>
     */
    public static function displayPreferenceProvider(): \Iterator
    {
        yield 'pref utilisateur true surpasse defaultDisplay false' => [true, false, true];
        yield 'pref utilisateur false surpasse defaultDisplay true' => [false, true, false];
        yield 'sans pref utilisateur, utilise defaultDisplay true' => [null, true, true];
        yield 'sans pref utilisateur, utilise defaultDisplay false' => [null, false, false];
        yield 'sans pref ni defaultDisplay, vaut true par défaut' => [null, null, true];
    }

    // -------------------------------------------------------------------------
    // Préservation de l'ordre
    // -------------------------------------------------------------------------

    public function testOrdreDesRubriquesEstPreserve(): void
    {
        // Arrange — deux rubriques dans un ordre spécifique (idx déjà triés par le repository)
        $rubrique1 = new Rubrique();
        $this->setEntityId($rubrique1, 1);
        $rubrique1->setIdx(1);

        $rubrique2 = new Rubrique();
        $this->setEntityId($rubrique2, 2);
        $rubrique2->setIdx(2);

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique1, $rubrique2));
        $this->valeurRepo->method('findByDossier')->willReturn([]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert — l'ordre fourni par le repository est préservé tel quel
        $this->assertCount(2, $result['rubriques']);
        $this->assertSame($rubrique1, $result['rubriques'][0]['rubrique']);
        $this->assertSame($rubrique2, $result['rubriques'][1]['rubrique']);
    }

    public function testOrdreDesChampsEstPreserve(): void
    {
        // Arrange — trois champs ajoutés dans l'ordre attendu (idx gérés par le repository)
        $champ1 = $this->buildSimpleChamp(10);
        $champ2 = $this->buildSimpleChamp(11);
        $champ3 = $this->buildSimpleChamp(12);
        $rubrique = $this->buildRubriqueWithChamps(1, $champ1, $champ2, $champ3);

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')->willReturn([]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert
        $champs = $result['rubriques'][0]['champs'];
        $this->assertCount(3, $champs);
        $this->assertSame($champ1, $champs[0]['champ']);
        $this->assertSame($champ2, $champs[1]['champ']);
        $this->assertSame($champ3, $champs[2]['champ']);
    }

    public function testOrdreDesSousChampsEstPreserve(): void
    {
        // Arrange — trois enfants dans l'ordre attendu (idx gérés par le repository)
        $champParent = $this->buildParentChamp(20, false);
        $enfant1 = $this->buildSimpleChamp(21);
        $enfant2 = $this->buildSimpleChamp(22);
        $enfant3 = $this->buildSimpleChamp(23);
        $champParent->addEnfant($enfant1);
        $champParent->addEnfant($enfant2);
        $champParent->addEnfant($enfant3);

        $rubrique = $this->buildRubriqueWithChamps(1, $champParent);

        $dossier = new Dossier();
        $this->capsuleRepo->method('findByNomInterneWithFullData')
            ->willReturn($this->buildCapsule($rubrique));
        $this->valeurRepo->method('findByDossier')->willReturn([]);
        $this->dossierDisplayRepo->method('findBy')->willReturn([]);

        // Act
        $result = $this->service->getForDossier($dossier, 'descriptifEtablissement');

        // Assert
        $sousChamps = $result['rubriques'][0]['champs'][0]['sous_champs'];
        $this->assertCount(3, $sousChamps);
        $this->assertSame($enfant1, $sousChamps[0]['champ']);
        $this->assertSame($enfant2, $sousChamps[1]['champ']);
        $this->assertSame($enfant3, $sousChamps[2]['champ']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function setEntityId(Rubrique|TypeChamp|Champ $entity, int $id): void
    {
        $ref = new \ReflectionProperty($entity::class, 'id');
        $ref->setValue($entity, $id);
    }

    private function buildTypeChamp(int $id): TypeChamp
    {
        $type = new TypeChamp();
        $this->setEntityId($type, $id);

        return $type;
    }

    private function buildSimpleChamp(int $id): Champ
    {
        $champ = new Champ();
        $this->setEntityId($champ, $id);
        $champ->setType($this->buildTypeChamp(TypeChampConstant::TEXT));

        return $champ;
    }

    private function buildParentChamp(int $id, bool $tableau): Champ
    {
        $champ = new Champ();
        $this->setEntityId($champ, $id);
        $champ->setType($this->buildTypeChamp(TypeChampConstant::PARENT));
        $champ->setTableau($tableau);

        return $champ;
    }

    private function buildRubriqueWithChamps(int $id, Champ ...$champs): Rubrique
    {
        $rubrique = new Rubrique();
        $this->setEntityId($rubrique, $id);
        foreach ($champs as $champ) {
            $rubrique->addChamp($champ);
        }

        return $rubrique;
    }

    private function buildCapsule(Rubrique ...$rubriques): CapsuleRubrique
    {
        $capsule = new CapsuleRubrique();
        foreach ($rubriques as $rubrique) {
            $capsule->addRubrique($rubrique);
        }

        return $capsule;
    }
}

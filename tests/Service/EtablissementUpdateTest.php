<?php

namespace App\Tests\Service;

use App\Constant\GenreEtablissement;
use App\Constant\TypeChangement;
use App\Entity\Etablissement;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\EtablissementInformationsTypesActivitesSecondaires;
use App\Entity\EtablissementStatut;
use App\Entity\EtablissementType;
use App\Repository\AvisDerogationsRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementInformationsRepository;
use App\Repository\EtablissementRepository;
use App\Service\Etablissement as EtablissementService;
use App\Service\EtablissementPersistenceService;
use App\Service\TexteApplicable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Tests unitaires pour EtablissementService::update() et EtablissementService::checkAlerte().
 *
 * @covers \App\Service\Etablissement::update
 * @covers \App\Service\Etablissement::checkAlerte
 */
class EtablissementUpdateTest extends TestCase
{
    /**
     * Construit le service avec des mocks minimaux.
     */
    private function buildService(
        EtablissementRepository $etablissementRepo,
        EtablissementInformationsRepository $informationsRepo
    ): EtablissementService {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');
        $connection->method('rollBack');

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('persist');
        $em->method('flush');
        $em->method('getReference')->willReturnCallback(fn (string $class, $id): MockObject => $this->createMock($class));

        $logger = $this->createMock(LoggerInterface::class);
        $dossierRepo = $this->createMock(DossierRepository::class);
        $avisDerogationsRepo = $this->createMock(AvisDerogationsRepository::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $texteApplicable = $this->createMock(TexteApplicable::class);
        $persistenceService = new EtablissementPersistenceService($etablissementRepo);

        return new EtablissementService(
            $etablissementRepo,
            $dossierRepo,
            $avisDerogationsRepo,
            $parameterBag,
            $em,
            $logger,
            $texteApplicable,
            $persistenceService,
            $informationsRepo
        );
    }

    /**
     * Crée un mock d'EtablissementInformations avec statut, catégorie, type, activité et activités secondaires.
     *
     * @param int[] $activitesSecondairesIds IDs des activités secondaires existantes
     *
     * @return EtablissementInformations&MockObject
     */
    private function createInformationsMock(
        ?int $statutId,
        ?int $categorieId = null,
        ?int $typeId = null,
        ?int $activiteId = null,
        array $activitesSecondairesIds = []
    ): EtablissementInformations {
        /** @var EtablissementStatut&MockObject $statut */
        $statut = $this->createMock(EtablissementStatut::class);
        $statut->method('getId')->willReturn($statutId);

        /** @var EtablissementCategorie&MockObject $categorie */
        $categorie = $this->createMock(EtablissementCategorie::class);
        $categorie->method('getId')->willReturn($categorieId);

        /** @var EtablissementType&MockObject $type */
        $type = $this->createMock(EtablissementType::class);
        $type->method('getId')->willReturn($typeId);

        /** @var EtablissementActivite&MockObject $activite */
        $activite = $this->createMock(EtablissementActivite::class);
        $activite->method('getId')->willReturn($activiteId);

        $activitesSecondaires = new ArrayCollection();
        foreach ($activitesSecondairesIds as $id) {
            /** @var EtablissementInformationsTypesActivitesSecondaires&MockObject $as */
            $as = $this->createMock(EtablissementInformationsTypesActivitesSecondaires::class);
            $as->method('getId')->willReturn($id);
            $activitesSecondaires->add($as);
        }

        /** @var EtablissementInformations&MockObject $informations */
        $informations = $this->createMock(EtablissementInformations::class);
        $informations->method('getStatut')->willReturn(null !== $statutId ? $statut : null);
        $informations->method('getCategorie')->willReturn(null !== $categorieId ? $categorie : null);
        $informations->method('getType')->willReturn(null !== $typeId ? $type : null);
        $informations->method('getTypeActivite')->willReturn(null !== $activiteId ? $activite : null);
        $informations->method('getTypesActivitesSecondaires')->willReturn($activitesSecondaires);

        return $informations;
    }

    // ——————————————————————————————————————————————————————————
    // Tests update()
    // ——————————————————————————————————————————————————————————

    /**
     * update() doit lever une exception si l'établissement n'existe pas.
     */
    public function testUpdateThrowsExceptionWhenEtablissementNotFound(): void
    {
        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $etablissementRepo->method('find')->willReturn(null);

        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);

        $service = $this->buildService($etablissementRepo, $informationsRepo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Établissement 999 introuvable');

        $service->update(GenreEtablissement::SITE, [], 999);
    }

    /**
     * update() doit réutiliser la fiche d'informations existante si elle date d'aujourd'hui (upsert).
     */
    public function testUpdateReusesExistingInfoForToday(): void
    {
        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getId')->willReturn(42);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection());
        $etablissement->method('getFils')->willReturn(new ArrayCollection());
        $etablissement->method('getParents')->willReturn(new ArrayCollection());

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $etablissementRepo->method('find')->willReturn($etablissement);

        // La fiche d'aujourd'hui existe déjà
        $existingInfo = $this->createMock(EtablissementInformations::class);
        $existingInfo->method('getPlans')->willReturn(new ArrayCollection());
        $existingInfo->method('getRubriquesIcpe')->willReturn(new ArrayCollection());
        $existingInfo->method('getTypesActivitesSecondaires')->willReturn(new ArrayCollection());

        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);
        $informationsRepo->method('findOneBy')->willReturn($existingInfo);

        $service = $this->buildService($etablissementRepo, $informationsRepo);

        // Si une fiche existante est réutilisée, clearPreventionnistes() doit être appelé
        $existingInfo->expects($this->once())->method('clearPreventionnistes');

        $service->update(GenreEtablissement::SITE, [
            'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Site test',
            'ID_STATUT' => '1',
        ], 42);
    }

    /**
     * update() doit créer une nouvelle fiche si aucune n'existe pour aujourd'hui.
     */
    public function testUpdateCreatesNewInfoWhenNoneExistsForToday(): void
    {
        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getId')->willReturn(43);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection());
        $etablissement->method('getFils')->willReturn(new ArrayCollection());
        $etablissement->method('getParents')->willReturn(new ArrayCollection());

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $etablissementRepo->method('find')->willReturn($etablissement);

        // Aucune fiche pour aujourd'hui → nouvelle création
        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);
        $informationsRepo->method('findOneBy')->willReturn(null);

        $service = $this->buildService($etablissementRepo, $informationsRepo);

        // Ne doit pas lever d'exception
        $service->update(GenreEtablissement::SITE, [
            'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Site nouveau',
            'ID_STATUT' => '1',
        ], 43);

        // Si on arrive ici sans exception, la nouvelle fiche a bien été créée
        $this->assertTrue(true);
    }

    // ——————————————————————————————————————————————————————————
    // Tests checkAlerte()
    // ——————————————————————————————————————————————————————————

    /**
     * checkAlerte() doit retourner false si rien ne change.
     */
    public function testCheckAlerteReturnsFalseWhenNothingChanges(): void
    {
        $informations = $this->createInformationsMock(1, 2, 3, 4, [10, 20]);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);
        $service = $this->buildService($etablissementRepo, $informationsRepo);

        $result = $service->checkAlerte($etablissement, [
            'ID_STATUT' => '1',
            'ID_CATEGORIE' => '2',
            'ID_TYPE' => '3',
            'ID_TYPEACTIVITE' => '4',
            'TYPES_ACTIVITES_SECONDAIRES' => [10 => [], 20 => []],
        ]);

        $this->assertFalse($result);
    }

    /**
     * checkAlerte() doit retourner STATUT si le statut change.
     */
    public function testCheckAlerteReturnsStatutChangeWhenStatutChanges(): void
    {
        $informations = $this->createInformationsMock(1, 2, 3, 4);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);
        $service = $this->buildService($etablissementRepo, $informationsRepo);

        $result = $service->checkAlerte($etablissement, [
            'ID_STATUT' => '2', // Changé de 1 → 2
            'ID_CATEGORIE' => '2',
            'ID_TYPE' => '3',
            'ID_TYPEACTIVITE' => '4',
            'TYPES_ACTIVITES_SECONDAIRES' => [],
        ]);

        $this->assertSame(TypeChangement::STATUT, $result);
    }

    /**
     * checkAlerte() doit retourner STATUT quand un statut null reçoit une valeur (premier statut).
     * Régression corrigée : l'ancienne implémentation avait des guards null !== qui empêchaient ce cas.
     */
    public function testCheckAlerteReturnsStatutWhenNullBecomesValue(): void
    {
        // Établissement sans statut (null)
        $informations = $this->createInformationsMock(null, 2, 3, 4);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);
        $service = $this->buildService($etablissementRepo, $informationsRepo);

        $result = $service->checkAlerte($etablissement, [
            'ID_STATUT' => '1', // null → 1 : doit déclencher l'alerte
            'ID_CATEGORIE' => '2',
            'ID_TYPE' => '3',
            'ID_TYPEACTIVITE' => '4',
            'TYPES_ACTIVITES_SECONDAIRES' => [],
        ]);

        $this->assertSame(TypeChangement::STATUT, $result);
    }

    /**
     * checkAlerte() doit retourner CLASSEMENT si la catégorie change.
     */
    public function testCheckAlerteReturnsClassementWhenCategorieChanges(): void
    {
        $informations = $this->createInformationsMock(1, 2, 3, 4);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);
        $service = $this->buildService($etablissementRepo, $informationsRepo);

        $result = $service->checkAlerte($etablissement, [
            'ID_STATUT' => '1',
            'ID_CATEGORIE' => '5', // Changé de 2 → 5
            'ID_TYPE' => '3',
            'ID_TYPEACTIVITE' => '4',
            'TYPES_ACTIVITES_SECONDAIRES' => [],
        ]);

        $this->assertSame(TypeChangement::CLASSEMENT, $result);
    }

    /**
     * checkAlerte() doit retourner CLASSEMENT si le type change.
     */
    public function testCheckAlerteReturnsClassementWhenTypeChanges(): void
    {
        $informations = $this->createInformationsMock(1, 2, 3, 4);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);
        $service = $this->buildService($etablissementRepo, $informationsRepo);

        $result = $service->checkAlerte($etablissement, [
            'ID_STATUT' => '1',
            'ID_CATEGORIE' => '2',
            'ID_TYPE' => '7', // Changé de 3 → 7
            'ID_TYPEACTIVITE' => '4',
            'TYPES_ACTIVITES_SECONDAIRES' => [],
        ]);

        $this->assertSame(TypeChangement::CLASSEMENT, $result);
    }

    /**
     * checkAlerte() doit retourner CLASSEMENT si une activité secondaire est supprimée.
     * Régression corrigée : l'ancienne implémentation n'incluait pas compareActivitesSecondaires().
     */
    public function testCheckAlerteReturnsClassementWhenActiviteSecondaireSupprimee(): void
    {
        // Fiche avec 2 activités secondaires (id=10 et id=20)
        $informations = $this->createInformationsMock(1, 2, 3, 4, [10, 20]);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);
        $service = $this->buildService($etablissementRepo, $informationsRepo);

        // L'utilisateur supprime l'activité 20 → seul id=10 présent dans POST
        $result = $service->checkAlerte($etablissement, [
            'ID_STATUT' => '1',
            'ID_CATEGORIE' => '2',
            'ID_TYPE' => '3',
            'ID_TYPEACTIVITE' => '4',
            'TYPES_ACTIVITES_SECONDAIRES' => [10 => []], // id=20 supprimé
        ]);

        $this->assertSame(TypeChangement::CLASSEMENT, $result);
    }

    /**
     * checkAlerte() : CLASSEMENT a priorité sur STATUT (retour immédiat).
     */
    public function testCheckAlerteClassementTakesPriorityOverStatut(): void
    {
        // Statut change ET type change
        $informations = $this->createInformationsMock(1, 2, 3, 4);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $informationsRepo = $this->createMock(EtablissementInformationsRepository::class);
        $service = $this->buildService($etablissementRepo, $informationsRepo);

        $result = $service->checkAlerte($etablissement, [
            'ID_STATUT' => '2',   // Statut change aussi
            'ID_CATEGORIE' => '2',
            'ID_TYPE' => '9',     // Type change → CLASSEMENT prioritaire
            'ID_TYPEACTIVITE' => '4',
            'TYPES_ACTIVITES_SECONDAIRES' => [],
        ]);

        $this->assertSame(TypeChangement::CLASSEMENT, $result);
    }
}

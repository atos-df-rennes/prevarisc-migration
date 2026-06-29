<?php

namespace App\Tests\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\Famille;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\Etablissement\EtablissementSiteVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\Voter\EtablissementCollectionAccessChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementSiteVoterTest extends TestCase
{
    /**
     * Crée un mock d'utilisateur avec un groupe.
     *
     * @return MockObject&Utilisateur
     */
    private function createMockUser(): MockObject
    {
        $groupe = $this->createMock(Groupe::class);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        return $user;
    }

    /**
     * Crée un mock de token avec un utilisateur.
     *
     * @param MockObject&Utilisateur $user
     *
     * @return MockObject&TokenInterface
     */
    private function createMockToken(MockObject $user): MockObject
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    /**
     * Crée des mocks pour les repositories (pour les tests qui n'ont pas besoin de logique spécifique).
     *
     * @return array{0: MockObject, 1: MockObject}
     */
    private function createMockRepositories(): array
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);

        return [$resourcesRepository, $privilegesRepository];
    }

    /**
     * Crée un mock d'établissement Site avec des fils.
     *
     * @param Etablissement[] $fils
     *
     * @return MockObject&Etablissement
     */
    private function createMockSite(array $fils = []): MockObject
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => GenreEtablissement::SITE]);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);

        $site = $this->createMock(Etablissement::class);
        $site->method('getLatestInformations')->willReturn($latestInformations);
        $site->method('getFils')->willReturn(new ArrayCollection($fils));

        return $site;
    }

    /**
     * Crée un mock d'établissement ERP fils.
     *
     * @return MockObject&Etablissement
     */
    private function createMockErpFils(): MockObject
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => GenreEtablissement::ERP]);
        $categorie = $this->createConfiguredMock(EtablissementCategorie::class, ['getId' => 1]);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);
        $latestInformations->method('getCategorie')->willReturn($categorie);
        $latestInformations->method('getTypeActivite')->willReturn(null);

        $erp = $this->createMock(Etablissement::class);
        $erp->method('getLatestInformations')->willReturn($latestInformations);
        $erp->method('getAdresses')->willReturn(new ArrayCollection([]));

        return $erp;
    }

    /**
     * Crée un mock d'établissement IGH fils.
     *
     * @return MockObject&Etablissement
     */
    private function createMockIghFils(): MockObject
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => GenreEtablissement::IGH]);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);
        $latestInformations->method('getClasse')->willReturn(null);

        $igh = $this->createMock(Etablissement::class);
        $igh->method('getLatestInformations')->willReturn($latestInformations);
        $igh->method('getAdresses')->willReturn(new ArrayCollection([]));

        return $igh;
    }

    /**
     * Crée un mock d'établissement Habitation fils.
     *
     * @return MockObject&Etablissement
     */
    private function createMockHabitationFils(): MockObject
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => GenreEtablissement::HABITATION]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 1]);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);
        $latestInformations->method('getFamille')->willReturn($famille);

        $habitation = $this->createMock(Etablissement::class);
        $habitation->method('getLatestInformations')->willReturn($latestInformations);
        $habitation->method('getAdresses')->willReturn(new ArrayCollection([]));

        return $habitation;
    }

    /**
     * Test : Site avec fils et accès autorisé → ACCESS_GRANTED.
     * Valide que le voter délègue correctement à hasAtLeastOneAccessible avec le bon ensemble de fils.
     */
    public function testSiteAvecFilsAccordeAccesSiAccesAutorise(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $erpFils = $this->createMockErpFils();
        $site = $this->createMockSite([$erpFils]);

        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        // Vérifie que hasAtLeastOneAccessible est appelée avec le bon attribut
        $collectionAccessChecker->expects(self::once())
            ->method('hasAtLeastOneAccessible')
            ->with([$erpFils], EtablissementVoter::LECTURE, $token)
            ->willReturn(true);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, ...$this->createMockRepositories());

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site avec fils mais accès refusé → ACCESS_DENIED.
     * Valide que le voter propage correctement le refus d'accès.
     */
    public function testSiteAvecFilsRefuseAccesSiAccesRefuse(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $erpFils = $this->createMockErpFils();
        $site = $this->createMockSite([$erpFils]);

        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $collectionAccessChecker->expects(self::once())
            ->method('hasAtLeastOneAccessible')
            ->willReturn(false);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, ...$this->createMockRepositories());

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * Test : Site avec plusieurs fils de genres différents → hasAtLeastOneAccessible est appelée
     * avec tous les fils, peu importe leur genre.
     * Cela valide que le Voter ne filtre pas les genres et délègue correctement.
     */
    public function testSiteAvecFilsMixtesAppelleHasAtLeastOneAccessibleAvecTousLesFils(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $erpFils = $this->createMockErpFils();
        $ighFils = $this->createMockIghFils();
        $habitationFils = $this->createMockHabitationFils();
        $site = $this->createMockSite([$erpFils, $ighFils, $habitationFils]);

        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        // Vérifie que tous les fils sont passés à hasAtLeastOneAccessible
        $collectionAccessChecker->expects(self::once())
            ->method('hasAtLeastOneAccessible')
            ->with([$erpFils, $ighFils, $habitationFils], EtablissementVoter::LECTURE, $token)
            ->willReturn(true);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, ...$this->createMockRepositories());

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site vide avec utilisateur ayant une ressource ERP → ACCESS_GRANTED
     * Permet d'éditer un Site vide pour y ajouter des fils.
     */
    public function testSiteVideAvecRessourceErpAccordeAcces(): void
    {
        $site = $this->createMockSite([]); // Site vide

        // Mock : L'utilisateur a une ressource ERP
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_erp_1_2_0_0_0');

        $privilege = $this->createMock(Privileges::class);

        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        $token = $this->createMockToken($user);

        // Mock ResourcesRepository
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([$resource]);

        // Mock PrivilegesRepository
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site vide avec utilisateur SANS ressource établissement → ACCESS_DENIED.
     */
    public function testSiteVideSansRessourceEtablissementRefuseAcces(): void
    {
        $user = $this->createMock(Utilisateur::class);
        $token = $this->createMockToken($user);
        $site = $this->createMockSite([]); // Site vide

        // Mock : L'utilisateur n'a AUCUNE ressource établissement
        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->method('contains')->willReturn(false); // Aucun privilège

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user->method('getGroupe')->willReturn($groupe);

        // Mock ResourcesRepository : retourne des ressources mais l'utilisateur n'en a aucune
        $resource = $this->createMock(Resources::class);
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([$resource]);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn(null);

        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * Test : Site vide + MODIFICATION + privilège add_etablissement → ACCESS_GRANTED.
     * Miroir du comportement legacy ACL.php:275 : le privilège add_etablissement accorde
     * l'accès en modification à un site vide.
     */
    public function testSiteVideAvecPrivilegeAddEtablissementAccordeModification(): void
    {
        $site = $this->createMockSite([]);

        $privilege = $this->createMock(Privileges::class);

        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        $token = $this->createMockToken($user);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')
            ->with(['name' => EtablissementVoter::CREATE])
            ->willReturn($privilege);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $collectionAccessChecker->expects(self::never())->method('hasAtLeastOneAccessible');

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::MODIFICATION]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site non-vide + MODIFICATION + privilège add_etablissement → ACCESS_GRANTED.
     * Miroir du comportement legacy ACL.php:275 : le privilège add_etablissement accorde
     * l'accès en modification même si les fils ne sont pas accessibles.
     */
    public function testSiteAvecFilsEtPrivilegeAddEtablissementAccordeModification(): void
    {
        $erpFils = $this->createMockErpFils();
        $site = $this->createMockSite([$erpFils]);

        $privilege = $this->createMock(Privileges::class);

        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        $token = $this->createMockToken($user);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')
            ->with(['name' => EtablissementVoter::CREATE])
            ->willReturn($privilege);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        // Le collectionAccessChecker ne doit pas être appelé : add_etablissement court-circuite
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $collectionAccessChecker->expects(self::never())->method('hasAtLeastOneAccessible');

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::MODIFICATION]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site non-vide + MODIFICATION + sans privilège add_etablissement + fils refusent → ACCESS_DENIED.
     * Vérifie que l'absence de add_etablissement laisse le contrôle aux fils.
     */
    public function testSiteAvecFilsSansPrivilegeAddEtablissementRefuseModification(): void
    {
        $erpFils = $this->createMockErpFils();
        $site = $this->createMockSite([$erpFils]);

        $user = $this->createMockUser();
        $token = $this->createMockToken($user);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        // Le privilège add_etablissement n'existe pas ou l'utilisateur ne l'a pas
        $privilegesRepository->method('findOneBy')->willReturn(null);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $collectionAccessChecker->expects(self::once())
            ->method('hasAtLeastOneAccessible')
            ->willReturn(false);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::MODIFICATION]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * Test : Site vide + LECTURE + privilège add_etablissement → ACCESS_DENIED si pas de ressource établissement.
     * Vérifie que add_etablissement n'accorde PAS l'accès en lecture (uniquement en modification).
     */
    public function testSiteVideAvecPrivilegeAddEtablissementRefuseLecture(): void
    {
        $site = $this->createMockSite([]);

        $privilege = $this->createMock(Privileges::class);

        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->method('contains')->willReturn(false);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        $token = $this->createMockToken($user);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        // add_etablissement ne doit pas être vérifié pour LECTURE
        $privilegesRepository->expects(self::never())->method('findOneBy');

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([]);

        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}

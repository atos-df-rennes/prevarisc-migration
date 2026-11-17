<?php

declare(strict_types=1);

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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementSiteVoterTest extends TestCase
{
    /**
     * Crée un mock d'utilisateur avec un groupe.
     */
    private function createMockUser(): Utilisateur
    {
        return $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $this->createStub(Groupe::class)]);
    }

    /**
     * Crée un mock de token avec un utilisateur.
     */
    private function createMockToken(Utilisateur $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    /**
     * Crée des mocks pour les repositories (pour les tests qui n'ont pas besoin de logique spécifique).
     *
     * @return array{0: ResourcesRepository, 1: PrivilegesRepository}
     */
    private function createMockRepositories(): array
    {
        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $privilegesRepository = $this->createStub(PrivilegesRepository::class);

        return [$resourcesRepository, $privilegesRepository];
    }

    /**
     * Crée un mock d'établissement Site avec des fils.
     *
     * @param Etablissement[] $fils
     */
    private function createMockSite(array $fils = []): Etablissement
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::SITE]);
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, ['getGenre' => $genre]);

        return $this->createConfiguredStub(Etablissement::class, [
            'getLatestInformations' => $latestInformations,
            'getFils' => new ArrayCollection($fils),
        ]);
    }

    /**
     * Crée un mock d'établissement ERP fils.
     */
    private function createMockErpFils(): Etablissement
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::ERP]);
        $categorie = $this->createConfiguredStub(EtablissementCategorie::class, ['getId' => 1]);
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, [
            'getGenre' => $genre,
            'getCategorie' => $categorie,
            'getTypeActivite' => null,
        ]);

        return $this->createConfiguredStub(Etablissement::class, [
            'getLatestInformations' => $latestInformations,
            'getAdresses' => new ArrayCollection([]),
        ]);
    }

    /**
     * Crée un mock d'établissement IGH fils.
     */
    private function createMockIghFils(): Etablissement
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::IGH]);
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, [
            'getGenre' => $genre,
            'getClasse' => null,
        ]);

        return $this->createConfiguredStub(Etablissement::class, [
            'getLatestInformations' => $latestInformations,
            'getAdresses' => new ArrayCollection([]),
        ]);
    }

    /**
     * Crée un mock d'établissement Habitation fils.
     */
    private function createMockHabitationFils(): Etablissement
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::HABITATION]);
        $famille = $this->createConfiguredStub(Famille::class, ['getId' => 1]);
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, [
            'getGenre' => $genre,
            'getFamille' => $famille,
        ]);

        return $this->createConfiguredStub(Etablissement::class, [
            'getLatestInformations' => $latestInformations,
            'getAdresses' => new ArrayCollection([]),
        ]);
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
        $collectionAccessChecker->expects($this->once())
            ->method('hasAtLeastOneAccessible')
            ->with([$erpFils], EtablissementVoter::LECTURE, $token)
            ->willReturn(true);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, ...$this->createMockRepositories());

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
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
        $collectionAccessChecker->expects($this->once())
            ->method('hasAtLeastOneAccessible')
            ->willReturn(false);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, ...$this->createMockRepositories());

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
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
        $collectionAccessChecker->expects($this->once())
            ->method('hasAtLeastOneAccessible')
            ->with([$erpFils, $ighFils, $habitationFils], EtablissementVoter::LECTURE, $token)
            ->willReturn(true);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, ...$this->createMockRepositories());

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site vide avec utilisateur ayant une ressource ERP → ACCESS_GRANTED
     * Permet d'éditer un Site vide pour y ajouter des fils.
     */
    public function testSiteVideAvecRessourceErpAccordeAcces(): void
    {
        $site = $this->createMockSite([]); // Site vide

        // Mock : L'utilisateur a une ressource ERP
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_erp_1_2_0_0_0']);

        $privilege = $this->createStub(Privileges::class);

        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->expects($this->once())->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => $userPrivileges]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $groupe]);

        $token = $this->createMockToken($user);

        // Mock ResourcesRepository
        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([$resource]);

        // Mock PrivilegesRepository
        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        $collectionAccessChecker = $this->createStub(EtablissementCollectionAccessChecker::class);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site vide avec utilisateur SANS ressource établissement → ACCESS_DENIED.
     */
    public function testSiteVideSansRessourceEtablissementRefuseAcces(): void
    {
        $user = $this->createStub(Utilisateur::class);
        $token = $this->createMockToken($user);
        $site = $this->createMockSite([]); // Site vide

        // Mock : L'utilisateur n'a AUCUNE ressource établissement
        $userPrivileges = $this->createStub(Collection::class);
        $userPrivileges->method('contains')->willReturn(false); // Aucun privilège

        $groupe = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => $userPrivileges]);
        $user->method('getGroupe')->willReturn($groupe);

        // Mock ResourcesRepository : retourne des ressources mais l'utilisateur n'en a aucune
        $resource = $this->createStub(Resources::class);
        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([$resource]);

        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn(null);

        $collectionAccessChecker = $this->createStub(EtablissementCollectionAccessChecker::class);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * Test : Site vide + MODIFICATION + privilège add_etablissement → ACCESS_GRANTED.
     * Miroir du comportement legacy ACL.php:275 : le privilège add_etablissement accorde
     * l'accès en modification à un site vide.
     */
    public function testSiteVideAvecPrivilegeAddEtablissementAccordeModification(): void
    {
        $site = $this->createMockSite([]);

        $privilege = $this->createStub(Privileges::class);

        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->expects($this->once())->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => $userPrivileges]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $groupe]);

        $token = $this->createMockToken($user);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => EtablissementVoter::CREATE])
            ->willReturn($privilege);

        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $collectionAccessChecker->expects($this->never())->method('hasAtLeastOneAccessible');

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::MODIFICATION]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
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

        $privilege = $this->createStub(Privileges::class);

        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->expects($this->once())->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => $userPrivileges]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $groupe]);

        $token = $this->createMockToken($user);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => EtablissementVoter::CREATE])
            ->willReturn($privilege);

        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        // Le collectionAccessChecker ne doit pas être appelé : add_etablissement court-circuite
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $collectionAccessChecker->expects($this->never())->method('hasAtLeastOneAccessible');

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::MODIFICATION]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
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

        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        // Le privilège add_etablissement n'existe pas ou l'utilisateur ne l'a pas
        $privilegesRepository->method('findOneBy')->willReturn(null);

        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $collectionAccessChecker->expects($this->once())
            ->method('hasAtLeastOneAccessible')
            ->willReturn(false);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::MODIFICATION]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * Test : Site vide + LECTURE + privilège add_etablissement → ACCESS_DENIED si pas de ressource établissement.
     * Vérifie que add_etablissement n'accorde PAS l'accès en lecture (uniquement en modification).
     */
    public function testSiteVideAvecPrivilegeAddEtablissementRefuseLecture(): void
    {
        $site = $this->createMockSite([]);

        $privilege = $this->createStub(Privileges::class);

        $userPrivileges = $this->createStub(Collection::class);
        $userPrivileges->method('contains')->willReturn(false);

        $groupe = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => $userPrivileges]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $groupe]);

        $token = $this->createMockToken($user);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        // add_etablissement ne doit pas être vérifié pour LECTURE
        $privilegesRepository->expects($this->never())->method('findOneBy');

        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([]);

        $collectionAccessChecker = $this->createStub(EtablissementCollectionAccessChecker::class);

        $siteVoter = new EtablissementSiteVoter($collectionAccessChecker, $resourcesRepository, $privilegesRepository);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}

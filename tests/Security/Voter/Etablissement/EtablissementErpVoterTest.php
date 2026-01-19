<?php

namespace App\Tests\Security\Voter;

use App\Constant\GenreEtablissement;
use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Groupement;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\EtablissementErpVoter;
use App\Security\Voter\EtablissementVoter;
use App\Service\Voter\VoterUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementErpVoterTest extends TestCase
{
    private function createMockUserWithPrivileges(MockObject $privilege, array $groupements = [], $commune = null): MockObject
    {
        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);
        $user->method('getGroupements')->willReturn(new ArrayCollection($groupements));
        $user->method('getCommune')->willReturn($commune);

        return $user;
    }

    private function createMockEtablissement(int $typeActivite, int $categorie, array $communes = []): MockObject
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => GenreEtablissement::ERP]);
        $typeActivite = $this->createConfiguredMock(EtablissementActivite::class, ['getId' => $typeActivite]);
        $categorie = $this->createConfiguredMock(EtablissementCategorie::class, ['getId' => $categorie]);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);
        $latestInformations->method('getTypeActivite')->willReturn($typeActivite);
        $latestInformations->method('getCategorie')->willReturn($categorie);

        $adresses = array_map(function ($commune): MockObject {
            $adresse = $this->createMock(EtablissementAdresse::class);
            $adresse->method('getCommune')->willReturn($commune);

            return $adresse;
        }, $communes);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($latestInformations);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection($adresses));

        return $etablissement;
    }

    private function createVoter(array $resources, MockObject $privilege): EtablissementErpVoter
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissementErp')->willReturn($resources);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        return new EtablissementErpVoter($resourcesRepository, $privilegesRepository, new VoterUtils());
    }

    public function testAccessSingleCategoryGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_erp_1_2_0_0_0');
        $privilege = $this->createMock(Privileges::class);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement(1, 2);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessMultipleCategoriesGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_erp_1_2-3_0_0_0');
        $privilege = $this->createMock(Privileges::class);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subjectCat2 = $this->createMockEtablissement(1, 2);
        $subjectCat3 = $this->createMockEtablissement(1, 3);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCat2, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCat3, [EtablissementVoter::LECTURE]));
    }

    public function testAccessWithGroupementsGrantsAccessOnlyForMatchingGroupements(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_erp_1_2_0_1_0');
        $privilege = $this->createMock(Privileges::class);

        $commune10 = $this->createConfiguredMock(Commune::class, ['getId' => 100]);
        $commune30 = $this->createConfiguredMock(Commune::class, ['getId' => 300]);
        $groupement10 = $this->createMock(Groupement::class);
        $groupement10->method('getCommunes')->willReturn(new ArrayCollection([$commune10]));

        $user = $this->createMockUserWithPrivileges($privilege, [$groupement10]);
        $subjectCommune10 = $this->createMockEtablissement(1, 2, [$commune10]);
        $subjectCommune30 = $this->createMockEtablissement(1, 2, [$commune30]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]));
    }

    public function testAccessWithCommuneGrantsAccessOnlyForMatchingCommune(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_erp_1_2_0_0_1');
        $privilege = $this->createMock(Privileges::class);

        $commune100 = $this->createConfiguredMock(Commune::class, ['getId' => 100]);
        $commune200 = $this->createConfiguredMock(Commune::class, ['getId' => 200]);

        $user = $this->createMockUserWithPrivileges($privilege, [], $commune100);
        $subjectCommune100 = $this->createMockEtablissement(1, 2, [$commune100]);
        $subjectCommune200 = $this->createMockEtablissement(1, 2, [$commune200]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune100, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune200, [EtablissementVoter::LECTURE]));
    }

    public function testAccessWithMultipleResourcesGrantsAccessIfOneIsValid(): void
    {
        $resource1 = $this->createMock(Resources::class);
        $resource1->method('getName')->willReturn('etablissement_erp_1_2_0_0_0');
        $resource2 = $this->createMock(Resources::class);
        $resource2->method('getName')->willReturn('etablissement_erp_1_3_0_0_0');
        $privilege = $this->createMock(Privileges::class);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subjectCat2 = $this->createMockEtablissement(1, 2);
        $subjectCat3 = $this->createMockEtablissement(1, 3);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource1, $resource2], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCat2, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCat3, [EtablissementVoter::LECTURE]));
    }

    public function testAccessDeniedIfNoResourceIsValid(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_erp_1_2_0_0_0');
        $privilege = $this->createMock(Privileges::class);

        $userPrivileges = $this->createMock(Collection::class);
        $userPrivileges->method('contains')->with($privilege)->willReturn(false);
        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);
        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        $subject = $this->createMockEtablissement(1, 2);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }
}

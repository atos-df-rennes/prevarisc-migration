<?php

namespace App\Tests\Security\Voter;

use App\Entity\{Commune, Etablissement, EtablissementAdresse, EtablissementInformations, Famille, Genre, Groupement, Privileges, Resources, Utilisateur, Groupe};
use App\Repository\{PrivilegesRepository, ResourcesRepository};
use App\Security\Voter\{EtablissementHabitationVoter, EtablissementVoter};
use App\Service\Voter\VoterUtils;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementHabitationVoterTest extends TestCase
{
    private function createMockUserWithPrivileges($privilege, $groupements = [], $commune = null)
    {
        $userPrivileges = $this->createMock('Doctrine\\Common\\Collections\\Collection');
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);
        $user->method('getGroupements')->willReturn(new ArrayCollection($groupements));
        $user->method('getCommune')->willReturn($commune);

        return $user;
    }

    private function createMockEtablissement($famille, $genre, $communes = [])
    {
        $adresses = array_map(function ($commune) {
            $adresse = $this->createMock(EtablissementAdresse::class);
            $adresse->method('getCommune')->willReturn($commune);
            return $adresse;
        }, $communes);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getFamille')->willReturn($famille);
        $latestInformations->method('getGenre')->willReturn($genre);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($latestInformations);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection($adresses));

        return $etablissement;
    }

    private function createVoter($resources, $privilege, $voterUtils)
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->with(4)->willReturn($resources);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        return new EtablissementHabitationVoter($resourcesRepository, $privilegesRepository, $voterUtils);
    }

    public function testAccessAllFamillesRuleGrantsAccess()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_0_0_0');
        $privilege = $this->createMock(Privileges::class);
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille = null;
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($famille, $genre);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $voterUtils = new VoterUtils();
        $voter = $this->createVoter([$resource], $privilege, $voterUtils);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessSpecificFamilleRuleGrantsAccess()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_3_0_0');
        $privilege = $this->createMock(Privileges::class);
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($famille, $genre);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $voterUtils = new VoterUtils();
        $voter = $this->createVoter([$resource], $privilege, $voterUtils);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessMultipleFamillesRuleGrantsAccess()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_2-3_0_0');
        $privilege = $this->createMock(Privileges::class);
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille2 = $this->createConfiguredMock(Famille::class, ['getId' => 2]);
        $famille3 = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject2 = $this->createMockEtablissement($famille2, $genre);
        $subject3 = $this->createMockEtablissement($famille3, $genre);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $voterUtils = new VoterUtils();
        $voter = $this->createVoter([$resource], $privilege, $voterUtils);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject2, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject3, [EtablissementVoter::LECTURE]));
    }

    public function testAccessFamilleWithGroupementsRuleGrantsAccessOnlyForMatchingGroupements()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_3_1_0');
        $privilege = $this->createMock(Privileges::class);
        $commune10 = $this->createConfiguredMock(Commune::class, ['getId' => 100]);
        $commune30 = $this->createConfiguredMock(Commune::class, ['getId' => 300]);
        $groupement10 = $this->createMock(Groupement::class);
        $groupement10->method('getCommunes')->willReturn(new ArrayCollection([$commune10]));
        $user = $this->createMockUserWithPrivileges($privilege, [$groupement10]);
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $subjectCommune10 = $this->createMockEtablissement($famille, $genre, [$commune10]);
        $subjectCommune30 = $this->createMockEtablissement($famille, $genre, [$commune30]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $voterUtils = new VoterUtils();
        $voter = $this->createVoter([$resource], $privilege, $voterUtils);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]));
    }

    public function testAccessFamilleWithCommuneRuleGrantsAccessOnlyForMatchingCommune()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_3_0_1');
        $privilege = $this->createMock(Privileges::class);
        $commune100 = $this->createConfiguredMock(Commune::class, ['getId' => 100]);
        $commune200 = $this->createConfiguredMock(Commune::class, ['getId' => 200]);
        $user = $this->createMockUserWithPrivileges($privilege, [], $commune100);
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $subjectCommune100 = $this->createMockEtablissement($famille, $genre, [$commune100]);
        $subjectCommune200 = $this->createMockEtablissement($famille, $genre, [$commune200]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $voterUtils = new VoterUtils();
        $voter = $this->createVoter([$resource], $privilege, $voterUtils);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune100, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune200, [EtablissementVoter::LECTURE]));
    }

    public function testAccessFamilleWithGroupementsAndAllRuleGrantsAccessToAllEstablishmentsOfFamille()
    {
        $resourceGroupements = $this->createMock(Resources::class);
        $resourceGroupements->method('getName')->willReturn('etablissement_habitation_3_1_0');
        $resourceAll = $this->createMock(Resources::class);
        $resourceAll->method('getName')->willReturn('etablissement_habitation_3_0_0');
        $privilege = $this->createMock(Privileges::class);
        $commune10 = $this->createConfiguredMock(Commune::class, ['getId' => 100]);
        $commune30 = $this->createConfiguredMock(Commune::class, ['getId' => 300]);
        $groupement10 = $this->createMock(Groupement::class);
        $groupement10->method('getCommunes')->willReturn(new ArrayCollection([$commune10]));
        $user = $this->createMockUserWithPrivileges($privilege, [$groupement10]);
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $subjectCommune10 = $this->createMockEtablissement($famille, $genre, [$commune10]);
        $subjectCommune30 = $this->createMockEtablissement($famille, $genre, [$commune30]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $voterUtils = new VoterUtils();
        $voter = $this->createVoter([$resourceGroupements, $resourceAll], $privilege, $voterUtils);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]));
    }
}

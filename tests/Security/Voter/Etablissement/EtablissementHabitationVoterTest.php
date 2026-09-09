<?php

namespace App\Tests\Security\Voter\Etablissement;

use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementInformations;
use App\Entity\Famille;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Groupement;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\Etablissement\EtablissementHabitationVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\Voter\VoterUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementHabitationVoterTest extends TestCase
{
    /**
     * @param Privileges&MockObject $privilege
     */
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

    private function createMockEtablissement(?MockObject $famille, array $communes = []): MockObject
    {
        $adresses = array_map(function ($commune): MockObject {
            $adresse = $this->createMock(EtablissementAdresse::class);
            $adresse->method('getCommune')->willReturn($commune);

            return $adresse;
        }, $communes);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getFamille')->willReturn($famille);
        $latestInformations->method('getGenre')->willReturn($this->createConfiguredMock(Genre::class, ['getId' => 4]));

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($latestInformations);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection($adresses));

        return $etablissement;
    }

    /**
     * @param Privileges&MockObject $privilege
     */
    private function createVoter(array $resources, MockObject $privilege): EtablissementHabitationVoter
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissementHabitation')->willReturn($resources);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        return new EtablissementHabitationVoter($resourcesRepository, $privilegesRepository, new VoterUtils());
    }

    public function testAccessAllFamillesRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_0_0_0');
        $privilege = $this->createMock(Privileges::class);

        $famille = null;
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($famille);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessSpecificFamilleRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_3_0_0');
        $privilege = $this->createMock(Privileges::class);

        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($famille);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessMultipleFamillesRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_2-3_0_0');
        $privilege = $this->createMock(Privileges::class);

        $famille2 = $this->createConfiguredMock(Famille::class, ['getId' => 2]);
        $famille3 = $this->createConfiguredMock(Famille::class, ['getId' => 3]);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subject2 = $this->createMockEtablissement($famille2);
        $subject3 = $this->createMockEtablissement($famille3);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject2, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject3, [EtablissementVoter::LECTURE]));
    }

    public function testAccessFamilleWithGroupementsRuleGrantsAccessOnlyForMatchingGroupements(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_3_1_0');
        $privilege = $this->createMock(Privileges::class);

        $commune10 = $this->createConfiguredMock(Commune::class, ['getId' => '100']);
        $commune30 = $this->createConfiguredMock(Commune::class, ['getId' => '300']);
        $groupement10 = $this->createMock(Groupement::class);
        $groupement10->method('getCommunes')->willReturn(new ArrayCollection([$commune10]));

        $user = $this->createMockUserWithPrivileges($privilege, [$groupement10]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $subjectCommune10 = $this->createMockEtablissement($famille, [$commune10]);
        $subjectCommune30 = $this->createMockEtablissement($famille, [$commune30]);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]));
    }

    public function testAccessFamilleWithCommuneRuleGrantsAccessOnlyForMatchingCommune(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_3_0_1');
        $privilege = $this->createMock(Privileges::class);

        $commune100 = $this->createConfiguredMock(Commune::class, ['getId' => '100']);
        $commune200 = $this->createConfiguredMock(Commune::class, ['getId' => '200']);

        $user = $this->createMockUserWithPrivileges($privilege, [], $commune100);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $subjectCommune100 = $this->createMockEtablissement($famille, [$commune100]);
        $subjectCommune200 = $this->createMockEtablissement($famille, [$commune200]);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune100, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune200, [EtablissementVoter::LECTURE]));
    }

    public function testAccessFamilleWithGroupementsAndAllRuleGrantsAccessToAllEstablishmentsOfFamille(): void
    {
        $resourceGroupements = $this->createMock(Resources::class);
        $resourceGroupements->method('getName')->willReturn('etablissement_habitation_3_1_0');
        $resourceAll = $this->createMock(Resources::class);
        $resourceAll->method('getName')->willReturn('etablissement_habitation_3_0_0');
        $privilege = $this->createMock(Privileges::class);

        $commune10 = $this->createConfiguredMock(Commune::class, ['getId' => '100']);
        $commune30 = $this->createConfiguredMock(Commune::class, ['getId' => '300']);
        $groupement10 = $this->createMock(Groupement::class);
        $groupement10->method('getCommunes')->willReturn(new ArrayCollection([$commune10]));

        $user = $this->createMockUserWithPrivileges($privilege, [$groupement10]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $subjectCommune10 = $this->createMockEtablissement($famille, [$commune10]);
        $subjectCommune30 = $this->createMockEtablissement($famille, [$commune30]);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resourceGroupements, $resourceAll], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]));
    }
}

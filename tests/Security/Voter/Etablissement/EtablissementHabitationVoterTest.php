<?php

declare(strict_types=1);

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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementHabitationVoterTest extends TestCase
{
    private function createMockUserWithPrivileges(Privileges $privilege, array $groupements = [], $commune = null): Utilisateur
    {
        $userPrivileges = $this->createStub(Collection::class);
        $userPrivileges->method('contains')->willReturnCallback(static fn (mixed $candidate): bool => $candidate === $privilege);

        return $this->createConfiguredStub(Utilisateur::class, [
            'getGroupe' => $this->createConfiguredStub(Groupe::class, ['getPrivileges' => $userPrivileges]),
            'getGroupements' => new ArrayCollection($groupements),
            'getCommune' => $commune,
        ]);
    }

    private function createMockEtablissement(?Famille $famille, array $communes = []): Etablissement
    {
        $adresses = array_map(
            fn ($commune): EtablissementAdresse => $this->createConfiguredStub(EtablissementAdresse::class, ['getCommune' => $commune]),
            $communes
        );
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, [
            'getFamille' => $famille,
            'getGenre' => $this->createConfiguredStub(Genre::class, ['getId' => 4]),
        ]);

        return $this->createConfiguredStub(Etablissement::class, [
            'getLatestInformations' => $latestInformations,
            'getAdresses' => new ArrayCollection($adresses),
        ]);
    }

    private function createVoter(array $resources, Privileges $privilege): EtablissementHabitationVoter
    {
        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissementHabitation')->willReturn($resources);

        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        return new EtablissementHabitationVoter($resourcesRepository, $privilegesRepository, new VoterUtils());
    }

    public function testAccessAllFamillesRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_habitation_0_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $famille = null;
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($famille);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessSpecificFamilleRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_habitation_3_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $famille = $this->createConfiguredStub(Famille::class, ['getId' => 3]);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($famille);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessMultipleFamillesRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_habitation_2-3_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $famille2 = $this->createConfiguredStub(Famille::class, ['getId' => 2]);
        $famille3 = $this->createConfiguredStub(Famille::class, ['getId' => 3]);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subject2 = $this->createMockEtablissement($famille2);
        $subject3 = $this->createMockEtablissement($famille3);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject2, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject3, [EtablissementVoter::LECTURE]));
    }

    public function testAccessFamilleWithGroupementsRuleGrantsAccessOnlyForMatchingGroupements(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_habitation_3_1_0']);
        $privilege = $this->createStub(Privileges::class);

        $commune10 = $this->createConfiguredStub(Commune::class, ['getId' => '100']);
        $commune30 = $this->createConfiguredStub(Commune::class, ['getId' => '300']);
        $groupement10 = $this->createConfiguredStub(Groupement::class, ['getCommunes' => new ArrayCollection([$commune10])]);

        $user = $this->createMockUserWithPrivileges($privilege, [$groupement10]);
        $famille = $this->createConfiguredStub(Famille::class, ['getId' => 3]);
        $subjectCommune10 = $this->createMockEtablissement($famille, [$commune10]);
        $subjectCommune30 = $this->createMockEtablissement($famille, [$commune30]);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]));
    }

    public function testAccessFamilleWithCommuneRuleGrantsAccessOnlyForMatchingCommune(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_habitation_3_0_1']);
        $privilege = $this->createStub(Privileges::class);

        $commune100 = $this->createConfiguredStub(Commune::class, ['getId' => '100']);
        $commune200 = $this->createConfiguredStub(Commune::class, ['getId' => '200']);

        $user = $this->createMockUserWithPrivileges($privilege, [], $commune100);
        $famille = $this->createConfiguredStub(Famille::class, ['getId' => 3]);
        $subjectCommune100 = $this->createMockEtablissement($famille, [$commune100]);
        $subjectCommune200 = $this->createMockEtablissement($famille, [$commune200]);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune100, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune200, [EtablissementVoter::LECTURE]));
    }

    public function testAccessFamilleWithGroupementsAndAllRuleGrantsAccessToAllEstablishmentsOfFamille(): void
    {
        $resourceGroupements = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_habitation_3_1_0']);
        $resourceAll = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_habitation_3_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $commune10 = $this->createConfiguredStub(Commune::class, ['getId' => '100']);
        $commune30 = $this->createConfiguredStub(Commune::class, ['getId' => '300']);
        $groupement10 = $this->createConfiguredStub(Groupement::class, ['getCommunes' => new ArrayCollection([$commune10])]);

        $user = $this->createMockUserWithPrivileges($privilege, [$groupement10]);
        $famille = $this->createConfiguredStub(Famille::class, ['getId' => 3]);
        $subjectCommune10 = $this->createMockEtablissement($famille, [$commune10]);
        $subjectCommune30 = $this->createMockEtablissement($famille, [$commune30]);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resourceGroupements, $resourceAll], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]));
    }
}

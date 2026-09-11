<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Commission;
use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Groupement;
use App\Entity\IghClasse;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\Etablissement\EtablissementIghVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\Voter\VoterUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementIghVoterTest extends TestCase
{
    /**
     * @param Privileges&MockObject $privilege
     */
    private function createMockUserWithPrivileges(MockObject $privilege, array $groupements = [], MockObject|Stub|null $commune = null): MockObject
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

    private function createMockEtablissement(?MockObject $classe, array $communes = [], MockObject|Stub|null $commission = null): MockObject
    {
        $adresses = array_map(function ($commune): MockObject {
            $adresse = $this->createMock(EtablissementAdresse::class);
            $adresse->method('getCommune')->willReturn($commune);

            return $adresse;
        }, $communes);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getClasse')->willReturn($classe);
        $latestInformations->method('getGenre')->willReturn($this->createConfiguredMock(Genre::class, ['getId' => GenreEtablissement::IGH]));
        $latestInformations->method('getCommission')->willReturn($commission);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($latestInformations);
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection($adresses));

        return $etablissement;
    }

    /**
     * @param Privileges&MockObject $privilege
     */
    private function createVoter(array $resources, MockObject $privilege): EtablissementIghVoter
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissementIgh')->willReturn($resources);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        return new EtablissementIghVoter($resourcesRepository, $privilegesRepository, new VoterUtils());
    }

    public function testAccessAllClassesRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_0_0_0_0');
        $privilege = $this->createMock(Privileges::class);

        $classe = null;
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($classe);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessSpecificClasseRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_3_0_0_0');
        $privilege = $this->createMock(Privileges::class);

        $classe = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($classe);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessMultipleClassesRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_2-3_0_0_0');
        $privilege = $this->createMock(Privileges::class);

        $classe2 = $this->createConfiguredMock(IghClasse::class, ['getId' => 2]);
        $classe3 = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subject2 = $this->createMockEtablissement($classe2);
        $subject3 = $this->createMockEtablissement($classe3);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject2, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject3, [EtablissementVoter::LECTURE]));
    }

    public function testAccessDeniedIfNoMatchingClasse(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_2_0_0_0');
        $privilege = $this->createMock(Privileges::class);

        $classe = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($classe);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([], $privilege);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithGroupementRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_3_0_1_0');
        $privilege = $this->createMock(Privileges::class);

        $classe = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);
        $commune = $this->createStub(Commune::class);
        $groupement = $this->createMock(Groupement::class);
        $groupement->method('getCommunes')->willReturn(new ArrayCollection([$commune]));
        $user = $this->createMockUserWithPrivileges($privilege, [$groupement]);
        $subject = $this->createMockEtablissement($classe, [$commune]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithCommuneRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_3_0_0_1');
        $privilege = $this->createMock(Privileges::class);

        $classe = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);
        $commune = $this->createStub(Commune::class);
        $user = $this->createMockUserWithPrivileges($privilege, [], $commune);
        $subject = $this->createMockEtablissement($classe, [$commune]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithGroupementAndCommuneRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_3_0_1_1');
        $privilege = $this->createMock(Privileges::class);

        $classe = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);
        $commune = $this->createStub(Commune::class);
        $groupement = $this->createMock(Groupement::class);
        $groupement->method('getCommunes')->willReturn(new ArrayCollection([$commune]));
        $user = $this->createMockUserWithPrivileges($privilege, [$groupement], $commune);
        $subject = $this->createMockEtablissement($classe, [$commune]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessDeniedIfGroupementOrCommuneNotMatching(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_3_0_1_1');
        $privilege = $this->createMock(Privileges::class);

        $classe = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);
        $commune = $this->createStub(Commune::class);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($classe, [$commune]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithCommissionRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_3_1_0_0');
        $privilege = $this->createMock(Privileges::class);

        $classe = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);
        $commission = $this->createStub(Commission::class);
        $user = $this->createMockUserWithPrivileges($privilege);
        $user->method('getCommissions')->willReturn(new ArrayCollection([$commission]));

        $subject = $this->createMockEtablissement($classe, [], $commission);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithCommissionGroupementCommuneRuleGrantsAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_3_1_1_1');
        $privilege = $this->createMock(Privileges::class);

        $classe = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);
        $commission = $this->createStub(Commission::class);
        $commune = $this->createStub(Commune::class);
        $groupement = $this->createMock(Groupement::class);
        $groupement->method('getCommunes')->willReturn(new ArrayCollection([$commune]));
        $user = $this->createMockUserWithPrivileges($privilege, [$groupement], $commune);
        $user->method('getCommissions')->willReturn(new ArrayCollection([$commission]));

        $subject = $this->createMockEtablissement($classe, [$commune], $commission);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessDeniedIfCommissionNotMatching(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_igh_3_1_0_0');
        $privilege = $this->createMock(Privileges::class);

        $classe = $this->createConfiguredMock(IghClasse::class, ['getId' => 3]);
        $commission = $this->createStub(Commission::class);
        $user = $this->createMockUserWithPrivileges($privilege);
        $user->method('getCommissions')->willReturn(new ArrayCollection());

        $subject = $this->createMockEtablissement($classe, [], $commission);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }
}

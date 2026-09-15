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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementIghVoterTest extends TestCase
{
    private function createMockUserWithPrivileges(Privileges $privilege, array $groupements = [], ?Commune $commune = null): Utilisateur
    {
        $userPrivileges = $this->createStub(Collection::class);
        $userPrivileges->method('contains')->willReturnCallback(static fn (mixed $candidate): bool => $candidate === $privilege);

        return $this->createConfiguredStub(Utilisateur::class, [
            'getGroupe' => $this->createConfiguredStub(Groupe::class, ['getPrivileges' => $userPrivileges]),
            'getGroupements' => new ArrayCollection($groupements),
            'getCommune' => $commune,
        ]);
    }

    private function createMockEtablissement(?IghClasse $classe, array $communes = [], ?Commission $commission = null): Etablissement
    {
        $adresses = array_map(
            fn ($commune): EtablissementAdresse => $this->createConfiguredStub(EtablissementAdresse::class, ['getCommune' => $commune]),
            $communes
        );
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, [
            'getClasse' => $classe,
            'getGenre' => $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::IGH]),
            'getCommission' => $commission,
        ]);

        return $this->createConfiguredStub(Etablissement::class, [
            'getLatestInformations' => $latestInformations,
            'getAdresses' => new ArrayCollection($adresses),
        ]);
    }

    private function createVoter(array $resources, Privileges $privilege): EtablissementIghVoter
    {
        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissementIgh')->willReturn($resources);

        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        return new EtablissementIghVoter($resourcesRepository, $privilegesRepository, new VoterUtils());
    }

    public function testAccessAllClassesRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_0_0_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $classe = null;
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($classe);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessSpecificClasseRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_3_0_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $classe = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($classe);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessMultipleClassesRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_2-3_0_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $classe2 = $this->createConfiguredStub(IghClasse::class, ['getId' => 2]);
        $classe3 = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subject2 = $this->createMockEtablissement($classe2);
        $subject3 = $this->createMockEtablissement($classe3);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject2, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject3, [EtablissementVoter::LECTURE]));
    }

    public function testAccessDeniedIfNoMatchingClasse(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_2_0_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $classe = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($classe);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([], $privilege);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithGroupementRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_3_0_1_0']);
        $privilege = $this->createStub(Privileges::class);

        $classe = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);
        $commune = $this->createStub(Commune::class);
        $groupement = $this->createConfiguredStub(Groupement::class, ['getCommunes' => new ArrayCollection([$commune])]);
        $user = $this->createMockUserWithPrivileges($privilege, [$groupement]);
        $subject = $this->createMockEtablissement($classe, [$commune]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithCommuneRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_3_0_0_1']);
        $privilege = $this->createStub(Privileges::class);

        $classe = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);
        $commune = $this->createStub(Commune::class);
        $user = $this->createMockUserWithPrivileges($privilege, [], $commune);
        $subject = $this->createMockEtablissement($classe, [$commune]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithGroupementAndCommuneRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_3_0_1_1']);
        $privilege = $this->createStub(Privileges::class);

        $classe = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);
        $commune = $this->createStub(Commune::class);
        $groupement = $this->createConfiguredStub(Groupement::class, ['getCommunes' => new ArrayCollection([$commune])]);
        $user = $this->createMockUserWithPrivileges($privilege, [$groupement], $commune);
        $subject = $this->createMockEtablissement($classe, [$commune]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessDeniedIfGroupementOrCommuneNotMatching(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_3_0_1_1']);
        $privilege = $this->createStub(Privileges::class);

        $classe = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);
        $commune = $this->createStub(Commune::class);
        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement($classe, [$commune]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithCommissionRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_3_1_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $classe = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);
        $commission = $this->createStub(Commission::class);
        $user = $this->createMockUserWithPrivileges($privilege);
        $user->method('getCommissions')->willReturn(new ArrayCollection([$commission]));

        $subject = $this->createMockEtablissement($classe, [], $commission);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessClasseWithCommissionGroupementCommuneRuleGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_3_1_1_1']);
        $privilege = $this->createStub(Privileges::class);

        $classe = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);
        $commission = $this->createStub(Commission::class);
        $commune = $this->createStub(Commune::class);
        $groupement = $this->createConfiguredStub(Groupement::class, ['getCommunes' => new ArrayCollection([$commune])]);
        $user = $this->createMockUserWithPrivileges($privilege, [$groupement], $commune);
        $user->method('getCommissions')->willReturn(new ArrayCollection([$commission]));

        $subject = $this->createMockEtablissement($classe, [$commune], $commission);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessDeniedIfCommissionNotMatching(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_igh_3_1_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $classe = $this->createConfiguredStub(IghClasse::class, ['getId' => 3]);
        $commission = $this->createStub(Commission::class);
        $user = $this->createMockUserWithPrivileges($privilege);
        $user->method('getCommissions')->willReturn(new ArrayCollection());

        $subject = $this->createMockEtablissement($classe, [], $commission);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }
}

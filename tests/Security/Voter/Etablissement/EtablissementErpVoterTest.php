<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\Etablissement;

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
use App\Security\Voter\Etablissement\EtablissementErpVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\Voter\VoterUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementErpVoterTest extends TestCase
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

    private function createMockEtablissement(int $typeActivite, int $categorie, array $communes = []): Etablissement
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::ERP]);
        $typeActivite = $this->createConfiguredStub(EtablissementActivite::class, ['getId' => $typeActivite]);
        $categorie = $this->createConfiguredStub(EtablissementCategorie::class, ['getId' => $categorie]);
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, [
            'getGenre' => $genre,
            'getTypeActivite' => $typeActivite,
            'getCategorie' => $categorie,
        ]);
        $adresses = array_map(
            fn ($commune): EtablissementAdresse => $this->createConfiguredStub(EtablissementAdresse::class, ['getCommune' => $commune]),
            $communes
        );

        return $this->createConfiguredStub(Etablissement::class, [
            'getLatestInformations' => $latestInformations,
            'getAdresses' => new ArrayCollection($adresses),
        ]);
    }

    private function createVoter(array $resources, Privileges $privilege): EtablissementErpVoter
    {
        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissementErp')->willReturn($resources);

        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        return new EtablissementErpVoter($resourcesRepository, $privilegesRepository, new VoterUtils());
    }

    public function testAccessSingleCategoryGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_erp_1_2_0_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subject = $this->createMockEtablissement(1, 2);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }

    public function testAccessMultipleCategoriesGrantsAccess(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_erp_1_2-3_0_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subjectCat2 = $this->createMockEtablissement(1, 2);
        $subjectCat3 = $this->createMockEtablissement(1, 3);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCat2, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCat3, [EtablissementVoter::LECTURE]));
    }

    public function testAccessWithGroupementsGrantsAccessOnlyForMatchingGroupements(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_erp_1_2_0_1_0']);
        $privilege = $this->createStub(Privileges::class);

        $commune10 = $this->createConfiguredStub(Commune::class, ['getId' => '100']);
        $commune30 = $this->createConfiguredStub(Commune::class, ['getId' => '300']);
        $groupement10 = $this->createConfiguredStub(Groupement::class, ['getCommunes' => new ArrayCollection([$commune10])]);

        $user = $this->createMockUserWithPrivileges($privilege, [$groupement10]);
        $subjectCommune10 = $this->createMockEtablissement(1, 2, [$commune10]);
        $subjectCommune30 = $this->createMockEtablissement(1, 2, [$commune30]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]));
    }

    public function testAccessWithCommuneGrantsAccessOnlyForMatchingCommune(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_erp_1_2_0_0_1']);
        $privilege = $this->createStub(Privileges::class);

        $commune100 = $this->createConfiguredStub(Commune::class, ['getId' => '100']);
        $commune200 = $this->createConfiguredStub(Commune::class, ['getId' => '200']);

        $user = $this->createMockUserWithPrivileges($privilege, [], $commune100);
        $subjectCommune100 = $this->createMockEtablissement(1, 2, [$commune100]);
        $subjectCommune200 = $this->createMockEtablissement(1, 2, [$commune200]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCommune100, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subjectCommune200, [EtablissementVoter::LECTURE]));
    }

    public function testAccessWithMultipleResourcesGrantsAccessIfOneIsValid(): void
    {
        $resource1 = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_erp_1_2_0_0_0']);
        $resource2 = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_erp_1_3_0_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $user = $this->createMockUserWithPrivileges($privilege);
        $subjectCat2 = $this->createMockEtablissement(1, 2);
        $subjectCat3 = $this->createMockEtablissement(1, 3);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource1, $resource2], $privilege);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCat2, [EtablissementVoter::LECTURE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subjectCat3, [EtablissementVoter::LECTURE]));
    }

    public function testAccessDeniedIfNoResourceIsValid(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_erp_1_2_0_0_0']);
        $privilege = $this->createStub(Privileges::class);

        $userPrivileges = $this->createStub(Collection::class);
        $userPrivileges->method('contains')->willReturn(false);
        $groupe = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => $userPrivileges]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $groupe]);

        $subject = $this->createMockEtablissement(1, 2);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = $this->createVoter([$resource], $privilege);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [EtablissementVoter::LECTURE]));
    }
}

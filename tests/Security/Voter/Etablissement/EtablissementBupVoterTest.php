<?php

namespace App\Tests\Security\Voter\Etablissement;

use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Groupement;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\EtablissementBupVoter;
use App\Security\Voter\EtablissementVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementBupVoterTest extends TestCase
{
    private $resourcesRepository;
    private $privilegesRepository;
    private $voter;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createMock(ResourcesRepository::class);
        $this->privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $this->voter = new EtablissementBupVoter($this->resourcesRepository, $this->privilegesRepository);
    }

    private function createEtablissementMock(): Etablissement
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 6]);
        $latestInformations = $this->createConfiguredMock(EtablissementInformations::class, ['getGenre' => $genre]);
        return $this->createConfiguredMock(Etablissement::class, ['getLatestInformations' => $latestInformations]);
    }

    public function testUserWithPrivilegeOnGeneralBupRuleHasAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_bup_0_0');
        $this->resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([$resource]);
        $privilege = new Privileges();
        $groupe = $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $groupe]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock();
        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testUserWithoutPrivilegeOnGeneralBupRuleIsDeniedAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_bup_0_0');
        $this->resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([$resource]);
        $groupe = $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection()]);
        $user = $this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $groupe]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock();
        $this->privilegesRepository->method('findOneBy')->willReturn(null);
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testUserWithSpecificGroupementAndCommunePrivilegesHasAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_bup_1_1');
        $this->resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([$resource]);
        $privilege = new Privileges();
        $groupe = $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $userGroupement = $this->createMock(Groupement::class);
        $commune = $this->createMock(Commune::class);
        $userGroupement->method('getCommunes')->willReturn(new ArrayCollection([$commune]));
        $user = $this->createConfiguredMock(Utilisateur::class, [
            'getGroupe' => $groupe,
            'getGroupements' => new ArrayCollection([$userGroupement]),
            'getCommune' => $commune
        ]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissementAdresse = $this->createMock(EtablissementAdresse::class);
        $etablissementAdresse->method('getCommune')->willReturn($commune);
        $etablissement = $this->createEtablissementMock();
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([$etablissementAdresse]));
        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testUserWithoutMatchingGroupementOrCommunePrivilegesIsDeniedAccess(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_bup_1_1');
        $this->resourcesRepository->method('findRessourcesSpecialiseesEtablissement')->willReturn([$resource]);
        $privilege = new Privileges();
        $groupe = $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $userGroupement = $this->createMock(Groupement::class);
        $userGroupement->method('getCommunes')->willReturn(new ArrayCollection());
        $user = $this->createConfiguredMock(Utilisateur::class, [
            'getGroupe' => $groupe,
            'getGroupements' => new ArrayCollection([$userGroupement]),
            'getCommune' => $this->createMock(Commune::class)
        ]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissementAdresse = $this->createMock(EtablissementAdresse::class);
        $etablissementAdresse->method('getCommune')->willReturn($this->createMock(Commune::class));
        $etablissement = $this->createEtablissementMock();
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([$etablissementAdresse]));
        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }
}

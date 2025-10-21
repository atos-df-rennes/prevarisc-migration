<?php

namespace App\Tests\Security\Voter;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Entity\Groupe;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\DossierVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DossierVoterTest extends TestCase
{
    private $resourcesRepository;
    private $privilegesRepository;
    private $voter;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createMock(ResourcesRepository::class);
        $this->privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $this->voter = new DossierVoter($this->resourcesRepository, $this->privilegesRepository);
    }

    public function testVoteReturnsAccessAbstainForUnsupportedAttribute()
    {
        $dossier = $this->createMock(Dossier::class);
        $token = $this->createMock(TokenInterface::class);

        $result = $this->voter->vote($token, $dossier, ['unsupported_attribute']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteReturnsAccessAbstainWhenNoSpecializedResourcesExist()
    {
        $dossier = $this->createMock(Dossier::class);
        $this->resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([]);

        $token = $this->createMock(TokenInterface::class);

        $result = $this->voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteReturnsAccessDeniedForNonUtilisateurToken()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $dossier = $this->createMock(Dossier::class);
        $this->resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn(['resource_1']);

        $result = $this->voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteReturnsAccessDeniedWhenDossierHasNoNature()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);
        $this->resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn(['resource_1']);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getNature')->willReturn(null);

        $result = $this->voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteReturnsAccessGrantedWhenUserHasPrivilege()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(1);
        $dossier->method('getNature')->willReturn($nature);

        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('resource_1');
        $this->resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([$resource]);

        $privilege = $this->createMock(Privileges::class);
        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);

        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([$privilege]));
        $user->method('getGroupe')->willReturn($group);

        $result = $this->voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteReturnsAccessDeniedWhenUserHasPrivilegeForOtherNature()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        // Dossier avec une nature différente de celle du privilège
        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(2); // Nature différente
        $dossier->method('getNature')->willReturn($nature);

        // Simuler que le privilège est pour une autre nature (ex: id=1)
        $privilege = $this->createMock(Privileges::class);
        $resourcePrivilege = $this->createMock(Resources::class);
        $resourcePrivilege->method('getName')->willReturn('resource_1');
        $privilege->method('getResource')->willReturn($resourcePrivilege);

        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);

        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([$privilege]));
        $user->method('getGroupe')->willReturn($group);

        $this->resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([$resourcePrivilege]);

        $result = $this->voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteReturnsAccessGrantedWhenUserHasPrivilegeForAllNatures()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        // Dossier avec une nature quelconque (ex: id=5)
        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(5);
        $dossier->method('getNature')->willReturn($nature);

        // Privilège pour toutes les natures (id=0)
        $privilege = $this->createMock(Privileges::class);
        $resourcePrivilege = $this->createMock(Resources::class);
        $resourcePrivilege->method('getName')->willReturn('resource_0');
        $privilege->method('getResource')->willReturn($resourcePrivilege);

        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);

        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([$privilege]));
        $user->method('getGroupe')->willReturn($group);

        $this->resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([$resourcePrivilege]);

        $result = $this->voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteReturnsAccessGrantedWhenUserHasPrivilegeForSpecificNatureEvenIfAllNaturesDenied()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        // Dossier avec une nature spécifique (ex: id=3)
        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(3);
        $dossier->method('getNature')->willReturn($nature);

        // Ressource "toutes natures" (id=0) pour laquelle l'utilisateur n'a PAS le privilège
        $resourceAll = $this->createMock(Resources::class);
        $resourceAll->method('getName')->willReturn('resource_0');
        $privilegeAll = $this->createMock(Privileges::class);
        $privilegeAll->method('getResource')->willReturn($resourceAll);

        // Ressource spécifique (id=3) pour laquelle l'utilisateur a le privilège
        $resourceSpecific = $this->createMock(Resources::class);
        $resourceSpecific->method('getName')->willReturn('resource_3');
        $privilegeSpecific = $this->createMock(Privileges::class);
        $privilegeSpecific->method('getResource')->willReturn($resourceSpecific);

        // Le repository retourne les deux ressources
        $this->resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([$resourceAll, $resourceSpecific]);

        // Le repository de privilèges retourne le privilège spécifique pour la nature du dossier
        $this->privilegesRepository->method('findOneBy')->willReturnCallback(function ($criteria) use ($privilegeSpecific, $resourceSpecific) {
            if ($criteria['resource'] === $resourceSpecific) {
                return $privilegeSpecific;
            }
            return null;
        });

        // L'utilisateur n'a pas le privilège "toutes natures", mais a le privilège spécifique
        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([$privilegeSpecific]));
        $user->method('getGroupe')->willReturn($group);

        $result = $this->voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteIgnoresResourceWithMultipleNatures()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(1);
        $dossier->method('getNature')->willReturn($nature);

        // Le repository ne retourne que des ressources avec plusieurs natures (ce qui ne devrait plus arriver)
        $resourceMultiple = $this->createMock(Resources::class);
        $resourceMultiple->method('getName')->willReturn('resource_1-2');
        $this->resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([$resourceMultiple]);

        // Le groupe utilisateur n'a aucun privilège pertinent
        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([]));
        $user->method('getGroupe')->willReturn($group);

        // Le repository de privilèges retourne null (aucun privilège trouvé)
        $this->privilegesRepository->method('findOneBy')->willReturn(null);

        $result = $this->voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        // La règle doit être ignorée, donc accès refusé
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}

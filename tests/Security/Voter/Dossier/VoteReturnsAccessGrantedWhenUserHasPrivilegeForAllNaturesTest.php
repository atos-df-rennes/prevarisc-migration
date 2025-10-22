<?php

namespace App\Tests\Security\Voter\Dossier;

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

class VoteReturnsAccessGrantedWhenUserHasPrivilegeForAllNaturesTest extends TestCase
{
    public function testVoteReturnsAccessGrantedWhenUserHasPrivilegeForAllNatures()
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository);

        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(5);
        $dossier->method('getNature')->willReturn($nature);

        $privilege = $this->createMock(Privileges::class);
        $resourcePrivilege = $this->createMock(Resources::class);
        $resourcePrivilege->method('getName')->willReturn('resource_0');
        $privilege->method('getResource')->willReturn($resourcePrivilege);

        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([$privilege]));
        $user->method('getGroupe')->willReturn($group);

        $resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([$resourcePrivilege]);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}

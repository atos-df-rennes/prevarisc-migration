<?php

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\DossierVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoteReturnsAccessGrantedWhenUserHasPrivilegeTest extends TestCase
{
    public function testVoteReturnsAccessGrantedWhenUserHasPrivilege(): void
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository);

        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(1);
        $dossier->method('getNature')->willReturn($nature);

        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('resource_1');
        $resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([$resource]);

        $privilege = $this->createMock(Privileges::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([$privilege]));
        $user->method('getGroupe')->willReturn($group);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}

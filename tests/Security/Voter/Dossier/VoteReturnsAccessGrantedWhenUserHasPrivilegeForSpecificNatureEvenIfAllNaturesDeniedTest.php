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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoteReturnsAccessGrantedWhenUserHasPrivilegeForSpecificNatureEvenIfAllNaturesDeniedTest extends TestCase
{
    public function testVoteReturnsAccessGrantedWhenUserHasPrivilegeForSpecificNatureEvenIfAllNaturesDenied(): void
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository);

        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(3);
        $dossier->method('getNature')->willReturn($nature);

        $resourceAll = $this->createMock(Resources::class);
        $resourceAll->method('getName')->willReturn('resource_0');
        $privilegeAll = $this->createMock(Privileges::class);
        $privilegeAll->method('getResource')->willReturn($resourceAll);

        $resourceSpecific = $this->createMock(Resources::class);
        $resourceSpecific->method('getName')->willReturn('resource_3');
        $privilegeSpecific = $this->createMock(Privileges::class);
        $privilegeSpecific->method('getResource')->willReturn($resourceSpecific);

        $resourcesRepository->method('findRessourcesSpecialiseesSimplesDossier')->willReturn([$resourceAll, $resourceSpecific]);

        $privilegesRepository->method('findOneBy')->willReturnCallback(function ($criteria) use ($privilegeSpecific, $resourceSpecific): ?MockObject {
            if ($criteria['resource'] === $resourceSpecific) {
                return $privilegeSpecific;
            }

            return null;
        });

        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([$privilegeSpecific]));
        $user->method('getGroupe')->willReturn($group);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}

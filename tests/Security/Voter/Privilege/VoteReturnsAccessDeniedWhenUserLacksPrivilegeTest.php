<?php

namespace App\Tests\Security\Voter\Privilege;

use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Security\Voter\PrivilegeVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoteReturnsAccessDeniedWhenUserLacksPrivilegeTest extends TestCase
{
    public function testVoteReturnsAccessDeniedWhenUserDoesNotHavePrivilege(): void
    {
        $privilege = $this->createMock(Privileges::class);
        $privilege->method('getName')->willReturn('mon_privilege');

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findAll')->willReturn([$privilege]);

        $voter = new PrivilegeVoter($privilegesRepository);

        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([]));

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($group);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $voter->vote($token, null, ['mon_privilege']);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}

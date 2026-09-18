<?php

declare(strict_types=1);

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

final class VoteReturnsAccessDeniedWhenUserLacksPrivilegeTest extends TestCase
{
    public function testVoteReturnsAccessDeniedWhenUserDoesNotHavePrivilege(): void
    {
        $privilege = $this->createStub(Privileges::class);
        $privilege->method('getName')->willReturn('mon_privilege');

        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $privilegesRepository->method('findAll')->willReturn([$privilege]);

        $voter = new PrivilegeVoter($privilegesRepository);

        $group = $this->createStub(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([]));

        $user = $this->createStub(Utilisateur::class);
        $user->method('getGroupe')->willReturn($group);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $voter->vote($token, null, ['mon_privilege']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}

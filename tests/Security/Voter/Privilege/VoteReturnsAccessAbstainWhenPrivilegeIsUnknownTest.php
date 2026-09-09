<?php

namespace App\Tests\Security\Voter\Privilege;

use App\Repository\PrivilegesRepository;
use App\Security\Voter\PrivilegeVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoteReturnsAccessAbstainWhenPrivilegeIsUnknownTest extends TestCase
{
    public function testVoteReturnsAccessAbstainWhenPrivilegeIsUnknown(): void
    {
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findAll')->willReturn([]);

        $voter = new PrivilegeVoter($privilegesRepository);
        $token = $this->createStub(TokenInterface::class);

        $result = $voter->vote($token, null, ['privilege_inexistant']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}

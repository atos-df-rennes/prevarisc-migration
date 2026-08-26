<?php

namespace App\Tests\Security\Voter\Sdis29;

use App\Security\Voter\Sdis29\Sdis29Voter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoteReturnsAccessDeniedWhenSdis29CustomDisabledTest extends TestCase
{
    public function testVoteReturnsAccessDeniedWhenSdis29CustomDisabled(): void
    {
        $voter = new Sdis29Voter(false);

        $token = $this->createMock(TokenInterface::class);

        $result = $voter->vote($token, null, [Sdis29Voter::ENABLED]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}

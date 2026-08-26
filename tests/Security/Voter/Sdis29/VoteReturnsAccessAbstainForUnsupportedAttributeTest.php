<?php

namespace App\Tests\Security\Voter\Sdis29;

use App\Security\Voter\Sdis29\Sdis29Voter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoteReturnsAccessAbstainForUnsupportedAttributeTest extends TestCase
{
    public function testVoteReturnsAccessAbstainForUnsupportedAttribute(): void
    {
        $voter = new Sdis29Voter(true);

        $token = $this->createMock(TokenInterface::class);

        $result = $voter->vote($token, null, ['some_other_attribute']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}

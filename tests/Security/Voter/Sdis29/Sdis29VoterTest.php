<?php

namespace App\Tests\Security\Voter\Sdis29;

use App\Security\Voter\Sdis29\Sdis29Voter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Teste le garde-fou d'activation des portages SDIS29 (cf. app.sdis29_custom_enabled).
 */
final class Sdis29VoterTest extends TestCase
{
    public function testVoteReturnsAccessGrantedWhenSdis29CustomEnabled(): void
    {
        $voter = new Sdis29Voter(true);

        $result = $voter->vote($this->createMock(TokenInterface::class), null, [Sdis29Voter::ENABLED]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteReturnsAccessDeniedWhenSdis29CustomDisabled(): void
    {
        $voter = new Sdis29Voter(false);

        $result = $voter->vote($this->createMock(TokenInterface::class), null, [Sdis29Voter::ENABLED]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteReturnsAccessAbstainForUnsupportedAttribute(): void
    {
        $voter = new Sdis29Voter(true);

        $result = $voter->vote($this->createMock(TokenInterface::class), null, ['some_other_attribute']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}

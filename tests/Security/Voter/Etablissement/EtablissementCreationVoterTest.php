<?php

namespace App\Tests\Security\Voter\Etablissement;

use App\Entity\Groupe;
use App\Entity\Utilisateur;
use App\Security\Voter\Etablissement\EtablissementCreationVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Security\Voter\Etablissement\EtablissementCreationVoter
 */
class EtablissementCreationVoterTest extends TestCase
{
    /** @var EtablissementCreationVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->voter = new EtablissementCreationVoter();
    }

    /**
     * Crée un token pour un utilisateur authentifié avec un groupe.
     *
     * @return TokenInterface
     */
    private function createAuthenticatedToken(): TokenInterface
    {
        $groupe = $this->createMock(Groupe::class);
        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    /**
     * Crée un token pour un utilisateur anonyme (pas d'objet utilisateur).
     *
     * @return TokenInterface
     */
    private function createAnonymousToken(): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        return $token;
    }

    /**
     * Crée un token pour un utilisateur authentifié sans groupe.
     *
     * @return TokenInterface
     */
    private function createAuthenticatedTokenWithoutGroup(): TokenInterface
    {
        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn(null);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    /**
     * Un utilisateur authentifié avec un groupe DOIT pouvoir créer un établissement.
     */
    public function testGrantedForAuthenticatedUserWithGroup(): void
    {
        $token = $this->createAuthenticatedToken();

        $result = $this->voter->vote($token, null, [EtablissementVoter::CREATE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Un utilisateur anonyme NE DOIT PAS pouvoir créer un établissement.
     */
    public function testDeniedForAnonymousUser(): void
    {
        $token = $this->createAnonymousToken();

        $result = $this->voter->vote($token, null, [EtablissementVoter::CREATE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * Un utilisateur authentifié sans groupe NE DOIT PAS pouvoir créer un établissement.
     */
    public function testDeniedForAuthenticatedUserWithoutGroup(): void
    {
        $token = $this->createAuthenticatedTokenWithoutGroup();

        $result = $this->voter->vote($token, null, [EtablissementVoter::CREATE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * Le voter NE DOIT PAS s'appliquer à un attribut différent de CREATE.
     */
    public function testAbstainsForUnrelatedAttribute(): void
    {
        $token = $this->createAuthenticatedToken();

        $result = $this->voter->vote($token, null, ['view_ets']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    /**
     * Le voter NE DOIT PAS s'appliquer lorsqu'un sujet non-null est fourni avec CREATE.
     * (les voters genre-spécifiques prennent en charge ce cas).
     */
    public function testAbstainsForCreateWithNonNullSubject(): void
    {
        $token = $this->createAuthenticatedToken();
        $etablissement = new \stdClass();

        $result = $this->voter->vote($token, $etablissement, [EtablissementVoter::CREATE]);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    /**
     * La constante CREATE doit avoir la valeur attendue.
     */
    public function testCreateConstantValue(): void
    {
        $this->assertSame('create_ets', EtablissementVoter::CREATE);
    }
}

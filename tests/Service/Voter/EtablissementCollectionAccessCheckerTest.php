<?php

namespace App\Tests\Service\Voter;

use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Service\Voter\EtablissementCollectionAccessChecker;
use App\Service\Voter\EtablissementVoterFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementCollectionAccessCheckerTest extends TestCase
{
    /** @var MockObject&EtablissementVoterFactory */
    private $factory;

    /** @var MockObject&LoggerInterface */
    private $logger;

    /** @var EtablissementCollectionAccessChecker */
    private $checker;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(EtablissementVoterFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->checker = new EtablissementCollectionAccessChecker($this->factory, $this->logger);
    }

    /**
     * Crée un mock d'établissement avec un genre donné.
     *
     * @return MockObject&Etablissement
     */
    private function createMockEtablissement(int $genreId): MockObject
    {
        $genre = $this->createMock(Genre::class);
        $genre->method('getId')->willReturn($genreId);

        $informations = $this->createMock(EtablissementInformations::class);
        $informations->method('getGenre')->willReturn($genre);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);

        return $etablissement;
    }

    /**
     * Test : collection vide → false (aucun établissement accessible).
     */
    public function testCollectionVideRetourneFalse(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $result = $this->checker->hasAtLeastOneAccessible([], 'view_ets', $token);

        $this->assertFalse($result);
    }

    /**
     * Test : un seul établissement, voter accorde → true.
     */
    public function testUnEtablissementAccessibleRetourneTrue(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $etablissement = $this->createMockEtablissement(2);

        $voter = $this->createMock(VoterInterface::class);
        $voter->method('vote')->willReturn(VoterInterface::ACCESS_GRANTED);

        $this->factory->method('getVoterForGenre')->with(2)->willReturn($voter);

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertTrue($result);
    }

    /**
     * Test : un seul établissement, voter refuse → false.
     */
    public function testUnEtablissementNonAccessibleRetourneFalse(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $etablissement = $this->createMockEtablissement(2);

        $voter = $this->createMock(VoterInterface::class);
        $voter->method('vote')->willReturn(VoterInterface::ACCESS_DENIED);

        $this->factory->method('getVoterForGenre')->with(2)->willReturn($voter);

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertFalse($result);
    }

    /**
     * Test : deux établissements, premier refusé, deuxième accordé → true (au moins un accessible).
     */
    public function testDeuxEtablissementsAuMoinsUnAccessibleRetourneTrue(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $ets1 = $this->createMockEtablissement(2);
        $ets2 = $this->createMockEtablissement(3);

        $denyingVoter = $this->createMock(VoterInterface::class);
        $denyingVoter->method('vote')->willReturn(VoterInterface::ACCESS_DENIED);

        $grantingVoter = $this->createMock(VoterInterface::class);
        $grantingVoter->method('vote')->willReturn(VoterInterface::ACCESS_GRANTED);

        $this->factory->method('getVoterForGenre')->willReturnMap([
            [2, $denyingVoter],
            [3, $grantingVoter],
        ]);

        $result = $this->checker->hasAtLeastOneAccessible([$ets1, $ets2], 'view_ets', $token);

        $this->assertTrue($result);
    }

    /**
     * Test : établissement sans voter disponible → ignoré (log warning), résultat false si aucun autre.
     */
    public function testEtablissementSansVoterEstIgnoreAvecWarning(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $etablissement = $this->createMockEtablissement(999);

        $this->factory->method('getVoterForGenre')->with(999)->willReturn(null);
        $this->logger->expects($this->once())->method('warning');

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertFalse($result);
    }

    /**
     * Test : établissement sans informations (OutOfBoundsException) → ignoré avec log warning.
     */
    public function testEtablissementSansInformationsEstIgnoreAvecWarning(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willThrowException(new \OutOfBoundsException('No informations'));

        $this->logger->expects($this->once())->method('warning');
        $this->factory->expects($this->never())->method('getVoterForGenre');

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertFalse($result);
    }
}

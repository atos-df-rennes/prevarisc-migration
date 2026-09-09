<?php

declare(strict_types=1);

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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementCollectionAccessCheckerTest extends TestCase
{
    /** @var MockObject&EtablissementVoterFactory */
    private MockObject $factory;

    /** @var MockObject&LoggerInterface */
    private MockObject $logger;

    /** @var MockObject&AuthorizationCheckerInterface */
    private MockObject $authorizationChecker;

    private EtablissementCollectionAccessChecker $checker;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(EtablissementVoterFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->checker = new EtablissementCollectionAccessChecker($this->factory, $this->logger, $this->authorizationChecker);
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
        $token = $this->createStub(TokenInterface::class);

        $result = $this->checker->hasAtLeastOneAccessible([], 'view_ets', $token);

        $this->assertFalse($result);
    }

    /**
     * Test : un seul établissement, voter accorde → true.
     */
    public function testUnEtablissementAccessibleRetourneTrue(): void
    {
        $token = $this->createStub(TokenInterface::class);
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
        $token = $this->createStub(TokenInterface::class);
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
        $token = $this->createStub(TokenInterface::class);
        $ets1 = $this->createMockEtablissement(2);
        $ets2 = $this->createMockEtablissement(3);

        $denyingVoter = $this->createMock(VoterInterface::class);
        $denyingVoter->method('vote')->willReturn(VoterInterface::ACCESS_DENIED);

        $grantingVoter = $this->createMock(VoterInterface::class);
        $grantingVoter->method('vote')->willReturn(VoterInterface::ACCESS_GRANTED);

        $this->factory->expects($this->atLeast(2))->method('getVoterForGenre')->willReturnMap([
            [2, $denyingVoter],
            [3, $grantingVoter],
        ]);

        $result = $this->checker->hasAtLeastOneAccessible([$ets1, $ets2], 'view_ets', $token);

        $this->assertTrue($result);
    }

    /**
     * Test : établissement sans voter dédié dans la factory (ex. genre inconnu) → délégation à
     * l'authorization checker ; si celui-ci refuse également, résultat false.
     */
    public function testEtablissementSansVoterDedieDelegueAAuthorizationCheckerEtRefuse(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $etablissement = $this->createMockEtablissement(999);

        $this->factory->method('getVoterForGenre')->with(999)->willReturn(null);
        $this->authorizationChecker->method('isGranted')->with('view_ets', $etablissement)->willReturn(false);

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertFalse($result);
    }

    /**
     * Test : établissement de genre Site (1), qui n'a pas de voter dédié dans la factory
     * (pour éviter une dépendance circulaire) → délégation à l'authorization checker qui
     * invoque EtablissementSiteVoter via la chaîne de voters Symfony ; si celui-ci accorde
     * l'accès, résultat true. Couvre la régression d'accès au dossier d'un site (genre 1).
     */
    public function testEtablissementGenreSiteDelegueAAuthorizationCheckerEtAccorde(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $etablissement = $this->createMockEtablissement(1);

        $this->factory->method('getVoterForGenre')->with(1)->willReturn(null);
        $this->authorizationChecker->method('isGranted')->with('view_ets', $etablissement)->willReturn(true);

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertTrue($result);
    }

    /**
     * Test : établissement sans informations (OutOfBoundsException) → ignoré avec log warning.
     */
    public function testEtablissementSansInformationsEstIgnoreAvecWarning(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willThrowException(new \OutOfBoundsException('No informations'));

        $this->logger->expects($this->once())->method('warning');
        $this->factory->expects($this->never())->method('getVoterForGenre');

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertFalse($result);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Service\Voter;

use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Service\Voter\EtablissementCollectionAccessChecker;
use App\Service\Voter\EtablissementVoterFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementCollectionAccessCheckerTest extends TestCase
{
    private EtablissementVoterFactory $factory;

    private LoggerInterface $logger;

    private AuthorizationCheckerInterface $authorizationChecker;

    private EtablissementCollectionAccessChecker $checker;

    protected function setUp(): void
    {
        $this->recreateChecker(
            $this->createStub(EtablissementVoterFactory::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(AuthorizationCheckerInterface::class)
        );
    }

    /**
     * Crée un stub d'établissement avec un genre donné.
     */
    private function createEtablissementStub(int $genreId): Etablissement
    {
        $genre = $this->createStub(Genre::class);
        $genre->method('getId')->willReturn($genreId);

        $informations = $this->createStub(EtablissementInformations::class);
        $informations->method('getGenre')->willReturn($genre);

        $etablissement = $this->createStub(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);

        return $etablissement;
    }

    private function recreateChecker(
        EtablissementVoterFactory $factory,
        LoggerInterface $logger,
        AuthorizationCheckerInterface $authorizationChecker
    ): void {
        $this->factory = $factory;
        $this->logger = $logger;
        $this->authorizationChecker = $authorizationChecker;
        $this->checker = new EtablissementCollectionAccessChecker($this->factory, $this->logger, $this->authorizationChecker);
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
        $etablissement = $this->createEtablissementStub(2);

        $voter = $this->createStub(VoterInterface::class);
        $voter->method('vote')->willReturn(VoterInterface::ACCESS_GRANTED);

        $this->factory->method('getVoterForGenre')->willReturnCallback(function (int $genreId) use ($voter): VoterInterface {
            $this->assertSame(2, $genreId);

            return $voter;
        });

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertTrue($result);
    }

    /**
     * Test : un seul établissement, voter refuse → false.
     */
    public function testUnEtablissementNonAccessibleRetourneFalse(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $etablissement = $this->createEtablissementStub(2);

        $voter = $this->createStub(VoterInterface::class);
        $voter->method('vote')->willReturn(VoterInterface::ACCESS_DENIED);

        $this->factory->method('getVoterForGenre')->willReturnCallback(function (int $genreId) use ($voter): VoterInterface {
            $this->assertSame(2, $genreId);

            return $voter;
        });

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertFalse($result);
    }

    /**
     * Test : deux établissements, premier refusé, deuxième accordé → true (au moins un accessible).
     */
    public function testDeuxEtablissementsAuMoinsUnAccessibleRetourneTrue(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $ets1 = $this->createEtablissementStub(2);
        $ets2 = $this->createEtablissementStub(3);

        $denyingVoter = $this->createStub(VoterInterface::class);
        $denyingVoter->method('vote')->willReturn(VoterInterface::ACCESS_DENIED);

        $grantingVoter = $this->createStub(VoterInterface::class);
        $grantingVoter->method('vote')->willReturn(VoterInterface::ACCESS_GRANTED);

        $this->factory->method('getVoterForGenre')->willReturnMap([
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
        $etablissement = $this->createEtablissementStub(999);

        $this->factory->method('getVoterForGenre')->willReturnCallback(function (int $genreId): ?VoterInterface {
            $this->assertSame(999, $genreId);

            return null;
        });
        $this->authorizationChecker->method('isGranted')->willReturnCallback(function (string $attribute, Etablissement $subject) use ($etablissement): bool {
            $this->assertSame('view_ets', $attribute);
            $this->assertSame($etablissement, $subject);

            return false;
        });

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
        $etablissement = $this->createEtablissementStub(1);

        $this->factory->method('getVoterForGenre')->willReturnCallback(function (int $genreId): ?VoterInterface {
            $this->assertSame(1, $genreId);

            return null;
        });
        $this->authorizationChecker->method('isGranted')->willReturnCallback(function (string $attribute, Etablissement $subject) use ($etablissement): bool {
            $this->assertSame('view_ets', $attribute);
            $this->assertSame($etablissement, $subject);

            return true;
        });

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertTrue($result);
    }

    /**
     * Test : établissement sans informations (OutOfBoundsException) → ignoré avec log warning.
     */
    public function testEtablissementSansInformationsEstIgnoreAvecWarning(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $this->recreateChecker($this->factory, $logger, $this->authorizationChecker);

        $etablissement = $this->createStub(Etablissement::class);
        $etablissement->method('getLatestInformations')->willThrowException(new \OutOfBoundsException('No informations'));

        $logger->expects($this->once())->method('warning');

        $result = $this->checker->hasAtLeastOneAccessible([$etablissement], 'view_ets', $token);

        $this->assertFalse($result);
    }
}

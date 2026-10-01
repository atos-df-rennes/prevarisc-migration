<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\DossierPieceJointe;

use App\Entity\Dossier;
use App\Entity\DossierPieceJointe;
use App\Entity\PieceJointe;
use App\Entity\PieceJointeStatut;
use App\Enum\PieceJointeStatutEnum;
use App\Security\Voter\DossierPieceJointeVoter;
use App\Security\Voter\DossierVoter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[AllowMockObjectsWithoutExpectations]
final class DossierPieceJointeVoterTest extends TestCase
{
    #[DataProvider('voteProvider')]
    public function testVote(string $attribute, ?PieceJointeStatutEnum $statut, bool $editDossier, int $attendu): void
    {
        $accessDecisionManager = $this->createStub(AccessDecisionManagerInterface::class);
        $accessDecisionManager->method('decide')->willReturn($editDossier);
        $voter = new DossierPieceJointeVoter($accessDecisionManager);

        $result = $voter->vote($this->createStub(TokenInterface::class), $this->createDossierPieceJointe($statut), [$attribute]);

        $this->assertSame($attendu, $result);
    }

    public static function voteProvider(): \Generator
    {
        foreach ([DossierPieceJointeVoter::MODIFICATION, DossierPieceJointeVoter::SUPPRESSION] as $attribute) {
            yield $attribute.' sans statut' => [$attribute, null, true, VoterInterface::ACCESS_GRANTED];
            yield $attribute.' sans droit sur le dossier' => [$attribute, PieceJointeStatutEnum::NonEnvoye, false, VoterInterface::ACCESS_DENIED];

            foreach (PieceJointeStatutEnum::cases() as $statut) {
                $attendu = $statut->bloqueModification() ? VoterInterface::ACCESS_DENIED : VoterInterface::ACCESS_GRANTED;
                yield $attribute.' '.$statut->value => [$attribute, $statut, true, $attendu];
            }
        }
    }

    public function testVoteAbstientPourAttributOuSujetNonSupporte(): void
    {
        $voter = new DossierPieceJointeVoter($this->createStub(AccessDecisionManagerInterface::class));
        $token = $this->createStub(TokenInterface::class);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $this->createDossierPieceJointe(null), [DossierVoter::MODIFICATION]));
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, new PieceJointe(), [DossierPieceJointeVoter::MODIFICATION]));
    }

    public function testDecisionSurLeDossierEstMemoriseeParJetonEtDossier(): void
    {
        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager->expects($this->once())
            ->method('decide')
            ->with($this->anything(), [DossierVoter::MODIFICATION], $this->isInstanceOf(Dossier::class))
            ->willReturn(true);
        $voter = new DossierPieceJointeVoter($accessDecisionManager);
        $token = $this->createStub(TokenInterface::class);
        $dossier = new Dossier();

        foreach ([DossierPieceJointeVoter::MODIFICATION, DossierPieceJointeVoter::SUPPRESSION] as $attribute) {
            $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $this->createDossierPieceJointe(null, $dossier), [$attribute]));
        }
    }

    private function createDossierPieceJointe(?PieceJointeStatutEnum $statut, ?Dossier $dossier = null): DossierPieceJointe
    {
        $pieceJointe = new PieceJointe();
        if (null !== $statut) {
            $pieceJointe->setStatut(new PieceJointeStatut()->setNomStatut($statut->value));
        }

        return new DossierPieceJointe()
            ->setPieceJointe($pieceJointe)
            ->setDossier($dossier ?? new Dossier());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Entity\PieceJointe;
use App\Entity\PieceJointeStatut;
use App\Enum\PieceJointeStatutEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PieceJointeStatutEnumTest extends TestCase
{
    #[DataProvider('statutProvider')]
    public function testValeurEtBlocage(PieceJointeStatutEnum $statut, string $valeur, bool $bloque): void
    {
        $this->assertSame($valeur, $statut->value);
        $this->assertSame($bloque, $statut->bloqueModification());
    }

    public static function statutProvider(): \Generator
    {
        yield 'non envoyé' => [PieceJointeStatutEnum::NonEnvoye, 'not_exported', false];
        yield "en attente d'envoi" => [PieceJointeStatutEnum::EnAttenteEnvoi, 'to_be_exported', true];
        yield 'envoyé' => [PieceJointeStatutEnum::Envoye, 'exported', true];
        yield 'erreur' => [PieceJointeStatutEnum::Erreur, 'on_error', false];
        yield 'vérification du statut' => [PieceJointeStatutEnum::VerificationStatut, 'awaiting_status', true];
    }

    public function testSuivisExportValues(): void
    {
        $this->assertSame(['to_be_exported', 'on_error', 'awaiting_status'], PieceJointeStatutEnum::suivisExportValues());
    }

    #[DataProvider('pieceJointeStatutProvider')]
    public function testPieceJointeGetStatutEnum(?string $nomStatut, ?PieceJointeStatutEnum $attendu): void
    {
        $pieceJointe = new PieceJointe();
        if (null !== $nomStatut) {
            $pieceJointe->setStatut(new PieceJointeStatut()->setNomStatut($nomStatut));
        }

        $this->assertSame($attendu, $pieceJointe->getStatutEnum());
    }

    public static function pieceJointeStatutProvider(): \Generator
    {
        yield 'sans statut' => [null, null];
        yield 'statut inconnu' => ['inconnu', null];
        yield 'statut connu' => ['exported', PieceJointeStatutEnum::Envoye];
    }
}

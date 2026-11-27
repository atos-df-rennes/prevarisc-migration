<?php

declare(strict_types=1);

namespace App\Platau;

use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;

final readonly class PecDossiers extends ExportDossiers
{
    /** @return array{id: int, incomplete: ?bool, status: ?string, date: ?string, observations: ?string, author: array{prenomAuteur: ?string, nomAuteur: ?string, emailAuteur: ?string, telephoneAuteur: ?string}}|null */
    public function find(string $consultationId): ?array
    {
        $row = $this->db->fetchAssociative(<<<'SQL'
            SELECT d.ID_DOSSIER, d.INCOMPLET_DOSSIER, p.STATUT_PEC, p.DATE_PEC,
                   m.DOCMANQUANT
              FROM dossier d
              LEFT JOIN platauconsultation p ON p.ID_PLATAU = d.ID_PLATAU
              LEFT JOIN dossierdocmanquant m ON m.ID_DOSSIER = d.ID_DOSSIER
             WHERE d.ID_PLATAU = ? LIMIT 1
            SQL, [$consultationId]);
        if (false === $row || !is_numeric($row['ID_DOSSIER'])) {
            return null;
        }

        return [
            'id' => (int) $row['ID_DOSSIER'],
            'incomplete' => match ($row['INCOMPLET_DOSSIER']) {
                1, '1', true => true,
                0, '0', false => false,
                default => null,
            },
            'status' => is_string($row['STATUT_PEC']) ? $row['STATUT_PEC'] : null,
            'date' => is_string($row['DATE_PEC']) ? $row['DATE_PEC'] : null,
            'observations' => is_string($row['DOCMANQUANT']) ? $row['DOCMANQUANT'] : null,
            'author' => $this->author((int) $row['ID_DOSSIER']),
        ];
    }

    public function accepted(string $id): void
    {
        $this->save($id, [
            'STATUT_PEC' => PlatauStatutPecEnum::PRISE_EN_COMPTE,
            'DATE_PEC' => new \DateTimeImmutable()->format('Y-m-d'),
            'STATUT_AVIS' => PlatauStatutAvisEnum::EN_COURS,
        ]);
    }

    public function failed(string $id): void
    {
        $this->save($id, ['STATUT_PEC' => PlatauStatutPecEnum::EN_ERREUR]);
    }
}

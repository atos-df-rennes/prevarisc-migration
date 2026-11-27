<?php

declare(strict_types=1);

namespace App\Platau;

use App\Enum\PlatauStatutAvisEnum as Status;
use Symfony\Component\Clock\DatePoint;

final readonly class AvisDossiers extends ExportDossiers
{
    /** @return list<string> */
    public function retryIds(): array
    {
        /** @var list<string> $ids */
        $ids = $this->db->fetchFirstColumn(<<<'SQL'
            SELECT DISTINCT d.ID_PLATAU FROM dossier d
              JOIN platauconsultation p ON p.ID_PLATAU = d.ID_PLATAU
             WHERE p.STATUT_AVIS IN (?, ?) AND d.AVIS_DOSSIER_COMMISSION IN (1, 2, 6)
             ORDER BY d.ID_PLATAU
            SQL, [Status::A_RENVOYER, Status::EN_ERREUR]);

        return $ids;
    }

    /** @return array{id: int, opinion: ?int, status: ?string, date: ?string, author: array<string, ?string>}|null */
    public function find(string $id): ?array
    {
        $row = $this->db->fetchAssociative(<<<'SQL'
            SELECT d.ID_DOSSIER, d.AVIS_DOSSIER_COMMISSION, p.STATUT_AVIS, p.DATE_AVIS
              FROM dossier d LEFT JOIN platauconsultation p ON p.ID_PLATAU = d.ID_PLATAU
             WHERE d.ID_PLATAU = ? LIMIT 1
            SQL, [$id]);
        if (false === $row || !is_numeric($row['ID_DOSSIER'])) {
            return null;
        }
        $dossierId = (int) $row['ID_DOSSIER'];

        return [
            'id' => $dossierId,
            'opinion' => is_numeric($row['AVIS_DOSSIER_COMMISSION']) ? (int) $row['AVIS_DOSSIER_COMMISSION'] : null,
            'status' => is_string($row['STATUT_AVIS']) ? $row['STATUT_AVIS'] : null,
            'date' => is_string($row['DATE_AVIS']) ? $row['DATE_AVIS'] : null,
            'author' => $this->author($dossierId),
        ];
    }

    /** @return list<string> */
    public function prescriptions(int $dossierId): array
    {
        // Une ligne par prescription, indépendamment du nombre de références texte/article.
        /** @var list<string> $labels */
        $labels = $this->db->fetchFirstColumn(<<<'SQL'
            SELECT COALESCE(p.LIBELLE_PRESCRIPTION_DOSSIER, t.PRESCRIPTIONTYPE_LIBELLE, '')
              FROM prescriptiondossier p
              LEFT JOIN prescriptiontype t ON t.ID_PRESCRIPTIONTYPE = p.ID_PRESCRIPTION_TYPE
             WHERE p.ID_DOSSIER = ?
             ORDER BY p.TYPE_PRESCRIPTION_DOSSIER, p.NUM_PRESCRIPTION_DOSSIER, p.ID_PRESCRIPTION_DOSSIER
            SQL, [$dossierId]);

        return $labels;
    }

    public function accepted(string $id): void
    {
        $this->save($id, ['STATUT_AVIS' => Status::TRAITE, 'DATE_AVIS' => new DatePoint()->format('Y-m-d')]);
    }

    public function failed(string $id): void
    {
        $this->save($id, ['STATUT_AVIS' => Status::EN_ERREUR]);
    }
}

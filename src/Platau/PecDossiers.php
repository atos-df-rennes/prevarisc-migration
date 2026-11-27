<?php

declare(strict_types=1);

namespace App\Platau;

use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;
use Doctrine\DBAL\Connection;

final readonly class PecDossiers
{
    public function __construct(private Connection $db)
    {
    }

    /** @return array{id: int, incomplete: ?bool, status: ?string, date: ?string, observations: ?string, author: array{prenomAuteur: ?string, nomAuteur: ?string, emailAuteur: ?string, telephoneAuteur: ?string}}|null */
    public function find(string $consultationId): ?array
    {
        $row = $this->db->fetchAssociative(<<<'SQL'
            SELECT d.ID_DOSSIER, d.INCOMPLET_DOSSIER, p.STATUT_PEC, p.DATE_PEC,
                   m.DOCMANQUANT, i.PRENOM_UTILISATEURINFORMATIONS, i.NOM_UTILISATEURINFORMATIONS,
                   i.MAIL_UTILISATEURINFORMATIONS, i.TELFIXE_UTILISATEURINFORMATIONS, i.TELPORTABLE_UTILISATEURINFORMATIONS
              FROM dossier d
              LEFT JOIN platauconsultation p ON p.ID_PLATAU = d.ID_PLATAU
              LEFT JOIN dossierdocmanquant m ON m.ID_DOSSIER = d.ID_DOSSIER
              LEFT JOIN dossierpreventionniste dp ON dp.ID_DOSSIER = d.ID_DOSSIER
              LEFT JOIN utilisateur u ON u.ID_UTILISATEUR = dp.ID_PREVENTIONNISTE
              LEFT JOIN utilisateurinformations i ON i.ID_UTILISATEURINFORMATIONS = u.ID_UTILISATEURINFORMATIONS
             WHERE d.ID_PLATAU = ? LIMIT 1
            SQL, [$consultationId]);
        if (false === $row || !is_numeric($row['ID_DOSSIER'])) {
            return null;
        }

        $phone = $row['TELFIXE_UTILISATEURINFORMATIONS'];

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
            'author' => [
                'prenomAuteur' => is_string($row['PRENOM_UTILISATEURINFORMATIONS']) ? $row['PRENOM_UTILISATEURINFORMATIONS'] : null,
                'nomAuteur' => is_string($row['NOM_UTILISATEURINFORMATIONS']) ? $row['NOM_UTILISATEURINFORMATIONS'] : null,
                'emailAuteur' => is_string($row['MAIL_UTILISATEURINFORMATIONS']) ? $row['MAIL_UTILISATEURINFORMATIONS'] : null,
                'telephoneAuteur' => '' === $phone && is_string($row['TELPORTABLE_UTILISATEURINFORMATIONS']) ? $row['TELPORTABLE_UTILISATEURINFORMATIONS'] : (is_string($phone) ? $phone : null),
            ],
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

    /** @param non-empty-array<string, string> $fields */
    private function save(string $id, array $fields): void
    {
        $key = ['ID_PLATAU' => $id];
        if (false === $this->db->fetchOne('SELECT 1 FROM platauconsultation WHERE ID_PLATAU = ?', [$id])) {
            $this->db->insert('platauconsultation', $key + $fields);
        } else {
            $this->db->update('platauconsultation', $fields, $key);
        }
    }
}

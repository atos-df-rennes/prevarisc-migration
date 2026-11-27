<?php

declare(strict_types=1);

namespace App\Platau;

use Doctrine\DBAL\Connection;

abstract readonly class ExportDossiers
{
    public function __construct(protected Connection $db)
    {
    }

    /** @return array{prenomAuteur: ?string, nomAuteur: ?string, emailAuteur: ?string, telephoneAuteur: ?string} */
    protected function author(int $dossierId): array
    {
        $row = $this->db->fetchAssociative(<<<'SQL'
            SELECT i.PRENOM_UTILISATEURINFORMATIONS, i.NOM_UTILISATEURINFORMATIONS,
                   i.MAIL_UTILISATEURINFORMATIONS, i.TELFIXE_UTILISATEURINFORMATIONS, i.TELPORTABLE_UTILISATEURINFORMATIONS
              FROM dossierpreventionniste dp
              LEFT JOIN utilisateur u ON u.ID_UTILISATEUR = dp.ID_PREVENTIONNISTE
              LEFT JOIN utilisateurinformations i ON i.ID_UTILISATEURINFORMATIONS = u.ID_UTILISATEURINFORMATIONS
             WHERE dp.ID_DOSSIER = ? LIMIT 1
            SQL, [$dossierId]);
        $phone = $row['TELFIXE_UTILISATEURINFORMATIONS'] ?? null;
        if ('' === $phone && is_string($row['TELPORTABLE_UTILISATEURINFORMATIONS'] ?? null)) {
            $phone = $row['TELPORTABLE_UTILISATEURINFORMATIONS'];
        }

        return [
            'prenomAuteur' => is_string($row['PRENOM_UTILISATEURINFORMATIONS'] ?? null) ? $row['PRENOM_UTILISATEURINFORMATIONS'] : null,
            'nomAuteur' => is_string($row['NOM_UTILISATEURINFORMATIONS'] ?? null) ? $row['NOM_UTILISATEURINFORMATIONS'] : null,
            'emailAuteur' => is_string($row['MAIL_UTILISATEURINFORMATIONS'] ?? null) ? $row['MAIL_UTILISATEURINFORMATIONS'] : null,
            'telephoneAuteur' => is_string($phone) ? $phone : null,
        ];
    }

    /** @param non-empty-array<string, string> $fields */
    protected function save(string $id, array $fields): void
    {
        $key = ['ID_PLATAU' => $id];
        if (false === $this->db->fetchOne('SELECT 1 FROM platauconsultation WHERE ID_PLATAU = ?', [$id])) {
            $this->db->insert('platauconsultation', $key + $fields);
        } else {
            $this->db->update('platauconsultation', $fields, $key);
        }
    }
}

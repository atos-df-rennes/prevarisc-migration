<?php

namespace App\Enum;

/**
 * Statuts d'export Plat'AU d'une pièce jointe (table piecejointestatut, colonne NOM_STATUT).
 */
enum PieceJointeStatutEnum: string
{
    case NonEnvoye = 'not_exported';

    case EnAttenteEnvoi = 'to_be_exported';

    case Envoye = 'exported';

    case Erreur = 'on_error';

    case VerificationStatut = 'awaiting_status';

    /**
     * Indique si le statut interdit la modification et la suppression de la pièce jointe (RG-PJ-010).
     */
    public function bloqueModification(): bool
    {
        return match ($this) {
            self::EnAttenteEnvoi, self::Envoye, self::VerificationStatut => true,
            self::NonEnvoye, self::Erreur => false,
        };
    }

    /**
     * Statuts signalant une pièce jointe dont l'export vers Plat'AU est en cours ou en échec.
     *
     * @return list<self>
     */
    public static function suivisExport(): array
    {
        return [self::EnAttenteEnvoi, self::Erreur, self::VerificationStatut];
    }

    /**
     * @return list<string>
     */
    public static function suivisExportValues(): array
    {
        return array_map(static fn (self $statut): string => $statut->value, self::suivisExport());
    }
}

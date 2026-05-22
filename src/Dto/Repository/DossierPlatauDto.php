<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * DTO pour les blocs dashboard Plat'AU du DossierRepository.
 *
 * Couvre 3 méthodes : findDossiersPlatauSansEtablissement, findDossiersPlatauPjsEnErreur,
 * findDossiersPlatauNouvellesPjs.
 *
 * Hérite de DossierDashboardDto (mêmes colonnes buildSearchDossierBaseSql) et ajoute
 * les colonnes spécifiques Plat'AU (IS_NEW, HAS_NEW_PJ, ID_PIECEJOINTESTATUT).
 */
final class DossierPlatauDto extends DossierDashboardDto
{
    /**
     * Indicateur de nouveau dossier (1 si notifié depuis la dernière visite).
     *
     * @var int|null
     */
    public $IS_NEW;

    /**
     * Indicateur de nouvelle pièce jointe (1 si notifiée depuis la dernière visite).
     *
     * @var int|null
     */
    public $HAS_NEW_PJ;

    /**
     * Statut de la pièce jointe (pour pjPlatauErreur).
     *
     * @var int|null
     */
    public $ID_PIECEJOINTESTATUT;

    /**
     * Construit un DossierPlatauDto depuis une ligne SQL brute.
     *
     * @param array<string, mixed> $row
     *
     * @return self
     */
    public static function fromArray(array $row)
    {
        /** @var self $dto */
        $dto = parent::fromArray($row);
        $dto->IS_NEW = SqlTypeHelper::toInt($row['IS_NEW'] ?? null);
        $dto->HAS_NEW_PJ = SqlTypeHelper::toInt($row['HAS_NEW_PJ'] ?? null);
        $dto->ID_PIECEJOINTESTATUT = SqlTypeHelper::toInt($row['ID_PIECEJOINTESTATUT'] ?? null);

        return $dto;
    }
}

<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * DTO pour DossierRepository::findDossierDateCommissionEchu().
 *
 * Hérite de DossierDashboardDto (colonnes buildSearchDossierBaseSql) et ajoute
 * les colonnes dossieraffectation et datecommission.
 */
final class DossierDateCommissionEchuDto extends DossierDashboardDto
{
    /**
     * @var string|null
     */
    public $HEURE_DEB_AFFECT;

    /**
     * @var string|null
     */
    public $HEURE_FIN_AFFECT;

    /**
     * @var int|null
     */
    public $NUM_DOSSIER;

    /**
     * @var int|null
     */
    public $ID_DATECOMMISSION_AFFECT;

    /**
     * @var int|null
     */
    public $ID_DOSSIER_AFFECT;

    /**
     * @var int|null
     */
    public $ID_DATECOMMISSION;

    /**
     * @var string|null
     */
    public $DATE_COMMISSION;

    /**
     * @var string|null
     */
    public $HEUREDEB_COMMISSION;

    /**
     * @var string|null
     */
    public $HEUREFIN_COMMISSION;

    /**
     * @var string|null
     */
    public $LIBELLE_DATECOMMISSION;

    /**
     * @var int|null
     */
    public $GESTION_HEURES;

    /**
     * @var int|null
     */
    public $DATECOMMISSION_LIEES;

    /**
     * @var int|null
     */
    public $ID_COMMISSIONTYPEEVENEMENT;

    /**
     * @var int|null
     */
    public $COMMISSION_CONCERNE;

    /**
     * Construit un DossierDateCommissionEchuDto depuis une ligne SQL brute.
     *
     * @param array<string, mixed> $row
     *
     * @return self
     */
    #[\Override]
    public static function fromArray(array $row)
    {
        /** @var self $dto */
        $dto = parent::fromArray($row);
        // Champs spécifiques commission/affectation
        $dto->HEURE_DEB_AFFECT = SqlTypeHelper::toStr($row['HEURE_DEB_AFFECT'] ?? null);
        $dto->HEURE_FIN_AFFECT = SqlTypeHelper::toStr($row['HEURE_FIN_AFFECT'] ?? null);
        $dto->NUM_DOSSIER = SqlTypeHelper::toInt($row['NUM_DOSSIER'] ?? null);
        $dto->ID_DATECOMMISSION_AFFECT = SqlTypeHelper::toInt($row['ID_DATECOMMISSION_AFFECT'] ?? null);
        $dto->ID_DOSSIER_AFFECT = SqlTypeHelper::toInt($row['ID_DOSSIER_AFFECT'] ?? null);
        $dto->ID_DATECOMMISSION = SqlTypeHelper::toInt($row['ID_DATECOMMISSION'] ?? null);
        $dto->DATE_COMMISSION = SqlTypeHelper::toStr($row['DATE_COMMISSION'] ?? null);
        $dto->HEUREDEB_COMMISSION = SqlTypeHelper::toStr($row['HEUREDEB_COMMISSION'] ?? null);
        $dto->HEUREFIN_COMMISSION = SqlTypeHelper::toStr($row['HEUREFIN_COMMISSION'] ?? null);
        $dto->LIBELLE_DATECOMMISSION = SqlTypeHelper::toStr($row['LIBELLE_DATECOMMISSION'] ?? null);
        $dto->GESTION_HEURES = SqlTypeHelper::toInt($row['GESTION_HEURES'] ?? null);
        $dto->DATECOMMISSION_LIEES = SqlTypeHelper::toInt($row['DATECOMMISSION_LIEES'] ?? null);
        $dto->ID_COMMISSIONTYPEEVENEMENT = SqlTypeHelper::toInt($row['ID_COMMISSIONTYPEEVENEMENT'] ?? null);
        $dto->COMMISSION_CONCERNE = SqlTypeHelper::toInt($row['COMMISSION_CONCERNE'] ?? null);

        return $dto;
    }
}

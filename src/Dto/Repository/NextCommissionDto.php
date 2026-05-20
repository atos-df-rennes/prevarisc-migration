<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * DTO pour DateCommissionRepository::findNextCommissionsForDashboard().
 *
 * Champs issus de : datecommission d LEFT JOIN commission c.
 * Utilisé par DashboardService::getNextCommission() pour construire les blocs "prochaines commissions".
 */
final class NextCommissionDto
{
    // ---- datecommission.* ----

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

    // ---- commission.* ----

    /**
     * @var int|null
     */
    public $ID_COMMISSION;

    /**
     * @var string|null
     */
    public $LIBELLE_COMMISSION;

    /**
     * Construit un NextCommissionDto depuis une ligne SQL brute.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $dto = new self();
        $dto->ID_DATECOMMISSION = SqlTypeHelper::toInt($row['ID_DATECOMMISSION'] ?? null);
        $dto->DATE_COMMISSION = SqlTypeHelper::toStr($row['DATE_COMMISSION'] ?? null);
        $dto->HEUREDEB_COMMISSION = SqlTypeHelper::toStr($row['HEUREDEB_COMMISSION'] ?? null);
        $dto->HEUREFIN_COMMISSION = SqlTypeHelper::toStr($row['HEUREFIN_COMMISSION'] ?? null);
        $dto->LIBELLE_DATECOMMISSION = SqlTypeHelper::toStr($row['LIBELLE_DATECOMMISSION'] ?? null);
        $dto->GESTION_HEURES = SqlTypeHelper::toInt($row['GESTION_HEURES'] ?? null);
        $dto->DATECOMMISSION_LIEES = SqlTypeHelper::toInt($row['DATECOMMISSION_LIEES'] ?? null);
        $dto->ID_COMMISSIONTYPEEVENEMENT = SqlTypeHelper::toInt($row['ID_COMMISSIONTYPEEVENEMENT'] ?? null);
        $dto->COMMISSION_CONCERNE = SqlTypeHelper::toInt($row['COMMISSION_CONCERNE'] ?? null);
        $dto->ID_COMMISSION = SqlTypeHelper::toInt($row['ID_COMMISSION'] ?? null);
        $dto->LIBELLE_COMMISSION = SqlTypeHelper::toStr($row['LIBELLE_COMMISSION'] ?? null);

        return $dto;
    }
}

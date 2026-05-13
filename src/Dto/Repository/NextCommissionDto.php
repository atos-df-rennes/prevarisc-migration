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
        $dto->ID_DATECOMMISSION = self::toInt($row['ID_DATECOMMISSION'] ?? null);
        $dto->DATE_COMMISSION = self::toStr($row['DATE_COMMISSION'] ?? null);
        $dto->HEUREDEB_COMMISSION = self::toStr($row['HEUREDEB_COMMISSION'] ?? null);
        $dto->HEUREFIN_COMMISSION = self::toStr($row['HEUREFIN_COMMISSION'] ?? null);
        $dto->LIBELLE_DATECOMMISSION = self::toStr($row['LIBELLE_DATECOMMISSION'] ?? null);
        $dto->GESTION_HEURES = self::toInt($row['GESTION_HEURES'] ?? null);
        $dto->DATECOMMISSION_LIEES = self::toInt($row['DATECOMMISSION_LIEES'] ?? null);
        $dto->ID_COMMISSIONTYPEEVENEMENT = self::toInt($row['ID_COMMISSIONTYPEEVENEMENT'] ?? null);
        $dto->COMMISSION_CONCERNE = self::toInt($row['COMMISSION_CONCERNE'] ?? null);
        $dto->ID_COMMISSION = self::toInt($row['ID_COMMISSION'] ?? null);
        $dto->LIBELLE_COMMISSION = self::toStr($row['LIBELLE_COMMISSION'] ?? null);

        return $dto;
    }

    /**
     * @param mixed $value
     */
    private static function toInt($value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return (int) $value;
        }

        if (\is_float($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private static function toStr($value): ?string
    {
        if (\is_string($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return (string) $value;
        }

        if (\is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}

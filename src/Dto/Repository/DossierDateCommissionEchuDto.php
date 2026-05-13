<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * DTO pour DossierRepository::findDossierDateCommissionEchu().
 *
 * Contient les colonnes dashboard (via AbstractDossierResultDto) + les colonnes
 * de buildSearchDossierBaseSql() + les colonnes dossieraffectation et datecommission.
 */
final class DossierDateCommissionEchuDto extends AbstractDossierResultDto
{
    /**
     * @var int|null
     */
    public $NB_PJ;

    /**
     * @var int|null
     */
    public $NB_DOSS_LIES;

    /**
     * @var string|null
     */
    public $NB_URBA;

    /**
     * @var int|null
     */
    public $ALERTE_RECEPTION_TRAVAUX;

    /**
     * @var int|null
     */
    public $ECHEANCIER_TRAVAUX;

    /**
     * @var int|null
     */
    public $ID_DOSSIER1;

    /**
     * @var int|null
     */
    public $ID_DOSSIER2;

    /**
     * @var string|null
     */
    public $NUM_DOCURBA;

    /**
     * @var string|null
     */
    public $LIBELLE_AVIS;

    /**
     * @var int|null
     */
    public $ID_UTILISATEUR;

    /**
     * @var int|null
     */
    public $ID_ETABLISSEMENT;

    /**
     * @var int|null
     */
    public $ID_TYPE;

    /**
     * @var string|null
     */
    public $LIBELLE_TYPE;

    /**
     * @var string|null
     */
    public $LIBELLE_GENRE;

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
     */
    public static function fromArray(array $row): self
    {
        $dto = new self();
        $dto->fillCommonFields($row);
        // Champs de DossierDashboardDto
        $dto->NB_PJ = self::toInt($row['NB_PJ'] ?? null);
        $dto->NB_DOSS_LIES = self::toInt($row['NB_DOSS_LIES'] ?? null);
        $dto->NB_URBA = self::toStr($row['NB_URBA'] ?? null);
        $dto->ALERTE_RECEPTION_TRAVAUX = self::toInt($row['ALERTE_RECEPTION_TRAVAUX'] ?? null);
        $dto->ECHEANCIER_TRAVAUX = self::toInt($row['ECHEANCIER_TRAVAUX'] ?? null);
        $dto->ID_DOSSIER1 = self::toInt($row['ID_DOSSIER1'] ?? null);
        $dto->ID_DOSSIER2 = self::toInt($row['ID_DOSSIER2'] ?? null);
        $dto->NUM_DOCURBA = self::toStr($row['NUM_DOCURBA'] ?? null);
        $dto->LIBELLE_AVIS = self::toStr($row['LIBELLE_AVIS'] ?? null);
        $dto->ID_UTILISATEUR = self::toInt($row['ID_UTILISATEUR'] ?? null);
        $dto->ID_ETABLISSEMENT = self::toInt($row['ID_ETABLISSEMENT'] ?? null);
        $dto->ID_TYPE = self::toInt($row['ID_TYPE'] ?? null);
        $dto->LIBELLE_TYPE = self::toStr($row['LIBELLE_TYPE'] ?? null);
        $dto->LIBELLE_GENRE = self::toStr($row['LIBELLE_GENRE'] ?? null);
        // Champs spécifiques commission/affectation
        $dto->HEURE_DEB_AFFECT = self::toStr($row['HEURE_DEB_AFFECT'] ?? null);
        $dto->HEURE_FIN_AFFECT = self::toStr($row['HEURE_FIN_AFFECT'] ?? null);
        $dto->NUM_DOSSIER = self::toInt($row['NUM_DOSSIER'] ?? null);
        $dto->ID_DATECOMMISSION_AFFECT = self::toInt($row['ID_DATECOMMISSION_AFFECT'] ?? null);
        $dto->ID_DOSSIER_AFFECT = self::toInt($row['ID_DOSSIER_AFFECT'] ?? null);
        $dto->ID_DATECOMMISSION = self::toInt($row['ID_DATECOMMISSION'] ?? null);
        $dto->DATE_COMMISSION = self::toStr($row['DATE_COMMISSION'] ?? null);
        $dto->HEUREDEB_COMMISSION = self::toStr($row['HEUREDEB_COMMISSION'] ?? null);
        $dto->HEUREFIN_COMMISSION = self::toStr($row['HEUREFIN_COMMISSION'] ?? null);
        $dto->LIBELLE_DATECOMMISSION = self::toStr($row['LIBELLE_DATECOMMISSION'] ?? null);
        $dto->GESTION_HEURES = self::toInt($row['GESTION_HEURES'] ?? null);
        $dto->DATECOMMISSION_LIEES = self::toInt($row['DATECOMMISSION_LIEES'] ?? null);
        $dto->ID_COMMISSIONTYPEEVENEMENT = self::toInt($row['ID_COMMISSIONTYPEEVENEMENT'] ?? null);
        $dto->COMMISSION_CONCERNE = self::toInt($row['COMMISSION_CONCERNE'] ?? null);

        return $dto;
    }
}

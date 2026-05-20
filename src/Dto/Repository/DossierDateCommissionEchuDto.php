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
        $dto->NB_PJ = SqlTypeHelper::toInt($row['NB_PJ'] ?? null);
        $dto->NB_DOSS_LIES = SqlTypeHelper::toInt($row['NB_DOSS_LIES'] ?? null);
        $dto->NB_URBA = SqlTypeHelper::toStr($row['NB_URBA'] ?? null);
        $dto->ALERTE_RECEPTION_TRAVAUX = SqlTypeHelper::toInt($row['ALERTE_RECEPTION_TRAVAUX'] ?? null);
        $dto->ECHEANCIER_TRAVAUX = SqlTypeHelper::toInt($row['ECHEANCIER_TRAVAUX'] ?? null);
        $dto->ID_DOSSIER1 = SqlTypeHelper::toInt($row['ID_DOSSIER1'] ?? null);
        $dto->ID_DOSSIER2 = SqlTypeHelper::toInt($row['ID_DOSSIER2'] ?? null);
        $dto->NUM_DOCURBA = SqlTypeHelper::toStr($row['NUM_DOCURBA'] ?? null);
        $dto->LIBELLE_AVIS = SqlTypeHelper::toStr($row['LIBELLE_AVIS'] ?? null);
        $dto->ID_UTILISATEUR = SqlTypeHelper::toInt($row['ID_UTILISATEUR'] ?? null);
        $dto->ID_ETABLISSEMENT = SqlTypeHelper::toInt($row['ID_ETABLISSEMENT'] ?? null);
        $dto->ID_TYPE = SqlTypeHelper::toInt($row['ID_TYPE'] ?? null);
        $dto->LIBELLE_TYPE = SqlTypeHelper::toStr($row['LIBELLE_TYPE'] ?? null);
        $dto->LIBELLE_GENRE = SqlTypeHelper::toStr($row['LIBELLE_GENRE'] ?? null);
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

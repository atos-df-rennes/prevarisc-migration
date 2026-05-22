<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * DTO pour les méthodes dashboard de EtablissementRepository.
 *
 * Couvre 4 méthodes : findERPOuvertsSousAvisDefavorable, findERPOuvertsSansProchainesVisitePeriodiques,
 * findERPSansPreventionniste, findERPSuivisByUser.
 *
 * Champs issus de buildEtablissementBaseSql(false) : e.*, etablissementinformations.*,
 * dossier.*, avis.*, type.*, genre.*, etablissementlie.*, utilisateur.*, etablissementadresse.*,
 * adressecommune.*, sous-requêtes NB_ENFANTS et PRESENCE_ECHEANCIER_TRAVAUX.
 */
final class EtablissementDashboardDto
{
    /**
     * @var int|null
     */
    public $ID_ETABLISSEMENT;

    /**
     * @var string|null
     */
    public $NUMEROID_ETABLISSEMENT;

    /**
     * @var int|null
     */
    public $DUREEVISITE_ETABLISSEMENT;

    /**
     * @var int|null
     */
    public $NBPREV_ETABLISSEMENT;

    /**
     * @var int|null
     */
    public $NB_ENFANTS;

    /**
     * @var int|null
     */
    public $PRESENCE_ECHEANCIER_TRAVAUX;

    // ---- etablissementinformations.* ----

    /**
     * @var int|null
     */
    public $ID_ETABLISSEMENTINFORMATIONS;

    /**
     * @var string|null
     */
    public $LIBELLE_ETABLISSEMENTINFORMATIONS;

    /**
     * @var string|null
     */
    public $DATE_ETABLISSEMENTINFORMATIONS;

    /**
     * @var int|null
     */
    public $LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS;

    /**
     * @var int|null
     */
    public $PERIODICITE_ETABLISSEMENTINFORMATIONS;

    /**
     * @var int|null
     */
    public $ID_STATUT;

    /**
     * @var int|null
     */
    public $ID_TYPE;

    /**
     * @var int|null
     */
    public $ID_CATEGORIE;

    /**
     * @var int|null
     */
    public $ID_GENRE;

    /**
     * @var int|null
     */
    public $ID_COMMISSION;

    // ---- dossier.* (avis donnant) ----

    /**
     * @var string|null
     */
    public $DATEVISITE_DOSSIER;

    /**
     * @var string|null
     */
    public $DATECOMM_DOSSIER;

    /**
     * @var string|null
     */
    public $DATEINSERT_DOSSIER;

    /**
     * @var int|null
     */
    public $DIFFEREAVIS_DOSSIER;

    // ---- avis.* ----

    /**
     * @var int|null
     */
    public $ID_AVIS;

    /**
     * @var string|null
     */
    public $LIBELLE_AVIS;

    // ---- type.* / genre.* ----

    /**
     * @var string|null
     */
    public $LIBELLE_TYPE;

    /**
     * @var string|null
     */
    public $LIBELLE_GENRE;

    // ---- etablissementlie.* ----

    /**
     * @var int|null
     */
    public $pere;

    /**
     * @var int|null
     */
    public $ID_FILS_ETABLISSEMENT;

    // ---- utilisateur.* ----

    /**
     * @var int|null
     */
    public $ID_UTILISATEUR;

    // ---- etablissementadresse.* ----

    /**
     * @var string|null
     */
    public $NUMINSEE_COMMUNE;

    /**
     * @var string|null
     */
    public $LON_ETABLISSEMENTADRESSE;

    /**
     * @var string|null
     */
    public $LAT_ETABLISSEMENTADRESSE;

    /**
     * @var int|null
     */
    public $ID_ADRESSE;

    /**
     * @var int|null
     */
    public $ID_RUE;

    // ---- adressecommune.* (aliases) ----

    /**
     * @var string|null
     */
    public $LIBELLE_COMMUNE_ADRESSE_DEFAULT;

    /**
     * @var int|null
     */
    public $ID_RUE_SITE;

    /**
     * @var string|null
     */
    public $LIBELLE_COMMUNE_ADRESSE_SITE;

    /**
     * @var int|null
     */
    public $ID_RUE_CELL;

    /**
     * @var string|null
     */
    public $LIBELLE_COMMUNE_ADRESSE_CELLULE;

    /**
     * Construit un EtablissementDashboardDto depuis une ligne SQL brute.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $dto = new self();
        $dto->ID_ETABLISSEMENT = SqlTypeHelper::toInt($row['ID_ETABLISSEMENT'] ?? null);
        $dto->NUMEROID_ETABLISSEMENT = SqlTypeHelper::toStr($row['NUMEROID_ETABLISSEMENT'] ?? null);
        $dto->DUREEVISITE_ETABLISSEMENT = SqlTypeHelper::toInt($row['DUREEVISITE_ETABLISSEMENT'] ?? null);
        $dto->NBPREV_ETABLISSEMENT = SqlTypeHelper::toInt($row['NBPREV_ETABLISSEMENT'] ?? null);
        $dto->NB_ENFANTS = SqlTypeHelper::toInt($row['NB_ENFANTS'] ?? null);
        $dto->PRESENCE_ECHEANCIER_TRAVAUX = SqlTypeHelper::toInt($row['PRESENCE_ECHEANCIER_TRAVAUX'] ?? null);
        $dto->ID_ETABLISSEMENTINFORMATIONS = SqlTypeHelper::toInt($row['ID_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->LIBELLE_ETABLISSEMENTINFORMATIONS = SqlTypeHelper::toStr($row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->DATE_ETABLISSEMENTINFORMATIONS = SqlTypeHelper::toStr($row['DATE_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS = SqlTypeHelper::toInt($row['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->PERIODICITE_ETABLISSEMENTINFORMATIONS = SqlTypeHelper::toInt($row['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->ID_STATUT = SqlTypeHelper::toInt($row['ID_STATUT'] ?? null);
        $dto->ID_TYPE = SqlTypeHelper::toInt($row['ID_TYPE'] ?? null);
        $dto->ID_CATEGORIE = SqlTypeHelper::toInt($row['ID_CATEGORIE'] ?? null);
        $dto->ID_GENRE = SqlTypeHelper::toInt($row['ID_GENRE'] ?? null);
        $dto->ID_COMMISSION = SqlTypeHelper::toInt($row['ID_COMMISSION'] ?? null);
        $dto->DATEVISITE_DOSSIER = SqlTypeHelper::toStr($row['DATEVISITE_DOSSIER'] ?? null);
        $dto->DATECOMM_DOSSIER = SqlTypeHelper::toStr($row['DATECOMM_DOSSIER'] ?? null);
        $dto->DATEINSERT_DOSSIER = SqlTypeHelper::toStr($row['DATEINSERT_DOSSIER'] ?? null);
        $dto->DIFFEREAVIS_DOSSIER = SqlTypeHelper::toInt($row['DIFFEREAVIS_DOSSIER'] ?? null);
        $dto->ID_AVIS = SqlTypeHelper::toInt($row['ID_AVIS'] ?? null);
        $dto->LIBELLE_AVIS = SqlTypeHelper::toStr($row['LIBELLE_AVIS'] ?? null);
        $dto->LIBELLE_TYPE = SqlTypeHelper::toStr($row['LIBELLE_TYPE'] ?? null);
        $dto->LIBELLE_GENRE = SqlTypeHelper::toStr($row['LIBELLE_GENRE'] ?? null);
        $dto->pere = SqlTypeHelper::toInt($row['pere'] ?? null);
        $dto->ID_FILS_ETABLISSEMENT = SqlTypeHelper::toInt($row['ID_FILS_ETABLISSEMENT'] ?? null);
        $dto->ID_UTILISATEUR = SqlTypeHelper::toInt($row['ID_UTILISATEUR'] ?? null);
        $dto->NUMINSEE_COMMUNE = SqlTypeHelper::toStr($row['NUMINSEE_COMMUNE'] ?? null);
        $dto->LON_ETABLISSEMENTADRESSE = SqlTypeHelper::toStr($row['LON_ETABLISSEMENTADRESSE'] ?? null);
        $dto->LAT_ETABLISSEMENTADRESSE = SqlTypeHelper::toStr($row['LAT_ETABLISSEMENTADRESSE'] ?? null);
        $dto->ID_ADRESSE = SqlTypeHelper::toInt($row['ID_ADRESSE'] ?? null);
        $dto->ID_RUE = SqlTypeHelper::toInt($row['ID_RUE'] ?? null);
        $dto->LIBELLE_COMMUNE_ADRESSE_DEFAULT = SqlTypeHelper::toStr($row['LIBELLE_COMMUNE_ADRESSE_DEFAULT'] ?? null);
        $dto->ID_RUE_SITE = SqlTypeHelper::toInt($row['ID_RUE_SITE'] ?? null);
        $dto->LIBELLE_COMMUNE_ADRESSE_SITE = SqlTypeHelper::toStr($row['LIBELLE_COMMUNE_ADRESSE_SITE'] ?? null);
        $dto->ID_RUE_CELL = SqlTypeHelper::toInt($row['ID_RUE_CELL'] ?? null);
        $dto->LIBELLE_COMMUNE_ADRESSE_CELLULE = SqlTypeHelper::toStr($row['LIBELLE_COMMUNE_ADRESSE_CELLULE'] ?? null);

        return $dto;
    }
}

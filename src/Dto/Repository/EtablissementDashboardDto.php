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
        $dto->ID_ETABLISSEMENT = self::toInt($row['ID_ETABLISSEMENT'] ?? null);
        $dto->NUMEROID_ETABLISSEMENT = self::toStr($row['NUMEROID_ETABLISSEMENT'] ?? null);
        $dto->DUREEVISITE_ETABLISSEMENT = self::toInt($row['DUREEVISITE_ETABLISSEMENT'] ?? null);
        $dto->NBPREV_ETABLISSEMENT = self::toInt($row['NBPREV_ETABLISSEMENT'] ?? null);
        $dto->NB_ENFANTS = self::toInt($row['NB_ENFANTS'] ?? null);
        $dto->PRESENCE_ECHEANCIER_TRAVAUX = self::toInt($row['PRESENCE_ECHEANCIER_TRAVAUX'] ?? null);
        $dto->ID_ETABLISSEMENTINFORMATIONS = self::toInt($row['ID_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->LIBELLE_ETABLISSEMENTINFORMATIONS = self::toStr($row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->DATE_ETABLISSEMENTINFORMATIONS = self::toStr($row['DATE_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS = self::toInt($row['LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->PERIODICITE_ETABLISSEMENTINFORMATIONS = self::toInt($row['PERIODICITE_ETABLISSEMENTINFORMATIONS'] ?? null);
        $dto->ID_STATUT = self::toInt($row['ID_STATUT'] ?? null);
        $dto->ID_TYPE = self::toInt($row['ID_TYPE'] ?? null);
        $dto->ID_CATEGORIE = self::toInt($row['ID_CATEGORIE'] ?? null);
        $dto->ID_GENRE = self::toInt($row['ID_GENRE'] ?? null);
        $dto->ID_COMMISSION = self::toInt($row['ID_COMMISSION'] ?? null);
        $dto->DATEVISITE_DOSSIER = self::toStr($row['DATEVISITE_DOSSIER'] ?? null);
        $dto->DATECOMM_DOSSIER = self::toStr($row['DATECOMM_DOSSIER'] ?? null);
        $dto->DATEINSERT_DOSSIER = self::toStr($row['DATEINSERT_DOSSIER'] ?? null);
        $dto->DIFFEREAVIS_DOSSIER = self::toInt($row['DIFFEREAVIS_DOSSIER'] ?? null);
        $dto->ID_AVIS = self::toInt($row['ID_AVIS'] ?? null);
        $dto->LIBELLE_AVIS = self::toStr($row['LIBELLE_AVIS'] ?? null);
        $dto->LIBELLE_TYPE = self::toStr($row['LIBELLE_TYPE'] ?? null);
        $dto->LIBELLE_GENRE = self::toStr($row['LIBELLE_GENRE'] ?? null);
        $dto->pere = self::toInt($row['pere'] ?? null);
        $dto->ID_FILS_ETABLISSEMENT = self::toInt($row['ID_FILS_ETABLISSEMENT'] ?? null);
        $dto->ID_UTILISATEUR = self::toInt($row['ID_UTILISATEUR'] ?? null);
        $dto->NUMINSEE_COMMUNE = self::toStr($row['NUMINSEE_COMMUNE'] ?? null);
        $dto->LON_ETABLISSEMENTADRESSE = self::toStr($row['LON_ETABLISSEMENTADRESSE'] ?? null);
        $dto->LAT_ETABLISSEMENTADRESSE = self::toStr($row['LAT_ETABLISSEMENTADRESSE'] ?? null);
        $dto->ID_ADRESSE = self::toInt($row['ID_ADRESSE'] ?? null);
        $dto->ID_RUE = self::toInt($row['ID_RUE'] ?? null);
        $dto->LIBELLE_COMMUNE_ADRESSE_DEFAULT = self::toStr($row['LIBELLE_COMMUNE_ADRESSE_DEFAULT'] ?? null);
        $dto->ID_RUE_SITE = self::toInt($row['ID_RUE_SITE'] ?? null);
        $dto->LIBELLE_COMMUNE_ADRESSE_SITE = self::toStr($row['LIBELLE_COMMUNE_ADRESSE_SITE'] ?? null);
        $dto->ID_RUE_CELL = self::toInt($row['ID_RUE_CELL'] ?? null);
        $dto->LIBELLE_COMMUNE_ADRESSE_CELLULE = self::toStr($row['LIBELLE_COMMUNE_ADRESSE_CELLULE'] ?? null);

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

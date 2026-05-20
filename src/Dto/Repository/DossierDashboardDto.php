<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * DTO pour les méthodes dashboard de DossierRepository utilisant buildSearchDossierBaseSql().
 *
 * Couvre 8 méthodes : findDossierAvecAvisDiffere, findCourrierSansReponse, findLeveePresc,
 * findAbsenceQuorum, findNpsp, findDossiersIncomplets, findDossiersSuivisNonVerrouilles,
 * findDossiersSuivisSansAvis.
 *
 * Classe non-finale : étendue par DossierPlatauDto et DossierDateCommissionEchuDto
 * qui ajoutent des colonnes spécifiques au-dessus du jeu dashboard standard.
 *
 * @phpstan-consistent-constructor
 *
 * @see AbstractDossierResultDto pour les champs communs avec DossierAffectationDto
 */
class DossierDashboardDto extends AbstractDossierResultDto
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
     * Construit un DossierDashboardDto depuis une ligne SQL brute.
     *
     * @param array<string, mixed> $row
     *
     * @return static
     */
    public static function fromArray(array $row)
    {
        $dto = new static();
        $dto->fillCommonFields($row);
        $dto->fillDashboardFields($row);

        return $dto;
    }

    /**
     * Remplit les champs dashboard (colonnes de buildSearchDossierBaseSql).
     *
     * @param array<string, mixed> $row
     */
    protected function fillDashboardFields(array $row): void
    {
        $this->NB_PJ = SqlTypeHelper::toInt($row['NB_PJ'] ?? null);
        $this->NB_DOSS_LIES = SqlTypeHelper::toInt($row['NB_DOSS_LIES'] ?? null);
        $this->NB_URBA = SqlTypeHelper::toStr($row['NB_URBA'] ?? null);
        $this->ALERTE_RECEPTION_TRAVAUX = SqlTypeHelper::toInt($row['ALERTE_RECEPTION_TRAVAUX'] ?? null);
        $this->ECHEANCIER_TRAVAUX = SqlTypeHelper::toInt($row['ECHEANCIER_TRAVAUX'] ?? null);
        $this->ID_DOSSIER1 = SqlTypeHelper::toInt($row['ID_DOSSIER1'] ?? null);
        $this->ID_DOSSIER2 = SqlTypeHelper::toInt($row['ID_DOSSIER2'] ?? null);
        $this->NUM_DOCURBA = SqlTypeHelper::toStr($row['NUM_DOCURBA'] ?? null);
        $this->LIBELLE_AVIS = SqlTypeHelper::toStr($row['LIBELLE_AVIS'] ?? null);
        $this->ID_UTILISATEUR = SqlTypeHelper::toInt($row['ID_UTILISATEUR'] ?? null);
        $this->ID_ETABLISSEMENT = SqlTypeHelper::toInt($row['ID_ETABLISSEMENT'] ?? null);
        $this->ID_TYPE = SqlTypeHelper::toInt($row['ID_TYPE'] ?? null);
        $this->LIBELLE_TYPE = SqlTypeHelper::toStr($row['LIBELLE_TYPE'] ?? null);
        $this->LIBELLE_GENRE = SqlTypeHelper::toStr($row['LIBELLE_GENRE'] ?? null);
    }
}

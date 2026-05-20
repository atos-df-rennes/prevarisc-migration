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
 * @see AbstractDossierResultDto pour les champs communs avec DossierAffectationDto
 */
final class DossierDashboardDto extends AbstractDossierResultDto
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
     */
    public static function fromArray(array $row): self
    {
        $dto = new self();
        $dto->fillCommonFields($row);
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

        return $dto;
    }
}

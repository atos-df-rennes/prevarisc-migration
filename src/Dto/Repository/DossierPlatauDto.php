<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * DTO pour les blocs dashboard Plat'AU du DossierRepository.
 *
 * Couvre 3 méthodes : findDossiersPlatauSansEtablissement, findDossiersPlatauPjsEnErreur,
 * findDossiersPlatauNouvellesPjs.
 *
 * Hérite des champs communs dossier (AbstractDossierResultDto) et des champs dashboard
 * (NB_PJ, NB_DOSS_LIES, etc.) + colonnes spécifiques Plat'AU (IS_NEW, HAS_NEW_PJ,
 * ID_PIECEJOINTESTATUT).
 */
final class DossierPlatauDto extends AbstractDossierResultDto
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

    // ---- Champs spécifiques Plat'AU ----

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
        $dto->IS_NEW = SqlTypeHelper::toInt($row['IS_NEW'] ?? null);
        $dto->HAS_NEW_PJ = SqlTypeHelper::toInt($row['HAS_NEW_PJ'] ?? null);
        $dto->ID_PIECEJOINTESTATUT = SqlTypeHelper::toInt($row['ID_PIECEJOINTESTATUT'] ?? null);

        return $dto;
    }
}

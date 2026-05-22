<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * DTO pour DossierSearchService::dossiers().
 *
 * Contient les champs communs du dossier (via AbstractDossierResultDto) + les colonnes
 * supplémentaires spécifiques à la recherche (sous-requêtes corrélées, jointures affichage).
 *
 * Les propriétés PREVENTIONNISTES et PIECESJOINTES sont enrichies en post-traitement
 * par le service après la requête principale.
 */
final class DossierSearchResultDto extends AbstractDossierResultDto
{
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
    public $CODEPOSTAL_COMMUNE;

    /**
     * @var string|null
     */
    public $LIBELLE_COMMUNE;

    /**
     * Liste des préventionnistes enrichis par le service (post-traitement batch).
     * Null si aucun préventionniste affecté.
     *
     * @var array<int, array<string, mixed>>|null
     */
    public $PREVENTIONNISTES;

    /**
     * Liste des pièces jointes enrichies par le service (post-traitement batch).
     * Null si aucune pièce jointe.
     *
     * @var array<int, array<string, mixed>>|null
     */
    public $PIECESJOINTES;

    /**
     * Construit un DossierSearchResultDto depuis une ligne SQL brute.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $dto = new self();
        $dto->fillCommonFields($row);
        $dto->NB_DOSS_LIES = SqlTypeHelper::toInt($row['NB_DOSS_LIES'] ?? null);
        $dto->NB_URBA = SqlTypeHelper::toStr($row['NB_URBA'] ?? null);
        $dto->ALERTE_RECEPTION_TRAVAUX = SqlTypeHelper::toInt($row['ALERTE_RECEPTION_TRAVAUX'] ?? null);
        $dto->ECHEANCIER_TRAVAUX = SqlTypeHelper::toInt($row['ECHEANCIER_TRAVAUX'] ?? null);
        $dto->ID_ETABLISSEMENT = SqlTypeHelper::toInt($row['ID_ETABLISSEMENT'] ?? null);
        $dto->ID_TYPE = SqlTypeHelper::toInt($row['ID_TYPE'] ?? null);
        $dto->LIBELLE_TYPE = SqlTypeHelper::toStr($row['LIBELLE_TYPE'] ?? null);
        $dto->LIBELLE_GENRE = SqlTypeHelper::toStr($row['LIBELLE_GENRE'] ?? null);
        $dto->CODEPOSTAL_COMMUNE = SqlTypeHelper::toStr($row['CODEPOSTAL_COMMUNE'] ?? null);
        $dto->LIBELLE_COMMUNE = SqlTypeHelper::toStr($row['LIBELLE_COMMUNE'] ?? null);

        return $dto;
    }
}

<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * DTO pour DateCommissionRepository::findDossierAffectForCommission()
 * et DateCommissionRepository::findDossierNonAffectForCommission().
 *
 * Contient les champs du dossier (doss.*) + les champs de dossieraffectation
 * + LIBELLE_DOSSIERNATURE, LIBELLE_DOSSIERTYPE, LIBELLE_ETABLISSEMENTINFORMATIONS.
 *
 * Propriétés enrichies optionnellement par le contrôleur (infosEtab, listeDocUrba,
 * preventionnistes) via assignation directe sur l'objet.
 *
 * @see AbstractDossierResultDto pour les champs communs partagés avec DossierDashboardDto
 */
final class DossierAffectationDto extends AbstractDossierResultDto
{
    /**
     * @var int|null
     */
    public $ID_DOSSIER_AFFECT;

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
     * Données établissement enrichies par le contrôleur (informations, adresses, parents).
     * Null si non enrichi (dossier sans établissement ou enrichissement non effectué).
     *
     * @var array<string, mixed>|null
     */
    public $infosEtab;

    /**
     * Liste des documents d'urbanisme enrichis par le contrôleur.
     * Null si non enrichi.
     *
     * @var array<int, array<string, mixed>>|null
     */
    public $listeDocUrba;

    /**
     * Liste des préventionnistes enrichis par le contrôleur.
     * Null si non enrichi.
     *
     * @var array<int, array<string, mixed>>|null
     */
    public $preventionnistes;

    /**
     * Construit un DossierAffectationDto depuis une ligne SQL brute.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $dto = new self();
        $dto->fillCommonFields($row);
        $dto->ID_DOSSIER_AFFECT = self::toInt($row['ID_DOSSIER_AFFECT'] ?? null);
        $dto->HEURE_DEB_AFFECT = self::toStr($row['HEURE_DEB_AFFECT'] ?? null);
        $dto->HEURE_FIN_AFFECT = self::toStr($row['HEURE_FIN_AFFECT'] ?? null);
        $dto->NUM_DOSSIER = self::toInt($row['NUM_DOSSIER'] ?? null);
        $dto->ID_DATECOMMISSION_AFFECT = self::toInt($row['ID_DATECOMMISSION_AFFECT'] ?? null);

        return $dto;
    }
}

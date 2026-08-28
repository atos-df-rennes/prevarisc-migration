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
     * Document d'urbanisme (chaîne concaténée des NUM_DOCURBA pour mode séquentiel).
     * Récupérée via SELECT group_concat() ou enrichie par listeDocUrba.
     *
     * @var string|null
     */
    public $NUM_DOCURBA;

    /**
     * Alias de NUM_DOCURBA, utilisé par le template partagé _dossier.html.twig
     * (comme pour DossierDashboardDto).
     *
     * @var string|null
     */
    public $NB_URBA;

    /**
     * Non calculé par les requêtes d'ODJ de commission, mais requis par le template
     * partagé _dossier.html.twig (comme pour DossierDashboardDto).
     *
     * @var int|null
     */
    public $NB_PJ;

    /**
     * Non calculé par les requêtes d'ODJ de commission, mais requis par le template
     * partagé _dossier.html.twig (comme pour DossierDashboardDto).
     *
     * @var int|null
     */
    public $ALERTE_RECEPTION_TRAVAUX;

    /**
     * Non calculé par les requêtes d'ODJ de commission, mais requis par le template
     * partagé _dossier.html.twig (comme pour DossierDashboardDto).
     *
     * @var int|null
     */
    public $ECHEANCIER_TRAVAUX;

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
        $dto->ID_DOSSIER_AFFECT = SqlTypeHelper::toInt($row['ID_DOSSIER_AFFECT'] ?? null);
        $dto->HEURE_DEB_AFFECT = SqlTypeHelper::toStr($row['HEURE_DEB_AFFECT'] ?? null);
        $dto->HEURE_FIN_AFFECT = SqlTypeHelper::toStr($row['HEURE_FIN_AFFECT'] ?? null);
        $dto->NUM_DOSSIER = SqlTypeHelper::toInt($row['NUM_DOSSIER'] ?? null);
        $dto->ID_DATECOMMISSION_AFFECT = SqlTypeHelper::toInt($row['ID_DATECOMMISSION_AFFECT'] ?? null);
        $dto->NUM_DOCURBA = SqlTypeHelper::toStr($row['NUM_DOCURBA'] ?? null);
        $dto->NB_URBA = $dto->NUM_DOCURBA;
        $dto->NB_PJ = SqlTypeHelper::toInt($row['NB_PJ'] ?? null);
        $dto->ALERTE_RECEPTION_TRAVAUX = SqlTypeHelper::toInt($row['ALERTE_RECEPTION_TRAVAUX'] ?? null);
        $dto->ECHEANCIER_TRAVAUX = SqlTypeHelper::toInt($row['ECHEANCIER_TRAVAUX'] ?? null);

        return $dto;
    }
}

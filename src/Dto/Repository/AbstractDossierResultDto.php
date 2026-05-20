<?php

declare(strict_types=1);

namespace App\Dto\Repository;

/**
 * Classe abstraite socle pour les DTOs de résultats de repository Dossier.
 *
 * Contient les champs communs accessibles par les templates Twig (_dossier.html.twig)
 * et le filtre dossier_avis_badge, qu'ils viennent d'un bloc dashboard ou d'un ODJ commission.
 *
 * Les propriétés sont en SCREAMING_SNAKE_CASE pour correspondre exactement aux colonnes SQL
 * et permettre l'accès Twig sans changer les templates existants.
 */
abstract class AbstractDossierResultDto
{
    /**
     * @var int|null
     */
    public $ID_DOSSIER;

    /**
     * @var int|null
     */
    public $TYPE_DOSSIER;

    /**
     * @var int|null
     */
    public $INCOMPLET_DOSSIER;

    /**
     * @var int|null
     */
    public $DIFFEREAVIS_DOSSIER;

    /**
     * @var int|null
     */
    public $VERROU_DOSSIER;

    /**
     * @var string|null
     */
    public $OBJET_DOSSIER;

    /**
     * @var string|null
     */
    public $DATECOMM_DOSSIER;

    /**
     * @var string|null
     */
    public $DATEVISITE_DOSSIER;

    /**
     * @var string|null
     */
    public $DATEINSERT_DOSSIER;

    /**
     * @var string|null
     */
    public $DATEREP_DOSSIER;

    /**
     * @var string|null
     */
    public $DATESDIS_DOSSIER;

    /**
     * @var string|null
     */
    public $ID_PLATAU;

    /**
     * @var string|null
     */
    public $LIBELLE_DOSSIERNATURE;

    /**
     * @var int|null
     */
    public $ID_DOSSIERNATURE;

    /**
     * @var string|null
     */
    public $LIBELLE_DOSSIERTYPE;

    /**
     * @var int|null
     */
    public $ID_AVIS;

    /**
     * @var string|null
     */
    public $LIBELLE_ETABLISSEMENTINFORMATIONS;

    /**
     * @var string|null
     */
    public $DATE_REPONSE_ATTENDUE;

    /**
     * Remplit les champs communs depuis un tableau brut SQL.
     *
     * @param array<string, mixed> $row
     */
    protected function fillCommonFields(array $row): void
    {
        $this->ID_DOSSIER = SqlTypeHelper::toInt($row['ID_DOSSIER'] ?? null);
        $this->TYPE_DOSSIER = SqlTypeHelper::toInt($row['TYPE_DOSSIER'] ?? null);
        $this->INCOMPLET_DOSSIER = SqlTypeHelper::toInt($row['INCOMPLET_DOSSIER'] ?? null);
        $this->DIFFEREAVIS_DOSSIER = SqlTypeHelper::toInt($row['DIFFEREAVIS_DOSSIER'] ?? null);
        $this->VERROU_DOSSIER = SqlTypeHelper::toInt($row['VERROU_DOSSIER'] ?? null);
        $this->OBJET_DOSSIER = SqlTypeHelper::toStr($row['OBJET_DOSSIER'] ?? null);
        $this->DATECOMM_DOSSIER = SqlTypeHelper::toStr($row['DATECOMM_DOSSIER'] ?? null);
        $this->DATEVISITE_DOSSIER = SqlTypeHelper::toStr($row['DATEVISITE_DOSSIER'] ?? null);
        $this->DATEINSERT_DOSSIER = SqlTypeHelper::toStr($row['DATEINSERT_DOSSIER'] ?? null);
        $this->DATEREP_DOSSIER = SqlTypeHelper::toStr($row['DATEREP_DOSSIER'] ?? null);
        $this->DATESDIS_DOSSIER = SqlTypeHelper::toStr($row['DATESDIS_DOSSIER'] ?? null);
        $this->ID_PLATAU = SqlTypeHelper::toStr($row['ID_PLATAU'] ?? null);
        $this->LIBELLE_DOSSIERNATURE = SqlTypeHelper::toStr($row['LIBELLE_DOSSIERNATURE'] ?? null);
        $this->ID_DOSSIERNATURE = SqlTypeHelper::toInt($row['ID_DOSSIERNATURE'] ?? null);
        $this->LIBELLE_DOSSIERTYPE = SqlTypeHelper::toStr($row['LIBELLE_DOSSIERTYPE'] ?? null);
        $this->ID_AVIS = SqlTypeHelper::toInt($row['ID_AVIS'] ?? null);
        $this->LIBELLE_ETABLISSEMENTINFORMATIONS = SqlTypeHelper::toStr($row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? null);
        $this->DATE_REPONSE_ATTENDUE = SqlTypeHelper::toStr($row['DATE_REPONSE_ATTENDUE'] ?? null);
    }
}

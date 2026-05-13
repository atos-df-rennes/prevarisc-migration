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
        $this->ID_DOSSIER = self::toInt($row['ID_DOSSIER'] ?? null);
        $this->TYPE_DOSSIER = self::toInt($row['TYPE_DOSSIER'] ?? null);
        $this->INCOMPLET_DOSSIER = self::toInt($row['INCOMPLET_DOSSIER'] ?? null);
        $this->DIFFEREAVIS_DOSSIER = self::toInt($row['DIFFEREAVIS_DOSSIER'] ?? null);
        $this->VERROU_DOSSIER = self::toInt($row['VERROU_DOSSIER'] ?? null);
        $this->OBJET_DOSSIER = self::toStr($row['OBJET_DOSSIER'] ?? null);
        $this->DATECOMM_DOSSIER = self::toStr($row['DATECOMM_DOSSIER'] ?? null);
        $this->DATEVISITE_DOSSIER = self::toStr($row['DATEVISITE_DOSSIER'] ?? null);
        $this->DATEINSERT_DOSSIER = self::toStr($row['DATEINSERT_DOSSIER'] ?? null);
        $this->DATEREP_DOSSIER = self::toStr($row['DATEREP_DOSSIER'] ?? null);
        $this->DATESDIS_DOSSIER = self::toStr($row['DATESDIS_DOSSIER'] ?? null);
        $this->ID_PLATAU = self::toStr($row['ID_PLATAU'] ?? null);
        $this->LIBELLE_DOSSIERNATURE = self::toStr($row['LIBELLE_DOSSIERNATURE'] ?? null);
        $this->ID_DOSSIERNATURE = self::toInt($row['ID_DOSSIERNATURE'] ?? null);
        $this->LIBELLE_DOSSIERTYPE = self::toStr($row['LIBELLE_DOSSIERTYPE'] ?? null);
        $this->ID_AVIS = self::toInt($row['ID_AVIS'] ?? null);
        $this->LIBELLE_ETABLISSEMENTINFORMATIONS = self::toStr($row['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? null);
        $this->DATE_REPONSE_ATTENDUE = self::toStr($row['DATE_REPONSE_ATTENDUE'] ?? null);
    }

    /**
     * Convertit une valeur mixte en int ou null de façon sûre pour PHPStan niveau 10.
     *
     * @param mixed $value
     */
    protected static function toInt($value): ?int
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
     * Convertit une valeur mixte en string ou null de façon sûre pour PHPStan niveau 10.
     *
     * @param mixed $value
     */
    protected static function toStr($value): ?string
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

<?php

namespace App\Dto\Descriptif;

/**
 * DTO représentant la valeur d'un champ dans le formulaire personnalisé.
 *
 * Pour les champs simples (TEXT, TEXTAREA, SELECT, NUMBER, CHECKBOX, DATE),
 * seuls $champId, $valeurId et $value sont utilisés.
 *
 * Pour les champs Parent avec tableau=true, $lignes contient les lignes du tableau.
 * Pour les champs Parent avec tableau=false, $sousChamps contient les sous-champs du groupe.
 */
final class ChampValueData
{
    /**
     * @param int                                     $champId    ID du champ
     * @param string|int|bool|\DateTimeInterface|null $value      Valeur du champ (TEXT/SELECT/TEXTAREA → string|null, NUMBER → int|null, CHECKBOX → bool|null, DATE → \DateTimeInterface|null, PARENT → null)
     * @param TableauRowData[]                        $lignes     Lignes du tableau (pour PARENT avec tableau=true)
     * @param ChampValueData[]                        $sousChamps Sous-champs du groupe (pour PARENT avec tableau=false)
     * @param int|null                                $valeurId   ID de la Valeur existante (null = nouvelle valeur)
     */
    public function __construct(
        private int $champId,
        private $value = null,
        private array $lignes = [],
        private array $sousChamps = [],
        private ?int $valeurId = null
    ) {
    }

    public function getChampId(): int
    {
        return $this->champId;
    }

    public function setChampId(int $champId): self
    {
        $this->champId = $champId;

        return $this;
    }

    public function getValeurId(): ?int
    {
        return $this->valeurId;
    }

    public function setValeurId(?int $valeurId): self
    {
        $this->valeurId = $valeurId;

        return $this;
    }

    /**
     * @return string|int|bool|\DateTimeInterface|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string|int|bool|\DateTimeInterface|null $value
     */
    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return TableauRowData[]
     */
    public function getLignes(): array
    {
        return $this->lignes;
    }

    /**
     * @param TableauRowData[] $lignes
     */
    public function setLignes(array $lignes): self
    {
        $this->lignes = $lignes;

        return $this;
    }

    public function addLigne(TableauRowData $ligne): self
    {
        $this->lignes[] = $ligne;

        return $this;
    }

    /**
     * @return ChampValueData[]
     */
    public function getSousChamps(): array
    {
        return $this->sousChamps;
    }

    /**
     * @param ChampValueData[] $sousChamps
     */
    public function setSousChamps(array $sousChamps): self
    {
        $this->sousChamps = $sousChamps;

        return $this;
    }

    public function getSousChamp(int $champId): ?self
    {
        return $this->sousChamps[$champId] ?? null;
    }

    public function setSousChamp(int $champId, self $data): self
    {
        $this->sousChamps[$champId] = $data;

        return $this;
    }
}

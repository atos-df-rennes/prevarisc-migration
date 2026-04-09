<?php

namespace App\Dto\Descriptif;

/**
 * DTO représentant une ligne d'un champ tableau (Parent avec tableau=true).
 *
 * Chaque cellule est indexée par le champId de l'enfant correspondant.
 * Les valeurIds permettent de suivre les Valeur existantes pour update vs insert.
 */
class TableauRowData
{
    /**
     * Valeurs des cellules indexées par champId enfant.
     *
     * @var array<int, mixed>
     */
    private $cells;

    /**
     * IDs des Valeur existantes indexés par champId enfant (null = nouvelle valeur).
     *
     * @var array<int, int|null>
     */
    private $valeurIds;

    /**
     * @param array<int, mixed>    $cells
     * @param array<int, int|null> $valeurIds
     */
    public function __construct(array $cells = [], array $valeurIds = [])
    {
        $this->cells = $cells;
        $this->valeurIds = $valeurIds;
    }

    /**
     * @return array<int, mixed>
     */
    public function getCells(): array
    {
        return $this->cells;
    }

    /**
     * @param array<int, mixed> $cells
     */
    public function setCells(array $cells): self
    {
        $this->cells = $cells;

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setCell(int $champId, $value): self
    {
        $this->cells[$champId] = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCell(int $champId)
    {
        return $this->cells[$champId] ?? null;
    }

    /**
     * @return array<int, int|null>
     */
    public function getValeurIds(): array
    {
        return $this->valeurIds;
    }

    /**
     * @param array<int, int|null> $valeurIds
     */
    public function setValeurIds(array $valeurIds): self
    {
        $this->valeurIds = $valeurIds;

        return $this;
    }

    public function setValeurId(int $champId, ?int $valeurId): self
    {
        $this->valeurIds[$champId] = $valeurId;

        return $this;
    }

    public function getValeurId(int $champId): ?int
    {
        return $this->valeurIds[$champId] ?? null;
    }
}

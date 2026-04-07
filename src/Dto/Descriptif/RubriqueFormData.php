<?php

namespace App\Dto\Descriptif;

/**
 * DTO représentant une rubrique dans le formulaire personnalisé.
 *
 * Contient la préférence d'affichage et les données des champs racines
 * (champs sans parent) de la rubrique.
 */
class RubriqueFormData
{
    /**
     * Préférence d'affichage de la rubrique (true = visible, false = masquée).
     *
     * @var bool
     */
    private $display;

    /**
     * Données des champs racines indexées par champId.
     *
     * @var array<int, ChampValueData>
     */
    private $champs;

    /**
     * @param array<int, ChampValueData> $champs
     */
    public function __construct(bool $display = true, array $champs = [])
    {
        $this->display = $display;
        $this->champs = $champs;
    }

    public function isDisplay(): bool
    {
        return $this->display;
    }

    public function setDisplay(bool $display): self
    {
        $this->display = $display;

        return $this;
    }

    /**
     * @return array<int, ChampValueData>
     */
    public function getChamps(): array
    {
        return $this->champs;
    }

    /**
     * @param array<int, ChampValueData> $champs
     */
    public function setChamps(array $champs): self
    {
        $this->champs = $champs;

        return $this;
    }

    public function getChamp(int $champId): ?ChampValueData
    {
        return $this->champs[$champId] ?? null;
    }

    public function setChamp(int $champId, ChampValueData $data): self
    {
        $this->champs[$champId] = $data;

        return $this;
    }
}

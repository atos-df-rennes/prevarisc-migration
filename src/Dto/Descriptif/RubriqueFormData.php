<?php

namespace App\Dto\Descriptif;

/**
 * DTO représentant une rubrique dans le formulaire personnalisé.
 *
 * Contient la préférence d'affichage et les données des champs racines
 * (champs sans parent) de la rubrique.
 *
 * Implémente ArrayAccess pour permettre à Symfony Forms d'utiliser
 * la notation bracket dans property_path (ex: '[champ_123]').
 *
 * @implements \ArrayAccess<string, ChampValueData|null>
 */
class RubriqueFormData implements \ArrayAccess
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

    // --- ArrayAccess (clés au format "champ_{id}") ---

    public function offsetExists($offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }

        return null !== $this->getChamp($this->parseChampId($offset));
    }

    /**
     * @return ChampValueData|null
     */
    public function offsetGet($offset)
    {
        if (!is_string($offset)) {
            return null;
        }

        return $this->getChamp($this->parseChampId($offset));
    }

    public function offsetSet($offset, $value): void
    {
        if (is_string($offset) && $value instanceof ChampValueData) {
            $this->setChamp($this->parseChampId($offset), $value);
        }
    }

    public function offsetUnset($offset): void
    {
        if (!is_string($offset)) {
            return;
        }

        unset($this->champs[$this->parseChampId($offset)]);
    }

    /**
     * Extrait l'ID entier depuis une clé de la forme "champ_{id}".
     */
    private function parseChampId(string $offset): int
    {
        return (int) substr($offset, 6); // strlen('champ_') = 6
    }
}

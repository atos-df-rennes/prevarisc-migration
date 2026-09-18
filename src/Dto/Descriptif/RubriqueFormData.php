<?php

namespace App\Dto\Descriptif;

use App\ValueObject\RubriqueEtat;

/**
 * DTO représentant une rubrique dans le formulaire personnalisé.
 *
 * Contient l'état résolu (affichage + ouverture) et les données des champs racines
 * (champs sans parent) de la rubrique.
 *
 * Implémente ArrayAccess pour permettre à Symfony Forms d'utiliser
 * la notation bracket dans property_path (ex: '[champ_123]').
 *
 * @implements \ArrayAccess<array-key, ChampValueData|null>
 */
class RubriqueFormData implements \ArrayAccess
{
    /**
     * @param array<int, ChampValueData> $champs Données des champs racines indexées par champId
     */
    public function __construct(private RubriqueEtat $etat = new RubriqueEtat(), private array $champs = [])
    {
    }

    public function getEtat(): RubriqueEtat
    {
        return $this->etat;
    }

    public function isDisplay(): bool
    {
        return $this->etat->isDisplay();
    }

    public function setDisplay(bool $display): self
    {
        $this->etat = $this->etat->withDisplay($display);

        return $this;
    }

    public function isOpened(): bool
    {
        return $this->etat->isOpened();
    }

    public function setOpened(bool $opened): self
    {
        $this->etat = $this->etat->withOpened($opened);

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

    public function offsetGet($offset): ?ChampValueData
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

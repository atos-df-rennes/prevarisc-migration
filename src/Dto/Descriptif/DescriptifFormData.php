<?php

namespace App\Dto\Descriptif;

/**
 * DTO racine du formulaire personnalisé.
 *
 * Contient les données de toutes les rubriques d'une capsule,
 * indexées par rubriqueId.
 *
 * Implémente ArrayAccess pour permettre à Symfony Forms d'utiliser
 * la notation bracket dans property_path (ex: '[rubrique_480]').
 *
 * @implements \ArrayAccess<string, RubriqueFormData|null>
 */
class DescriptifFormData implements \ArrayAccess
{
    /**
     * Données des rubriques indexées par rubriqueId.
     *
     * @var array<int, RubriqueFormData>
     */
    private $rubriques;

    /**
     * @param array<int, RubriqueFormData> $rubriques
     */
    public function __construct(array $rubriques = [])
    {
        $this->rubriques = $rubriques;
    }

    /**
     * @return array<int, RubriqueFormData>
     */
    public function getRubriques(): array
    {
        return $this->rubriques;
    }

    /**
     * @param array<int, RubriqueFormData> $rubriques
     */
    public function setRubriques(array $rubriques): self
    {
        $this->rubriques = $rubriques;

        return $this;
    }

    public function getRubrique(int $rubriqueId): ?RubriqueFormData
    {
        return $this->rubriques[$rubriqueId] ?? null;
    }

    public function setRubrique(int $rubriqueId, RubriqueFormData $data): self
    {
        $this->rubriques[$rubriqueId] = $data;

        return $this;
    }

    // --- ArrayAccess (clés au format "rubrique_{id}") ---

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }

        return null !== $this->getRubrique($this->parseRubriqueId($offset));
    }

    /**
     * @param mixed $offset
     *
     * @return RubriqueFormData|null
     */
    public function offsetGet($offset)
    {
        if (!is_string($offset)) {
            return null;
        }

        return $this->getRubrique($this->parseRubriqueId($offset));
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (is_string($offset) && $value instanceof RubriqueFormData) {
            $this->setRubrique($this->parseRubriqueId($offset), $value);
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        if (!is_string($offset)) {
            return;
        }

        unset($this->rubriques[$this->parseRubriqueId($offset)]);
    }

    /**
     * Extrait l'ID entier depuis une clé de la forme "rubrique_{id}".
     */
    private function parseRubriqueId(string $offset): int
    {
        return (int) substr($offset, 9); // strlen('rubrique_') = 9
    }
}

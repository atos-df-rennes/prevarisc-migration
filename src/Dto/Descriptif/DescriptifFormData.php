<?php

namespace App\Dto\Descriptif;

/**
 * DTO racine du formulaire personnalisé.
 *
 * Contient les données de toutes les rubriques d'une capsule,
 * indexées par rubriqueId.
 */
class DescriptifFormData
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
}

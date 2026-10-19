<?php

namespace App\ValueObject;

/**
 * État résolu (affichage + ouverture) d'une rubrique, déjà arbitré entre la préférence utilisateur
 * enregistrée (RubriquePreferences) et la valeur par défaut définie en administration.
 *
 * Contrairement à RubriquePreferences, les deux propriétés sont toujours définies : cet objet
 * représente ce qui est effectivement affiché à l'écran (structure Descriptif::buildRubriques())
 * et ce qui est soumis par le formulaire d'édition (RubriqueFormData).
 *
 * Immuable.
 */
final class RubriqueEtat
{
    public function __construct(private readonly bool $display = true, private readonly bool $opened = false)
    {
    }

    public function isDisplay(): bool
    {
        return $this->display;
    }

    public function isOpened(): bool
    {
        return $this->opened;
    }

    public function withDisplay(bool $display): self
    {
        return new self($display, $this->opened);
    }

    public function withOpened(bool $opened): self
    {
        return new self($this->display, $opened);
    }
}

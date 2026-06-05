<?php

namespace App\ValueObject;

/**
 * Préférences d'affichage d'une rubrique pour un utilisateur donné.
 *
 * Immuable — construit une seule fois depuis l'entité DisplayRubrique.
 */
class RubriqueDisplayPref
{
    /** @var bool */
    private $display;

    /** @var bool|null */
    private $opened;

    public function __construct(bool $display, ?bool $opened)
    {
        $this->display = $display;
        $this->opened = $opened;
    }

    public function isDisplay(): bool
    {
        return $this->display;
    }

    public function isOpened(): ?bool
    {
        return $this->opened;
    }
}

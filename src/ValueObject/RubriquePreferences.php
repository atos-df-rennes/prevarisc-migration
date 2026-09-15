<?php

namespace App\ValueObject;

/**
 * Préférences utilisateur enregistrées pour une rubrique (issues de DossierDisplayRubrique
 * ou EtablissementDisplayRubrique).
 *
 * `null` signifie « aucune préférence enregistrée pour cette rubrique » : dans ce cas, la valeur
 * par défaut définie en administration (Rubrique::getDefaultDisplay() / Rubrique::getOuverte())
 * s'applique. Voir RubriqueEtat pour l'état résolu (toujours non-null) construit à partir de ces
 * préférences.
 *
 * Immuable — construit une seule fois depuis l'entité DisplayRubrique.
 */
class RubriquePreferences
{
    public function __construct(private readonly ?bool $display = null, private readonly ?bool $opened = null)
    {
    }

    /**
     * Aucune préférence enregistrée : la résolution retombera systématiquement sur les valeurs
     * par défaut administrées.
     */
    public static function aucune(): self
    {
        return new self();
    }

    public function getDisplay(): ?bool
    {
        return $this->display;
    }

    public function getOpened(): ?bool
    {
        return $this->opened;
    }

    /**
     * Résout la préférence d'affichage, ou $defaut si aucune préférence n'est enregistrée.
     */
    public function resolveDisplay(bool $defaut): bool
    {
        return $this->display ?? $defaut;
    }

    /**
     * Résout la préférence d'ouverture, ou $defaut si aucune préférence n'est enregistrée.
     */
    public function resolveOpened(bool $defaut): bool
    {
        return $this->opened ?? $defaut;
    }
}

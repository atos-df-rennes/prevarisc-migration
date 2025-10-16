<?php

namespace App\Service\Formulaires\Fusion;

use App\Dto\Formulaires\NomFusion;
use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Rubrique;

/**
 * Gère la création d'un champ de fusion en fonction du type de champ et de la relation parent-enfant.
 * Le champ de fusion peut être simple ou composé d'une boucle. Il peux également ne peut être défini.
 */
final class ChampCreator
{
    public static function make(CapsuleRubrique $formulaire, Rubrique $rubrique, Champ $champ): ?NomFusion
    {
        $typeChamp = $champ->getType();
        $nomFormulaire = $formulaire->getNomInterne();

        if (null === $typeChamp) {
            throw new \InvalidArgumentException("Le type du champ est invalide.");
        }

        if (null === $nomFormulaire) {
            throw new \InvalidArgumentException("Le nom du formulaire est invalide.");
        }

        /*
         * Si le champ parent n'est pas un tableau, il n'a pas de champ de fusion. Ce sont ses enfants qui en ont.
         * Si le champ parent est un tableau, les champs enfants n'ont pas de champ de fusion.
         */
        if (
            ('Parent' === $typeChamp->getType() && !$champ->getTableau())
            || (null !== $champ->getParent() && $champ->getParent()->getTableau())
        ) {
            return null;
        }

        // Cas d'un champ tableau, on définit une boucle pour le document ODT.
        if ($champ->getTableau()) {
            $valeur = ChampNamer::getLoopName(implode(' ', [
                $formulaire->getNomInterne(),
                $rubrique->getNom(),
                $champ->getNom(),
                'valeurs'
            ]));
            $enfants = [];

            foreach ($champ->getEnfants() as $enfant) {
                $options = [(string) $rubrique->getNom(), (string) $champ->getNom(), (string) $enfant->getIdx()];

                $enfants[] = new NomFusion(ChampNamer::getFullFusionName($nomFormulaire, $options));
            }

            return new NomFusion($valeur, $enfants);
        }

        /**
         * Cas d'un champ classique, on fusionne les options.
         * En particulier, on ajoute le nom du champ parent si nécessaire.
         */
        $options = [(string) $rubrique->getNom()];

        if (null !== $champ->getParent()) {
            $options[] = (string) $champ->getParent()->getNom();
        }

        $options[] = (string) $champ->getNom();

        $valeur = ChampNamer::getFullFusionName($nomFormulaire, $options);

        return new NomFusion($valeur);
    }
}

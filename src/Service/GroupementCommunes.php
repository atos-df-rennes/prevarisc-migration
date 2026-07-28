<?php

namespace App\Service;

use App\Entity\Groupement;
use App\Repository\EtablissementInformationsRepository;

final class GroupementCommunes
{
    public function __construct(private readonly EtablissementInformationsRepository $etablissementInformationsRepository)
    {
    }

    /**
     * Réaffecte les préventionnistes d'un groupement à tous ses établissements, cellules et sites.
     * Cette méthode supprime d'abord toutes les affectations existantes pour les communes du groupement,
     * puis réaffecte les préventionnistes du groupement aux entités concernées.
     *
     * @param Groupement $groupement le groupement dont les préventionnistes doivent être réaffectés
     */
    public function reaffectationPreventioniste(Groupement $groupement): void
    {
        $this->clearPreventionnistes($groupement);
        $this->addPreventionnistes($groupement);
    }

    /**
     * Supprime les préventionnistes associés aux communes du groupement.
     *
     * @param Groupement $groupement le groupement dont les communes sont utilisées pour supprimer les préventionnistes
     */
    private function clearPreventionnistes(Groupement $groupement): void
    {
        $communes = $groupement->getCommunes();
        if ($communes->isEmpty()) {
            return;
        }

        $communesNumInsee = [];
        foreach ($communes as $commune) {
            $communesNumInsee[] = $commune->getId();
        }

        $this->etablissementInformationsRepository->deletePreventionnistesByCommunes($communesNumInsee);
        $this->etablissementInformationsRepository->deleteCellulesPreventionnistesByCommunes($communesNumInsee);
        $this->etablissementInformationsRepository->deleteSitesPreventionnistesByCommunes($communesNumInsee);
    }

    /**
     * Ajoute les préventionnistes aux établissements, cellules et sites du groupement.
     *
     * @param Groupement $groupement le groupement contenant les préventionnistes à assigner
     */
    private function addPreventionnistes(Groupement $groupement): void
    {
        $libelle = $groupement->getLibelle();
        if (null === $libelle) {
            return;
        }

        $this->etablissementInformationsRepository->insertEtablissementsPreventionnistes($libelle);
        $this->etablissementInformationsRepository->insertCellulesPreventionnistes($libelle);
        $this->etablissementInformationsRepository->insertSitesPreventionnistes($libelle);
    }
}

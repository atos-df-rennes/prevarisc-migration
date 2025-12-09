<?php

namespace App\Service;

use App\Entity\Groupement;
use App\Entity\Utilisateur;
use App\Repository\EtablissementInformationsRepository;
use Doctrine\ORM\EntityManagerInterface;

final class GroupementCommunes
{
    /** @var EtablissementInformationsRepository */
    private $etablissementInformationsRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        EtablissementInformationsRepository $etablissementInformationsRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->etablissementInformationsRepository = $etablissementInformationsRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Supprime les liens entre les établissements et leurs préventionnistes associés
     * pour les communes appartenant à un groupement donné.
     *
     * @param Groupement $groupement le groupement contenant les communes à traiter
     */
    public function deleteEtablissementsPreventioniste(Groupement $groupement): void
    {
        $communes = $groupement->getCommunes();
        if ($communes->isEmpty()) {
            return;
        }

        $communesNumInsee = [];
        foreach ($communes as $commune) {
            $communesNumInsee[] = $commune->getId();
        }

        $etablissementInformations = $this->etablissementInformationsRepository->findByCommunes($communesNumInsee, true);

        foreach ($etablissementInformations as $etablissementInfo) {
            $etablissementInfo->getPreventionnistes()->clear();
        }

        $this->entityManager->flush();
    }

    /**
     * Supprime les liens entre les cellules et leurs préventionnistes associés
     * pour les communes appartenant à un groupement donné.
     *
     * @param Groupement $groupement le groupement contenant les communes à traiter
     */
    public function deleteCellulesPreventioniste(Groupement $groupement): void
    {
        $communes = $groupement->getCommunes();
        if ($communes->isEmpty()) {
            return;
        }

        $communesNumInsee = [];
        foreach ($communes as $commune) {
            $communesNumInsee[] = $commune->getId();
        }

        $etablissementInformations = $this->etablissementInformationsRepository->findCellulesByCommunes($communesNumInsee);
        $etablissementInformationsASupprimer = $this->etablissementInformationsRepository->filterLastInformations($etablissementInformations);

        foreach ($etablissementInformationsASupprimer as $etablissementInfo) {
            $etablissementInfo->getPreventionnistes()->clear();
        }

        $this->entityManager->flush();
    }

    /**
     * Supprime les liens entre les sites et leurs préventionnistes associés
     * pour les communes appartenant à un groupement donné.
     *
     * @param Groupement $groupement le groupement contenant les communes à traiter
     */
    public function deleteSitesPreventioniste(Groupement $groupement): void
    {
        $communes = $groupement->getCommunes();
        if ($communes->isEmpty()) {
            return;
        }

        $communesNumInsee = [];
        foreach ($communes as $commune) {
            $communesNumInsee[] = $commune->getId();
        }

        $etablissementInformations = $this->etablissementInformationsRepository->findSitesByCommunes($communesNumInsee);
        $etablissementInformationsASupprimer = $this->etablissementInformationsRepository->filterLastInformations($etablissementInformations);

        foreach ($etablissementInformationsASupprimer as $etablissementInfo) {
            $etablissementInfo->getPreventionnistes()->clear();
        }

        $this->entityManager->flush();
    }

    /**
     * Ajoute les préventionnistes aux établissements du groupement.
     *
     * @param Groupement $groupement le groupement contenant les préventionnistes à assigner
     */
    public function addEtablissementsPreventioniste(Groupement $groupement): void
    {
        $data = $this->etablissementInformationsRepository->getEtablissementsWithPreventionnistes($groupement->getLibelle());

        foreach ($data as $item) {
            $etablissementInfo = $item['etablissementInformations'];
            $utilisateurId = $item['utilisateurId'];

            /** @var Utilisateur $utilisateur */
            $utilisateur = $this->entityManager->getReference(Utilisateur::class, $utilisateurId);
            $etablissementInfo->addPreventionniste($utilisateur);
        }

        $this->entityManager->flush();
    }

    /**
     * Ajoute les préventionnistes aux cellules du groupement.
     *
     * @param Groupement $groupement le groupement contenant les préventionnistes à assigner
     */
    public function addCellulesPreventioniste(Groupement $groupement): void
    {
        $data = $this->etablissementInformationsRepository->getCellulesWithPreventionnistes($groupement->getLibelle());

        foreach ($data as $item) {
            $etablissementInfo = $item['etablissementInformations'];
            $utilisateurId = $item['utilisateurId'];

            /** @var Utilisateur $utilisateur */
            $utilisateur = $this->entityManager->getReference(Utilisateur::class, $utilisateurId);
            $etablissementInfo->addPreventionniste($utilisateur);
        }

        $this->entityManager->flush();
    }

    /**
     * Ajoute les préventionnistes aux sites du groupement.
     *
     * @param Groupement $groupement le groupement contenant les préventionnistes à assigner
     */
    public function addSitesPreventioniste(Groupement $groupement): void
    {
        $data = $this->etablissementInformationsRepository->getSitesWithPreventionnistes($groupement->getLibelle());

        foreach ($data as $item) {
            $etablissementInfo = $item['etablissementInformations'];
            $utilisateurId = $item['utilisateurId'];

            /** @var Utilisateur $utilisateur */
            $utilisateur = $this->entityManager->getReference(Utilisateur::class, $utilisateurId);
            $etablissementInfo->addPreventionniste($utilisateur);
        }

        $this->entityManager->flush();
    }

    public function reaffectationPreventioniste(Groupement $groupement): void
    {
        $this->deleteEtablissementsPreventioniste($groupement);
        $this->deleteCellulesPreventioniste($groupement);
        $this->deleteSitesPreventioniste($groupement);

        $this->addEtablissementsPreventioniste($groupement);
        $this->addCellulesPreventioniste($groupement);
        $this->addSitesPreventioniste($groupement);
    }
}

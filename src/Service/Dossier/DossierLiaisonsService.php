<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DossierLiaisonsService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DossierRepository */
    private $dossierRepository;

    public function __construct(EntityManagerInterface $entityManager, DossierRepository $dossierRepository)
    {
        $this->entityManager = $entityManager;
        $this->dossierRepository = $dossierRepository;
    }

    /**
     * Retourne tous les dossiers liés au dossier donné (les deux côtés de la relation).
     *
     * @return Dossier[]
     */
    public function getLiaisons(Dossier $dossier): array
    {
        $lies = array_merge(
            $dossier->getDossiersLies()->toArray(),
            $dossier->getDossiersAyantLie()->toArray()
        );

        $unique = [];
        foreach ($lies as $lie) {
            $unique[$lie->getId()] = $lie;
        }

        return array_values($unique);
    }

    /**
     * Retourne les dossiers des mêmes établissements qui ne sont pas encore liés.
     *
     * @return Dossier[]
     */
    public function getDossiersDisponibles(Dossier $dossier): array
    {
        $excludedIds = [];
        if (null !== $dossier->getId()) {
            $excludedIds[] = $dossier->getId();
        }
        foreach ($this->getLiaisons($dossier) as $lie) {
            if (null !== $lie->getId()) {
                $excludedIds[] = $lie->getId();
            }
        }

        return $this->dossierRepository->findByEtablissementsExcluding($dossier, $excludedIds);
    }

    /**
     * Lie plusieurs dossiers au dossier donné.
     *
     * @param Dossier[] $dossiersToAdd
     */
    public function addLiaisons(Dossier $dossier, array $dossiersToAdd): void
    {
        foreach ($dossiersToAdd as $dossierToAdd) {
            $dossier->addDossiersLies($dossierToAdd);
        }

        $this->entityManager->flush();
    }

    /**
     * Supprime le lien entre deux dossiers (peu importe le sens de la relation).
     */
    public function removeLiaison(Dossier $dossier, Dossier $dossierLie): void
    {
        if ($dossier->getDossiersLies()->contains($dossierLie)) {
            $dossier->removeDossiersLies($dossierLie);
        } elseif ($dossierLie->getDossiersLies()->contains($dossier)) {
            $dossierLie->removeDossiersLies($dossier);
        }

        $this->entityManager->flush();
    }
}

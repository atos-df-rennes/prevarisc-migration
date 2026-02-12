<?php

namespace App\Service;

use App\Entity\Dossier as DossierEntity;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;

final class Dossier
{
    /** @var DossierRepository */
    private $dossierRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        DossierRepository $dossierRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->dossierRepository = $dossierRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Rétablit un dossier supprimé en remettant à null les champs de suppression.
     */
    public function restore(int $idDossier): void
    {
        $dossier = $this->dossierRepository->find($idDossier);

        if (!$dossier instanceof DossierEntity) {
            throw new \InvalidArgumentException(sprintf("Le dossier avec l'ID %d n'existe pas.", $idDossier));
        }

        $dossier->setDateSuppression(null);
        $dossier->setDeletedBy(null);

        $this->entityManager->flush();
    }
}

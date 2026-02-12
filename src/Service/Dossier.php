<?php

namespace App\Service;

use App\Entity\Dossier as DossierEntity;
use Doctrine\ORM\EntityManagerInterface;

final class Dossier
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Rétablit un dossier supprimé en remettant à null les champs de suppression.
     */
    public function restore(DossierEntity $dossier): void
    {
        $dossier->setDateSuppression(null);
        $dossier->setDeletedBy(null);

        $this->entityManager->flush();
    }
}

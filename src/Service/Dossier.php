<?php

namespace App\Service;

use App\Entity\Dossier as DossierEntity;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;
use App\Repository\DossierRepository;
use Symfony\Component\Security\Core\Security;

final class Dossier
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var Security */
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        DossierRepository $dossierRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->dossierRepository = $dossierRepository;
        $this->security = $security;
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

    public function nombreNouvellesPiecesJointes(DossierEntity $dossier): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof Utilisateur) {
            return 0;
        }

        $dateDerniereVisitePage = $user->getDerniereVisitePiecesDossier();

        if (null === $dateDerniereVisitePage) {
            return 0;
        }

        return $this->dossierRepository->findNombreNouvellesPieces($dossier, $dateDerniereVisitePage);
    }
}

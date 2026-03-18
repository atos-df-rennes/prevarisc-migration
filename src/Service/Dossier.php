<?php

namespace App\Service;

use App\Entity\Dossier as DossierEntity;
use App\Entity\Utilisateur;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

final class Dossier
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var Security */
    private $security;

    /** @var TexteApplicable */
    private $texteApplicableService;

    public function __construct(
        EntityManagerInterface $entityManager,
        DossierRepository $dossierRepository,
        Security $security,
        TexteApplicable $texteApplicableService
    ) {
        $this->entityManager = $entityManager;
        $this->dossierRepository = $dossierRepository;
        $this->security = $security;
        $this->texteApplicableService = $texteApplicableService;
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

    /**
     * Récupère les textes applicables d'un dossier et les trie par type.
     *
     * @return array{}|array{int, array{'titre': ?string, 'textes': array{int, ?string}}}
     */
    public function getTextesApplicables(int $idDossier): array
    {
        $textesApplicablesData = $this->dossierRepository->getTextesApplicablesByType($idDossier);
        if (null === $textesApplicablesData) {
            return [];
        }

        $textesApplicablesValues = $textesApplicablesData->getTextesApplicables()->getValues();

        return $this->texteApplicableService->trierParType($textesApplicablesValues);
    }
}

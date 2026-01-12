<?php

namespace App\Service;

use App\Entity\Dossier as DossierEntity;
use App\Entity\Utilisateur;
use App\Repository\DossierRepository;
use Symfony\Component\Security\Core\Security;

final class Dossier
{
    /** @var DossierRepository */
    private $dossierRepository;

    /** @var Security */
    private $security;

    public function __construct(DossierRepository $dossierRepository, Security $security)
    {
        $this->dossierRepository = $dossierRepository;
        $this->security = $security;
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

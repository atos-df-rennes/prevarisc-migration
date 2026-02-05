<?php

namespace App\Service\Platau;

use App\Entity\PieceJointe;
use App\Repository\PieceJointeStatutRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion de l'export des pièces jointes vers Plat'AU.
 */
class PieceJointeExportService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PieceJointeStatutRepository */
    private $statutRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PieceJointeStatutRepository $statutRepository
    ) {
        $this->entityManager = $entityManager;
        $this->statutRepository = $statutRepository;
    }

    /**
     * Marque les pièces jointes sélectionnées comme "à exporter vers Plat'AU".
     *
     * Change le statut des pièces jointes vers 'to_be_exported' pour
     * permettre leur export ultérieur vers la plateforme Plat'AU.
     *
     * @param iterable<PieceJointe> $piecesJointes Pièces jointes à marquer (Collection ou array)
     */
    public function markForExport(iterable $piecesJointes): void
    {
        $count = is_countable($piecesJointes) ? count($piecesJointes) : iterator_count($piecesJointes);
        if (0 === $count) {
            return;
        }

        // Récupère le statut "to_be_exported" (doit exister en base)
        $statutExport = $this->statutRepository->findOneBy(['nomStatut' => 'to_be_exported']);

        if (!$statutExport) {
            throw new \RuntimeException('Le statut Plat\'AU "to_be_exported" n\'existe pas en base de données. Vérifier la table piecejointestatut.');
        }

        foreach ($piecesJointes as $pieceJointe) {
            $pieceJointe->setStatut($statutExport);
        }

        $this->entityManager->flush();
    }
}

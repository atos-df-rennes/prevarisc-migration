<?php

namespace App\Service\Platau;

use App\Entity\PieceJointe;
use App\Enum\PieceJointeStatutEnum;
use App\Repository\PieceJointeStatutRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion de l'export des pièces jointes vers Plat'AU.
 */
final class PieceJointeExportService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PieceJointeStatutRepository $statutRepository)
    {
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
        $statutExport = $this->statutRepository->findOneBy(['nomStatut' => PieceJointeStatutEnum::EnAttenteEnvoi->value]);

        if (!$statutExport) {
            throw new \RuntimeException(sprintf('Le statut Plat\'AU "%s" n\'existe pas en base de données. Vérifier la table piecejointestatut.', PieceJointeStatutEnum::EnAttenteEnvoi->value));
        }

        foreach ($piecesJointes as $pieceJointe) {
            $pieceJointe->setStatut($statutExport);
        }

        $this->entityManager->flush();
    }
}

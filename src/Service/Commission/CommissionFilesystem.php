<?php

namespace App\Service\Commission;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Service de gestion du filesystem pour les commissions.
 * Gère la création et suppression des dossiers de documents.
 */
final class CommissionFilesystem
{
    public function __construct(private readonly string $documentsRealDataPath, private readonly LoggerInterface $logger, private readonly Filesystem $filesystem)
    {
    }

    public function createCommissionDirectory(int $commissionId): void
    {
        $commissionDir = $this->documentsRealDataPath.$commissionId;

        try {
            $this->filesystem->mkdir($commissionDir);
        } catch (IOException $ioException) {
            $this->logger->error('Impossible de créer le dossier de commission', [
                'commission_id' => $commissionId,
                'path' => $commissionDir,
                'error' => $ioException->getMessage(),
            ]);
        }
    }

    public function removeCommissionDirectory(int $commissionId): void
    {
        $commissionDir = $this->documentsRealDataPath.$commissionId;

        try {
            $this->filesystem->remove($commissionDir);
        } catch (IOException $ioException) {
            $this->logger->error('Impossible de supprimer le dossier de commission', [
                'commission_id' => $commissionId,
                'path' => $commissionDir,
                'error' => $ioException->getMessage(),
            ]);
        }
    }
}

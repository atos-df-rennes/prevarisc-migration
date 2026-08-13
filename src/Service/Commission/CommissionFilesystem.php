<?php

namespace App\Service\Commission;

use App\Utils\PathNormalizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Service de gestion du filesystem pour les commissions.
 * Gère la création et suppression des dossiers de documents.
 */
final class CommissionFilesystem
{
    /** @var string */
    private $documentsRealDataPath;

    /** @var LoggerInterface */
    private $logger;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(string $documentsRealDataPath, LoggerInterface $logger, Filesystem $filesystem)
    {
        $this->documentsRealDataPath = $documentsRealDataPath;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
    }

    public function createCommissionDirectory(int $commissionId): void
    {
        $commissionDir = PathNormalizer::join($this->documentsRealDataPath, (string) $commissionId);

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
        $commissionDir = PathNormalizer::join($this->documentsRealDataPath, (string) $commissionId);

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

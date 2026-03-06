<?php

declare(strict_types=1);

namespace App\Service\Commission;

use App\Entity\Commission;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Gère l'upload et la suppression du document type (Compte Rendu) d'une commission.
 */
final class CommissionDocumentCrService
{
    /** @var string */
    private $documentsCommissionRealDataPath;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Filesystem */
    private $filesystem;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        string $documentsCommissionRealDataPath,
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        $this->documentsCommissionRealDataPath = $documentsCommissionRealDataPath;
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Upload le document type (Compte Rendu) d'une commission.
     * Seuls les fichiers .odt sont acceptés.
     * Si un document existait, il est remplacé.
     *
     * @throws \InvalidArgumentException Si l'extension du fichier n'est pas .odt
     * @throws FileException             Si le déplacement du fichier échoue
     */
    public function upload(Commission $commission, UploadedFile $file): void
    {
        $originalFilename = $file->getClientOriginalName();
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if ('odt' !== $extension) {
            throw new \InvalidArgumentException(sprintf('Extension non supportée : ".%s". Seuls les fichiers .odt sont acceptés.', $extension));
        }

        $this->ensureDirectoryExists();

        $existingDocument = $commission->getDocumentCr();
        if (null !== $existingDocument) {
            $this->removeFile($existingDocument);
        }

        $destination = $this->documentsCommissionRealDataPath.$originalFilename;

        try {
            $file->move($this->documentsCommissionRealDataPath, $originalFilename);
        } catch (FileException $fileException) {
            $this->logger->error('Échec du déplacement du fichier document CR commission', [
                'commission_id' => $commission->getId(),
                'filename' => $originalFilename,
                'destination' => $destination,
                'error' => $fileException->getMessage(),
            ]);

            throw $fileException;
        }

        $commission->setDocumentCr($originalFilename);
        $this->entityManager->flush();
    }

    /**
     * Supprime le document type (Compte Rendu) d'une commission.
     * Sans effet si aucun document n'est défini.
     */
    public function delete(Commission $commission): void
    {
        $existingDocument = $commission->getDocumentCr();

        if (null === $existingDocument) {
            return;
        }

        $this->removeFile($existingDocument);

        $commission->setDocumentCr(null);
        $this->entityManager->flush();
    }

    private function ensureDirectoryExists(): void
    {
        if (!$this->filesystem->exists($this->documentsCommissionRealDataPath)) {
            try {
                $this->filesystem->mkdir($this->documentsCommissionRealDataPath);
            } catch (IOException $ioException) {
                $this->logger->error('Impossible de créer le dossier documents_commission', [
                    'path' => $this->documentsCommissionRealDataPath,
                    'error' => $ioException->getMessage(),
                ]);
            }
        }
    }

    private function removeFile(string $filename): void
    {
        $path = $this->documentsCommissionRealDataPath.$filename;

        if (!$this->filesystem->exists($path)) {
            return;
        }

        try {
            $this->filesystem->remove($path);
        } catch (IOException $ioException) {
            $this->logger->warning('Impossible de supprimer le fichier document CR commission', [
                'path' => $path,
                'error' => $ioException->getMessage(),
            ]);
        }
    }
}

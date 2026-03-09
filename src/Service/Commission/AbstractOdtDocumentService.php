<?php

declare(strict_types=1);

namespace App\Service\Commission;

use App\Utils\File;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Classe de base pour la gestion des fichiers ODT (upload/suppression).
 * Fournit les opérations communes sur le système de fichiers.
 */
abstract class AbstractOdtDocumentService
{
    /** @var Filesystem */
    protected $filesystem;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Retourne le chemin du répertoire de stockage (avec slash final).
     */
    abstract protected function getStoragePath(): string;

    /**
     * Déplace le fichier uploadé dans le répertoire de stockage sous le nom fourni.
     *
     * @throws FileException Si le déplacement échoue
     */
    protected function moveUploadedFile(UploadedFile $file, string $storedFilename): void
    {
        $this->ensureDirectoryExists();

        try {
            $file->move($this->getStoragePath(), $storedFilename);
        } catch (FileException $fileException) {
            $this->logger->error('Échec du déplacement du fichier ODT', [
                'filename' => $storedFilename,
                'destination' => $this->getStoragePath().$storedFilename,
                'error' => $fileException->getMessage(),
            ]);

            throw $fileException;
        }
    }

    /**
     * Supprime un fichier du répertoire de stockage.
     * Sans effet si le fichier n'existe pas.
     */
    protected function removeFile(string $storedFilename): void
    {
        $path = $this->getStoragePath().File::sanitize($storedFilename);

        if (!$this->filesystem->exists($path)) {
            return;
        }

        try {
            $this->filesystem->remove($path);
        } catch (IOException $ioException) {
            $this->logger->warning('Impossible de supprimer le fichier ODT', [
                'path' => $path,
                'error' => $ioException->getMessage(),
            ]);
        }
    }

    /**
     * Vérifie si un fichier stocké existe physiquement sur le disque.
     */
    protected function storedFileExists(string $storedFilename): bool
    {
        return $this->filesystem->exists($this->getStoragePath().File::sanitize($storedFilename));
    }

    private function ensureDirectoryExists(): void
    {
        if (!$this->filesystem->exists($this->getStoragePath())) {
            try {
                $this->filesystem->mkdir($this->getStoragePath());
            } catch (IOException $ioException) {
                $this->logger->error('Impossible de créer le répertoire de stockage ODT', [
                    'path' => $this->getStoragePath(),
                    'error' => $ioException->getMessage(),
                ]);
            }
        }
    }
}

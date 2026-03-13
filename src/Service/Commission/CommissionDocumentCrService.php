<?php

declare(strict_types=1);

namespace App\Service\Commission;

use App\Entity\Commission;
use App\Utils\File;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Gère l'upload et la suppression du document type (Compte Rendu) d'une commission.
 */
final class CommissionDocumentCrService extends AbstractOdtDocumentService
{
    /** @var string */
    private $documentsCommissionRealDataPath;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        string $documentsCommissionRealDataPath,
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        parent::__construct($filesystem, $logger);
        $this->documentsCommissionRealDataPath = $documentsCommissionRealDataPath;
        $this->entityManager = $entityManager;
    }

    protected function getStoragePath(): string
    {
        return $this->documentsCommissionRealDataPath;
    }

    /**
     * Vérifie si le document CR de la commission est accessible sur le disque.
     */
    public function documentCrExists(Commission $commission): bool
    {
        $doc = $commission->getDocumentCr();

        if (null === $doc) {
            return false;
        }

        return $this->storedFileExists($doc);
    }

    /**
     * Upload le document type (Compte Rendu) d'une commission.
     * La validation du type de fichier est assurée par CommissionDocumentCrType.
     * Si un document existait, il est remplacé.
     *
     * @throws FileException Si le déplacement du fichier échoue
     */
    public function upload(Commission $commission, UploadedFile $file): void
    {
        $originalFilename = File::sanitize($file->getClientOriginalName());

        $existingDocument = $commission->getDocumentCr();
        if (null !== $existingDocument) {
            $this->removeFile($existingDocument);
        }

        $this->moveUploadedFile($file, $originalFilename);

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
}

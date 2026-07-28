<?php

declare(strict_types=1);

namespace App\Service\Commission;

use App\Entity\CommissionMembre;
use App\Enum\CommissionMembreCourrierType;
use App\Utils\File;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Gère l'upload et la suppression des courriers types d'un membre de commission.
 *
 * Convention de nommage des fichiers : {idMembre}{type}_{filename}.odt
 * (ex: 42CONVOCATIONVISITE_convocation.odt)
 *
 * Le champ en base de données ne stocke que le nom original du fichier.
 */
final class CommissionMembreCourrierService extends AbstractOdtDocumentService
{
    public function __construct(
        private readonly string $courriersMembresRealDataPath,
        private readonly EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        parent::__construct($filesystem, $logger);
    }

    protected function getStoragePath(): string
    {
        return $this->courriersMembresRealDataPath;
    }

    /**
     * Vérifie si le courrier d'un type donné est accessible sur le disque.
     *
     * @throws \InvalidArgumentException Si le type est invalide
     */
    public function courrierExists(CommissionMembre $membre, string $type): bool
    {
        if (!CommissionMembreCourrierType::isValid($type)) {
            throw new \InvalidArgumentException(sprintf('Type de courrier invalide : "%s"', $type));
        }

        $filename = $this->getCourrierFilename($membre, $type);

        if (null === $filename) {
            return false;
        }

        return $this->storedFileExists($this->buildStoredFilename($membre->getId(), $type, $filename));
    }

    /**
     * Upload un courrier type pour un membre de commission.
     * Si un courrier du même type existait, il est remplacé.
     *
     * @throws \InvalidArgumentException Si le type est invalide
     * @throws FileException             Si le déplacement du fichier échoue
     */
    public function upload(CommissionMembre $membre, string $type, UploadedFile $file): void
    {
        if (!CommissionMembreCourrierType::isValid($type)) {
            throw new \InvalidArgumentException(sprintf('Type de courrier invalide : "%s"', $type));
        }

        $originalFilename = File::sanitize($file->getClientOriginalName());
        $storedFilename = $this->buildStoredFilename($membre->getId(), $type, $originalFilename);

        $existingFilename = $this->getCourrierFilename($membre, $type);
        if (null !== $existingFilename) {
            $existingStoredFilename = $this->buildStoredFilename($membre->getId(), $type, $existingFilename);
            if ($existingStoredFilename !== $storedFilename) {
                $this->removeFile($existingStoredFilename);
            }
        }

        $this->moveUploadedFile($file, $storedFilename);

        $this->setCourrierFilename($membre, $type, $originalFilename);
        $this->entityManager->flush();
    }

    /**
     * Supprime tous les fichiers ODT associés à un membre, sans toucher la base de données.
     * À utiliser après la suppression de l'entité membre. Ceci nécessite l'utilisation séparée de l'id qui est nullifié après suppression.
     */
    public function deleteAllFiles(CommissionMembre $membre, int $membreId): void
    {
        foreach (CommissionMembreCourrierType::ALL as $type) {
            $filename = $this->getCourrierFilename($membre, $type);
            if (null !== $filename) {
                $this->removeFile($this->buildStoredFilename($membreId, $type, $filename));
            }
        }
    }

    /**
     * Supprime un courrier type d'un membre de commission.
     * Sans effet si aucun courrier n'est défini pour ce type.
     *
     * @throws \InvalidArgumentException Si le type est invalide
     */
    public function delete(CommissionMembre $membre, string $type): void
    {
        if (!CommissionMembreCourrierType::isValid($type)) {
            throw new \InvalidArgumentException(sprintf('Type de courrier invalide : "%s"', $type));
        }

        $existingFilename = $this->getCourrierFilename($membre, $type);

        if (null === $existingFilename) {
            return;
        }

        $storedFilename = $this->buildStoredFilename($membre->getId(), $type, $existingFilename);
        $this->removeFile($storedFilename);

        $this->setCourrierFilename($membre, $type, null);
        $this->entityManager->flush();
    }

    /**
     * Construit le nom du fichier tel qu'il est stocké sur le disque.
     * Convention : {idMembre}{type}_{originalFilename}.
     */
    private function buildStoredFilename(?int $membreId, string $type, string $originalFilename): string
    {
        if (null === $membreId) {
            throw new \InvalidArgumentException("L'identifiant du membre n'a pas pu être récupéré. Impossible de déterminer le nom du fichier.");
        }

        return $membreId.$type.'_'.$originalFilename;
    }

    /**
     * Retourne le nom de fichier stocké en base pour le type donné.
     */
    private function getCourrierFilename(CommissionMembre $membre, string $type): ?string
    {
        return match ($type) {
            CommissionMembreCourrierType::CONVOCATIONVISITE => $membre->getCourrierConvocationVisite(),
            CommissionMembreCourrierType::CONVOCATIONSALLE => $membre->getCourrierConvocationSalle(),
            CommissionMembreCourrierType::ODJ => $membre->getCourrierOrdreDuJour(),
            CommissionMembreCourrierType::PV => $membre->getCourrierProcesVerbal(),
            default => null,
        };
    }

    /**
     * Met à jour le champ du membre correspondant au type donné.
     */
    private function setCourrierFilename(CommissionMembre $membre, string $type, ?string $filename): void
    {
        switch ($type) {
            case CommissionMembreCourrierType::CONVOCATIONVISITE:
                $membre->setCourrierConvocationVisite($filename);
                break;
            case CommissionMembreCourrierType::CONVOCATIONSALLE:
                $membre->setCourrierConvocationSalle($filename);
                break;
            case CommissionMembreCourrierType::ODJ:
                $membre->setCourrierOrdreDuJour($filename);
                break;
            case CommissionMembreCourrierType::PV:
                $membre->setCourrierProcesVerbal($filename);
                break;
        }
    }
}

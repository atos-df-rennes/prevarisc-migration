<?php

namespace App\Service;

use App\Constant\PlacementEtablissementPj;
use App\Entity\Etablissement as EtablissementEntity;
use App\Entity\EtablissementPieceJointe;
use App\Entity\PieceJointe;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\PieceJointeRepository;
use App\Service\DataStore\DataStore;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class EtablissementPieceJointeService
{
    public function __construct(private EtablissementPieceJointeRepository $etablissementPJRepository, private DataStore $dataStore, private LoggerInterface $datastoreLogger, private EntityManagerInterface $entityManager, private PieceJointeRepository $pieceJointeRepository, private Image $imageService, private Filesystem $filesystem)
    {
    }

    /**
     * @return array<list{0:EtablissementPieceJointe,1:string,2:string|null}>
     */
    public function getPJImages(int $id): array
    {
        $images = $this->etablissementPJRepository->getPjByType($id, ['.jpg', '.jpeg', '.png', '.gif']);

        return $this->filtrePJs($images);
    }

    /**
     * @return array<list{0:EtablissementPieceJointe,1:string,2:string|null}>
     */
    public function getAutresPJs(int $id): array
    {
        $pjs = $this->etablissementPJRepository->getPjByType($id, ['.jpg', '.jpeg', '.png', '.gif'], true);

        return $this->filtrePJs($pjs);
    }

    /**
     * Ajout d'une pièce jointe pour un établissement.
     *
     * @param int $mise_en_avant 0 = liste, 1 = diaporama, 2 = plans
     *
     * @throws \Exception
     */
    public function addPJ(EtablissementEntity $etablissement, UploadedFile $file, string $name, string $description, int $mise_en_avant): void
    {
        $extension = strtolower('.'.$file->getClientOriginalExtension());

        if (in_array($extension, ['.php', '.php4', '.php5', '.sh', '.ksh', '.csh'], true)) {
            throw new \Exception("Ce type de fichier n'est pas autorisé en upload");
        }

        $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif'];
        $placement = in_array($mise_en_avant, PlacementEtablissementPj::MISES_EN_AVANT, true) && in_array($extension, $imageExtensions, true)
            ? $mise_en_avant
            : PlacementEtablissementPj::LISTE;

        $pieceJointe = new PieceJointe();
        $pieceJointe->setNom('' === $name ? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) : $name);
        $pieceJointe->setExtension($extension);
        $pieceJointe->setDescription('' !== $description ? $description : null);
        $pieceJointe->setDate(new \DateTime());

        $filePath = null;
        $miniaturePath = null;

        $this->entityManager->beginTransaction();

        try {
            $this->entityManager->persist($pieceJointe);
            $this->entityManager->flush();

            $filePath = $this->dataStore->getFilePath($pieceJointe, 'etablissement', $etablissement->getId(), true);
            $file->move(dirname($filePath), basename($filePath));

            $etablissementPJ = new EtablissementPieceJointe();
            $etablissementPJ->setEtablissement($etablissement);
            $etablissementPJ->setPieceJointe($pieceJointe);
            $etablissementPJ->setPlacement($placement);

            $this->entityManager->persist($etablissementPJ);
            $this->entityManager->flush();

            if (
                in_array($extension, $imageExtensions, true)
                && in_array($placement, PlacementEtablissementPj::MISES_EN_AVANT, true)
            ) {
                $miniaturePjHelper = clone $pieceJointe;
                $miniaturePjHelper->setExtension('.jpg');
                $miniaturePath = $this->dataStore->getFilePath($miniaturePjHelper, 'etablissement_miniature', $etablissement->getId(), true);
                $this->imageService->resize($filePath, 450)->save($miniaturePath);

                if (!is_file($miniaturePath)) {
                    throw new \Exception('Cannot create miniature file: '.$miniaturePath);
                }
            }

            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();

            if (null !== $miniaturePath && is_file($miniaturePath)) {
                $this->filesystem->remove($miniaturePath);
            }

            if (null !== $filePath && is_file($filePath)) {
                $this->filesystem->remove($filePath);
            }

            throw new \Exception($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }

    /**
     * Suppression d'une pièce jointe d'un établissement.
     */
    public function deletePJ(EtablissementEntity $etablissement, int $id_pj): void
    {
        $pieceJointe = $this->pieceJointeRepository->find($id_pj);
        if (null === $pieceJointe) {
            return;
        }

        $etablissementPJ = $this->etablissementPJRepository->findOneBy([
            'etablissement' => $etablissement->getId(),
            'pieceJointe' => $id_pj,
        ]);

        if (null === $etablissementPJ) {
            return;
        }

        $filePath = $this->dataStore->getFilePath($pieceJointe, 'etablissement', $etablissement->getId());

        $miniaturePjHelper = clone $pieceJointe;
        $miniaturePjHelper->setExtension('.jpg');

        $miniaturePath = $this->dataStore->getFilePath($miniaturePjHelper, 'etablissement_miniature', $etablissement->getId());

        $this->filesystem->remove(array_filter([$filePath, $miniaturePath], file_exists(...)));

        $this->entityManager->remove($etablissementPJ);
        $this->entityManager->remove($pieceJointe);
        $this->entityManager->flush();
    }

    /**
     * @param EtablissementPieceJointe[] $pjs
     *
     * @return array<list{0:EtablissementPieceJointe,1:string,2:string|null}>
     */
    private function filtrePJs(array $pjs): array
    {
        $store = $this->dataStore;
        $pjsFiltrees = array_filter(
            $pjs,
            function (EtablissementPieceJointe $pj) use ($store): bool {
                $path = '';

                if (null !== $pj->getPieceJointe() && null !== $pj->getEtablissement()) {
                    $path = $store->getFilePath($pj->getPieceJointe(), 'etablissement', $pj->getEtablissement()->getId());
                }

                if ('' === $path || !is_readable($path)) {
                    $piece = $pj->getPieceJointe();
                    $this->datastoreLogger->warning('La pièce jointe n\'est pas accessible en lecture.', [
                        'path' => $path,
                        'filename' => $piece instanceof PieceJointe ? $piece->getNom().$piece->getExtension() : 'Nom de la pièce inconnu',
                    ]);
                }

                return '' !== $path && is_readable($path);
            }
        );

        $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif'];

        return array_map(function (EtablissementPieceJointe $pj) use ($store, $imageExtensions): array {
            $path = '';
            $miniatureUrl = null;

            if (null !== $pj->getPieceJointe() && null !== $pj->getEtablissement()) {
                $path = $store->getURLPath($pj->getPieceJointe(), 'etablissement', $pj->getEtablissement()->getId());

                if (in_array($pj->getPieceJointe()->getExtension(), $imageExtensions, true)) {
                    $miniaturePjHelper = clone $pj->getPieceJointe();
                    $miniaturePjHelper->setExtension('.jpg');
                    $miniatureUrl = $store->getURLPath($miniaturePjHelper, 'etablissement_miniature', $pj->getEtablissement()->getId());
                }
            }

            return [$pj, $path ?? '', $miniatureUrl];
        }, $pjsFiltrees);
    }
}

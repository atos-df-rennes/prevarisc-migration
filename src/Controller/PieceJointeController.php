<?php

namespace App\Controller;

use App\Constant\DataStoreObjectTypes;
use App\Entity\DateCommission;
use App\Repository\DateCommissionRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\PieceJointeRepository;
use App\Service\DataStore\DataStore;
use App\Utils\File;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Port direct de PieceJointeController (Zend).
 */
class PieceJointeController extends AbstractController
{
    public function __construct(private readonly DataStore $dataStore, private readonly EtablissementPieceJointeRepository $etablissementPieceJointeRepository, private readonly PieceJointeRepository $pieceJointeRepository, private readonly DateCommissionRepository $dateCommissionRepository)
    {
    }

    /**
     * Télécharge une pièce jointe d'établissement.
     *
     * Port direct de PieceJointeController::getAction() pour le type "etablissement".
     *
     * @todo: vérifier le droit à appliquer
     */
    #[Route(path: '/piece-jointe/etablissement/{id}/recuperer/{idpj}', name: 'piece_jointe_get_etablissement', methods: ['GET'], requirements: ['id' => '\d+', 'idpj' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function getEtablissement(int $id, int $idpj): Response
    {
        $pieceJointe = $this->pieceJointeRepository->find($idpj);
        if (null === $pieceJointe) {
            throw $this->createNotFoundException('Pièce jointe introuvable.');
        }

        $etablissementPJ = $this->etablissementPieceJointeRepository->findOneBy([
            'etablissement' => $id,
            'pieceJointe' => $idpj,
        ]);

        if (null === $etablissementPJ) {
            throw $this->createNotFoundException('Pièce jointe non rattachée à cet établissement.');
        }

        $filePath = $this->dataStore->getFilePath($pieceJointe, DataStoreObjectTypes::ETABLISSEMENT, $id);

        if (!is_readable($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable ou illisible.');
        }

        $filename = $this->dataStore->getFormattedFilename($pieceJointe, DataStoreObjectTypes::ETABLISSEMENT, $id);

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            File::sanitizeForContentDisposition($filename ?? ($pieceJointe->getNom().$pieceJointe->getExtension()))
        );

        return $response;
    }

    /**
     * Télécharge une pièce jointe de date de commission.
     *
     * Port direct de PieceJointeController::getAction() pour le type "dateCommission".
     *
     * @todo: vérifier le droit à appliquer
     */
    #[Route(path: '/piece-jointe/date-commission/{id}/recuperer/{idpj}', name: 'piece_jointe_get_date_commission', methods: ['GET'], requirements: ['id' => '\d+', 'idpj' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function getDateCommission(int $id, int $idpj): Response
    {
        $pieceJointe = $this->pieceJointeRepository->find($idpj);
        if (null === $pieceJointe) {
            throw $this->createNotFoundException('Pièce jointe introuvable.');
        }

        $dateCommission = $this->dateCommissionRepository->findOneByPieceJointe($id, $idpj);

        if (!$dateCommission instanceof DateCommission) {
            throw $this->createNotFoundException('Pièce jointe non rattachée à cette date de commission.');
        }

        $filePath = $this->dataStore->getFilePath($pieceJointe, DataStoreObjectTypes::DATE_COMMISSION, $id);

        if (!is_readable($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable ou illisible.');
        }

        $filename = $this->dataStore->getFormattedFilename($pieceJointe, DataStoreObjectTypes::DATE_COMMISSION, $id);

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            File::sanitizeForContentDisposition($filename ?? ($pieceJointe->getNom().$pieceJointe->getExtension()))
        );

        return $response;
    }
}

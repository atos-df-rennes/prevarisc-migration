<?php

namespace App\Controller;

use App\Repository\DateCommissionRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\PieceJointeRepository;
use App\Service\DataStore\DataStore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Port direct de PieceJointeController (Zend).
 */
class PieceJointeController extends AbstractController
{
    /** @var DataStore */
    private $dataStore;

    /** @var EtablissementPieceJointeRepository */
    private $etablissementPieceJointeRepository;

    /** @var PieceJointeRepository */
    private $pieceJointeRepository;

    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    public function __construct(
        DataStore $dataStore,
        EtablissementPieceJointeRepository $etablissementPieceJointeRepository,
        PieceJointeRepository $pieceJointeRepository,
        DateCommissionRepository $dateCommissionRepository
    ) {
        $this->dataStore = $dataStore;
        $this->etablissementPieceJointeRepository = $etablissementPieceJointeRepository;
        $this->pieceJointeRepository = $pieceJointeRepository;
        $this->dateCommissionRepository = $dateCommissionRepository;
    }

    /**
     * Télécharge une pièce jointe d'établissement.
     *
     * Port direct de PieceJointeController::getAction() pour le type "etablissement".
     *
     * @Route("/piece-jointe/etablissement/{id}/recuperer/{idpj}", name="piece_jointe_get_etablissement", methods={"GET"}, requirements={"id"="\d+","idpj"="\d+"})
     *
     * @todo: vérifier le droit à appliquer
     *
     * @IsGranted("ROLE_USER")
     */
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

        $filePath = $this->dataStore->getFilePath($pieceJointe, 'etablissement', $id);

        if (!is_readable($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable ou illisible.');
        }

        $filename = $this->dataStore->getFormattedFilename($pieceJointe, 'etablissement', $id);

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename ?? ($pieceJointe->getNom().$pieceJointe->getExtension())
        );

        return $response;
    }

    /**
     * Télécharge une pièce jointe de date de commission.
     *
     * Port direct de PieceJointeController::getAction() pour le type "dateCommission".
     *
     * @Route("/piece-jointe/date-commission/{id}/recuperer/{idpj}", name="piece_jointe_get_date_commission", methods={"GET"}, requirements={"id"="\d+","idpj"="\d+"})
     *
     * @todo: vérifier le droit à appliquer
     *
     * @IsGranted("ROLE_USER")
     */
    public function getDateCommission(int $id, int $idpj): Response
    {
        $pieceJointe = $this->pieceJointeRepository->find($idpj);
        if (null === $pieceJointe) {
            throw $this->createNotFoundException('Pièce jointe introuvable.');
        }

        $dateCommission = $this->dateCommissionRepository->findOneByPieceJointe($id, $idpj);

        if (null === $dateCommission) {
            throw $this->createNotFoundException('Pièce jointe non rattachée à cette date de commission.');
        }

        $filePath = $this->dataStore->getFilePath($pieceJointe, 'dateCommission', $id);

        if (!is_readable($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable ou illisible.');
        }

        $filename = $this->dataStore->getFormattedFilename($pieceJointe, 'dateCommission', $id);

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename ?? ($pieceJointe->getNom().$pieceJointe->getExtension())
        );

        return $response;
    }
}

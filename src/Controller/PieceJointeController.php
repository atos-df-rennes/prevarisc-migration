<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PiecejointeRepository;


class PieceJointeController extends AbstractController
{

/**
     * @Route("/piece-jointe/display-pj-platau", name="display_pj_platau", methods={"POST"})
     */
    public function displayPjPlatau(Request $request, PiecejointeRepository $pieceJointeRepository): Response
    {
        $idDossier = $request->request->get('idDossier');

        if (!$idDossier) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $piecesJointes = $pieceJointeRepository->findWithStatus((int)$idDossier, 'not_exported');

        if (empty($piecesJointes)) {
            return new Response('');
        }

        // Attention : renderView() retourne une string (HTML), alors que render() retourne une Response
        $html = $this->renderView('piece_jointe/export.html.twig', [
            'piecesJointes' => $piecesJointes,
        ]);

        return new Response($html);
    }
}
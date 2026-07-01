<?php

namespace App\Controller\Commission;

use App\Entity\DateCommission;
use App\Entity\PieceJointe;
use App\Repository\PieceJointeRepository;
use App\Service\DataStore\DataStore;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Gestion des pièces jointes d'une date de commission.
 * Extrait de CalendrierDesCommissionsController (REF-CTL-01).
 *
 * @IsGranted("modification_odj")
 */
class CalendrierDesCommissionsPiecesJointesController extends AbstractController
{
    /**
     * @var PieceJointeRepository
     */
    private $pieceJointeRepository;

    /**
     * @var DataStore
     */
    private $dataStore;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        PieceJointeRepository $pieceJointeRepository,
        DataStore $dataStore,
        EntityManagerInterface $entityManager
    ) {
        $this->pieceJointeRepository = $pieceJointeRepository;
        $this->dataStore = $dataStore;
        $this->entityManager = $entityManager;
    }

    /**
     * Affiche les pièces jointes d'une date de commission dans la modale.
     *
     * @Route("/commissions/calendrier/pieces-jointes/{id}", name="calendrier_des_commissions_pieces_jointes", methods={"GET"}, requirements={"id"="\d+"}, options={"expose"=true})
     */
    public function piecesJointes(DateCommission $dateCommission): Response
    {
        $piecesJointes = array_values(array_filter(
            $dateCommission->getPiecesJointes()->toArray(),
            function (PieceJointe $pj) use ($dateCommission): bool {
                $filePath = $this->dataStore->getFilePath($pj, 'dateCommission', (int) $dateCommission->getId());

                return is_readable($filePath);
            }
        ));

        return $this->render('calendrier_des_commissions/pieces_jointes.html.twig', [
            'dateCommission' => $dateCommission,
            'piecesJointes' => $piecesJointes,
        ]);
    }

    /**
     * Retourne le formulaire d'ajout de pièce jointe (chargé en AJAX dans la modale).
     *
     * @Route("/commissions/calendrier/pieces-jointes/{id}/formulaire", name="calendrier_des_commissions_pj_formulaire", methods={"GET"}, requirements={"id"="\d+"}, options={"expose"=true})
     */
    public function piecesJointesForm(DateCommission $dateCommission): Response
    {
        return $this->render('calendrier_des_commissions/pieces_jointes_form.html.twig', [
            'dateCommission' => $dateCommission,
        ]);
    }

    /**
     * Upload d'une pièce jointe pour une date de commission.
     * Retourne un JSON avec le HTML de la nouvelle ligne de tableau.
     *
     * @Route("/commissions/calendrier/pieces-jointes/{id}/ajouter", name="calendrier_des_commissions_pj_ajouter", methods={"POST"}, requirements={"id"="\d+"}, options={"expose"=true})
     */
    public function piecesJointesAdd(Request $request, DateCommission $dateCommission): JsonResponse
    {
        try {
            /* Le token est envoyé en POST par le formulaire et en header X-CSRF-Token par Dropzone */
            /** @var string $csrfToken */
            $csrfToken = $request->request->get('_csrf_token', '') ?: (string) $request->headers->get('X-CSRF-Token', '');
            if (!$this->isCsrfTokenValid('calendrier_commissions_pj', $csrfToken)) {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
            }

            /** @var UploadedFile|null $fichier */
            $fichier = $request->files->get('fichier');
            if (!$fichier instanceof UploadedFile) {
                return new JsonResponse(['success' => false, 'message' => 'Aucun fichier reçu.'], Response::HTTP_BAD_REQUEST);
            }

            $extensionRaw = strrchr($fichier->getClientOriginalName(), '.');
            $extension = strtolower(false !== $extensionRaw ? $extensionRaw : '');
            if ('' === $extension || in_array($extension, ['.php', '.php4', '.php5', '.sh', '.ksh', '.csh'], true)) {
                return new JsonResponse(['success' => false, 'message' => "Ce type de fichier n'est pas autorisé."], Response::HTTP_BAD_REQUEST);
            }

            /** @var string $rawNom */
            $rawNom = $request->request->get('nomFichier', '');
            $nom = '' !== $rawNom ? $rawNom : pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
            /** @var string $description */
            $description = $request->request->get('descriptionFichier', '');

            $pj = new PieceJointe();
            $pj->setExtension($extension);
            $pj->setNom($nom);
            $pj->setDescription('' !== $description ? $description : null);
            $pj->setDate(new \DateTime('today'));

            $this->pieceJointeRepository->add($pj);

            $filePath = $this->dataStore->getFilePath($pj, 'dateCommission', (int) $dateCommission->getId(), true);
            $fichier->move(dirname($filePath), basename($filePath));

            $dateCommission->addPiecesJointe($pj);
            $this->entityManager->flush();

            $rowHtml = $this->renderView('calendrier_des_commissions/_piece_jointe_row.html.twig', [
                'pj' => $pj,
                'dateCommission' => $dateCommission,
            ]);

            return new JsonResponse(['success' => true, 'html' => $rowHtml]);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprime une pièce jointe d'une date de commission.
     *
     * @Route("/commissions/calendrier/pieces-jointes/{id}/supprimer/{idPj}", name="calendrier_des_commissions_pj_supprimer", methods={"DELETE", "POST"}, requirements={"id"="\d+", "idPj"="\d+"}, options={"expose"=true})
     */
    public function piecesJointesDelete(Request $request, DateCommission $dateCommission, int $idPj): JsonResponse
    {
        try {
            /** @var string $csrfToken */
            $csrfToken = $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('calendrier_commissions_pj', $csrfToken)) {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
            }

            $pj = $this->pieceJointeRepository->find($idPj);
            if (null === $pj) {
                return new JsonResponse(['success' => false, 'message' => 'Pièce jointe introuvable.'], Response::HTTP_NOT_FOUND);
            }

            if (!$dateCommission->getPiecesJointes()->contains($pj)) {
                return new JsonResponse(['success' => false, 'message' => "Cette pièce jointe n'appartient pas à cette date de commission."], Response::HTTP_FORBIDDEN);
            }

            $filePath = $this->dataStore->getFilePath($pj, 'dateCommission', (int) $dateCommission->getId());
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $dateCommission->removePiecesJointe($pj);
            $this->pieceJointeRepository->remove($pj);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\DossierPieceJointe;
use App\Entity\Etablissement;
use App\Entity\EtablissementPieceJointe;
use App\Entity\PieceJointe;
use App\Entity\Utilisateur;
use App\Repository\DossierPieceJointeRepository;
use App\Repository\DossierRepository;
use App\Repository\PieceJointeRepository;
use App\Repository\PieceJointeStatutRepository;
use App\Service\DataStore\DataStore;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Port direct de PieceJointeController (type=dossier) et DossierController (pieceJointeAction, getZipAllPjAction).
 *
 * @IsGranted("ROLE_USER")
 */
class DossierPiecesJointesController extends AbstractController
{
    private const FORBIDDEN_EXTENSIONS = ['.php', '.php4', '.php5', '.sh', '.ksh', '.csh'];

    /** @var DataStore */
    private $dataStore;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var DossierPieceJointeRepository */
    private $dossierPieceJointeRepository;

    /** @var PieceJointeRepository */
    private $pieceJointeRepository;

    /** @var PieceJointeStatutRepository */
    private $pieceJointeStatutRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var NotificationService */
    private $notificationService;

    /** @var string */
    private $realDataPath;

    public function __construct(
        DataStore $dataStore,
        DossierRepository $dossierRepository,
        DossierPieceJointeRepository $dossierPieceJointeRepository,
        PieceJointeRepository $pieceJointeRepository,
        PieceJointeStatutRepository $pieceJointeStatutRepository,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService,
        string $realDataPath
    ) {
        $this->dataStore = $dataStore;
        $this->dossierRepository = $dossierRepository;
        $this->dossierPieceJointeRepository = $dossierPieceJointeRepository;
        $this->pieceJointeRepository = $pieceJointeRepository;
        $this->pieceJointeStatutRepository = $pieceJointeStatutRepository;
        $this->entityManager = $entityManager;
        $this->notificationService = $notificationService;
        $this->realDataPath = $realDataPath;
    }

    /**
     * Résout le chemin physique d'une pièce jointe selon que le dossier est Platau ou non.
     *
     * Pour les dossiers Platau, les fichiers sont stockés dans un espace dédié.
     * Pour les dossiers classiques, le DataStore gère la résolution.
     *
     * @param bool $createDir Créer le répertoire si inexistant (utile à l'upload)
     */
    private function resolvePjFilePath(PieceJointe $pj, Dossier $dossier, bool $createDir = false): string
    {
        if (null !== $dossier->getConsultationPlatau()) {
            return $this->realDataPath.'/uploads/pieces-jointes/'.$pj->getId().$pj->getExtension();
        }

        return $this->dataStore->getFilePath($pj, 'dossier', (int) $dossier->getId(), $createDir);
    }

    /**
     * Liste des pièces jointes d'un dossier.
     *
     * Port direct de PieceJointeController::indexAction() (type=dossier).
     *
     * @Route("/dossier/{id}/pieces-jointes", name="dossier_pieces_jointes", methods={"GET"})
     *
     * @param int $id ID du dossier
     */
    public function index(int $id): Response
    {
        $dossier = $this->dossierRepository->find($id);
        if (null === $dossier) {
            throw $this->createNotFoundException('Dossier introuvable.');
        }

        $dossierPjs = $this->dossierPieceJointeRepository->findAllByDossier($id);

        /** @var Utilisateur $user */
        $user = $this->getUser();
        $lastVisit = $this->notificationService->getLastVisitDate($user, NotificationService::PIECES_DOSSIER_NAMESPACE);

        $listePj = [];
        foreach ($dossierPjs as $dpj) {
            $pj = $dpj->getPieceJointe();
            if (null === $pj) {
                continue;
            }

            $filePath = $this->resolvePjFilePath($pj, $dossier);
            if (!is_readable($filePath)) {
                continue;
            }

            $dateNotif = $pj->getDateNotification();
            $isNew = null !== $dateNotif && $dateNotif > $lastVisit;

            $listePj[] = [
                'dpj' => $dpj,
                'isNew' => $isNew,
            ];
        }

        $this->notificationService->setLastVisitDate($user, NotificationService::PIECES_DOSSIER_NAMESPACE);

        $isPlatau = null !== $dossier->getConsultationPlatau();

        return $this->render('dossier/pieces_jointes/index.html.twig', [
            'dossier' => $dossier,
            'listePj' => $listePj,
            'isPlatau' => $isPlatau,
            'verrou' => $dossier->hasVerrou(),
            'displayDownloadButton' => [] !== $listePj,
        ]);
    }

    /**
     * Formulaire d'ajout de pièce jointe (fragment AJAX).
     *
     * Port direct de PieceJointeController::formAction() (type=dossier).
     *
     * @Route("/dossier/{id}/pieces-jointes/formulaire", name="dossier_pieces_jointes_form", methods={"GET"})
     *
     * @param int $id ID du dossier
     */
    public function form(int $id): Response
    {
        $dossier = $this->dossierRepository->find($id);
        if (null === $dossier) {
            throw $this->createNotFoundException('Dossier introuvable.');
        }

        $etablissements = $dossier->getEtablissements()->toArray();

        return $this->render('dossier/pieces_jointes/form.html.twig', [
            'dossier' => $dossier,
            'etablissements' => $etablissements,
        ]);
    }

    /**
     * Ajout d'une pièce jointe (POST AJAX, upload fichier).
     *
     * Port direct de PieceJointeController::addAction() (type=dossier).
     *
     * @Route("/dossier/{id}/pieces-jointes/ajouter", name="dossier_pieces_jointes_add", methods={"POST"})
     *
     * @param int $id ID du dossier
     */
    public function add(int $id, Request $request): JsonResponse
    {
        $rawCsrfAdd = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_pj_add', is_string($rawCsrfAdd) ? $rawCsrfAdd : null)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide.'], 403);
        }

        $dossier = $this->dossierRepository->find($id);
        if (null === $dossier) {
            return $this->json(['success' => false, 'message' => 'Dossier introuvable.'], 404);
        }

        $file = $request->files->get('fichier');
        if (null === $file) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun fichier reçu. Veuillez contacter votre service informatique.',
            ], 400);
        }

        /** @var UploadedFile $file */
        $rawExtension = $file->getClientOriginalExtension();
        if ('' === $rawExtension) {
            return $this->json([
                'success' => false,
                'message' => 'Les fichiers sans extension ne sont pas autorisés.',
            ], 400);
        }

        $extension = '.'.strtolower($rawExtension);
        if (in_array($extension, self::FORBIDDEN_EXTENSIONS, true)) {
            return $this->json([
                'success' => false,
                'message' => "Ce type de fichier n'est pas autorisé en upload",
            ], 400);
        }

        $nomFichierRaw = $request->request->get('nomFichier', '');
        $nomFichier = is_string($nomFichierRaw) ? $nomFichierRaw : '';
        $nom = ('' !== $nomFichier)
            ? $nomFichier
            : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $pj = new PieceJointe();
        $pj->setNom($nom);
        $pj->setExtension($extension);

        $descriptionRaw = $request->request->get('descriptionFichier');
        $pj->setDescription(is_string($descriptionRaw) && '' !== $descriptionRaw ? $descriptionRaw : null);
        $pj->setDate(new \DateTime());
        $pj->setDateNotification(new \DateTime());

        $this->entityManager->persist($pj);
        $this->entityManager->flush();

        $destPath = $this->resolvePjFilePath($pj, $dossier, true);

        try {
            $file->move(dirname($destPath), basename($destPath));
        } catch (\Exception $exception) {
            $this->entityManager->remove($pj);
            $this->entityManager->flush();

            return $this->json([
                'success' => false,
                'message' => 'Impossible de charger la pièce jointe. Veuillez contacter votre service informatique.',
            ], 500);
        }

        $dpj = new DossierPieceJointe();
        $dpj->setPieceJointe($pj);
        $dpj->setDossier($dossier);
        $dpj->setPieceJointeCommission(false);

        $this->entityManager->persist($dpj);

        $etabs = $request->request->get('etab', []);
        if (is_array($etabs)) {
            foreach ($etabs as $etabId) {
                if (!is_numeric($etabId)) {
                    continue;
                }

                /** @var Etablissement $etabRef */
                $etabRef = $this->entityManager->getReference(Etablissement::class, (int) $etabId);
                $etabPj = new EtablissementPieceJointe();
                $etabPj->setEtablissement($etabRef);
                $etabPj->setPieceJointe($pj);
                $etabPj->setPlacement(null);
                $this->entityManager->persist($etabPj);
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'id' => $pj->getId(),
            'extension' => $pj->getExtension(),
        ]);
    }

    /**
     * Téléchargement d'une pièce jointe de dossier.
     *
     * Port direct de PieceJointeController::getAction() (type=dossier).
     *
     * @Route("/dossier/{id}/pieces-jointes/{idpj}/telecharger", name="dossier_pieces_jointes_get", methods={"GET"}, requirements={"idpj"="\d+"})
     *
     * @param int $id   ID du dossier
     * @param int $idpj ID de la pièce jointe
     */
    public function download(int $id, int $idpj): Response
    {
        $dossier = $this->dossierRepository->find($id);
        if (null === $dossier) {
            throw $this->createNotFoundException('Dossier introuvable.');
        }

        $pj = $this->pieceJointeRepository->find($idpj);
        if (null === $pj) {
            throw $this->createNotFoundException('Pièce jointe introuvable.');
        }

        $dpj = $this->dossierPieceJointeRepository->findOneBy([
            'pieceJointe' => $idpj,
            'dossier' => $id,
        ]);
        if (null === $dpj) {
            throw $this->createNotFoundException('Pièce jointe non rattachée à ce dossier.');
        }

        $filePath = $this->resolvePjFilePath($pj, $dossier);
        if (!is_readable($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable ou illisible.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $pj->getNom().$pj->getExtension()
        );

        return $response;
    }

    /**
     * Suppression d'une pièce jointe de dossier (POST AJAX).
     *
     * Port direct de PieceJointeController::deleteAction() (type=dossier).
     *
     * @Route("/dossier/{id}/pieces-jointes/{idpj}/supprimer", name="dossier_pieces_jointes_delete", methods={"POST"}, requirements={"idpj"="\d+"})
     *
     * @param int $id   ID du dossier
     * @param int $idpj ID de la pièce jointe
     */
    public function delete(int $id, int $idpj, Request $request): JsonResponse
    {
        $rawTokenDelete = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_pj_delete_'.$idpj, is_string($rawTokenDelete) ? $rawTokenDelete : null)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        $dossier = $this->dossierRepository->find($id);
        if (null === $dossier) {
            return $this->json(['error' => 'Dossier introuvable.'], 404);
        }

        if ($dossier->hasVerrou()) {
            return $this->json(['error' => 'Le dossier est verrouillé.'], 403);
        }

        $pj = $this->pieceJointeRepository->find($idpj);
        if (null === $pj) {
            return $this->json(['error' => 'Pièce jointe introuvable.'], 404);
        }

        $dpj = $this->dossierPieceJointeRepository->findOneBy([
            'pieceJointe' => $idpj,
            'dossier' => $id,
        ]);
        if (null === $dpj) {
            return $this->json(['error' => 'Pièce jointe non rattachée à ce dossier.'], 404);
        }

        // RG-PJ-010 : Impossible de supprimer une PJ en cours de traitement Plat'AU
        $statut = $pj->getStatut();
        if (null !== $statut && in_array($statut->getNomStatut(), ['to_be_exported', 'exported', 'awaiting_status'], true)) {
            return $this->json([
                'error' => "Impossible de supprimer une pièce jointe en cours de traitement Plat'AU.",
            ], 403);
        }

        $filePath = $this->resolvePjFilePath($pj, $dossier);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Tentative suppression miniature (port direct legacy)
        $miniaturePath = $this->dataStore->getFilePath($pj, 'etablissement_miniature', (int) $dossier->getId());
        if (file_exists($miniaturePath)) {
            @unlink($miniaturePath);
        }

        $this->entityManager->remove($dpj);
        $this->entityManager->remove($pj);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Vérifie qu'une pièce jointe existe physiquement après upload (POST AJAX).
     *
     * Port direct de PieceJointeController::checkAction() (type=dossier).
     *
     * @Route("/dossier/{id}/pieces-jointes/{idpj}/verifier", name="dossier_pieces_jointes_check", methods={"POST"}, requirements={"idpj"="\d+"})
     *
     * @param int $id   ID du dossier
     * @param int $idpj ID de la pièce jointe
     */
    public function check(int $id, int $idpj, Request $request): JsonResponse
    {
        $rawToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_pj_add', is_string($rawToken) ? $rawToken : null)) {
            return $this->json(['exists' => false]);
        }

        $dossier = $this->dossierRepository->find($id);
        if (null === $dossier) {
            return $this->json(['exists' => false]);
        }

        $pj = $this->pieceJointeRepository->find($idpj);
        if (null === $pj) {
            return $this->json(['exists' => false]);
        }

        $dpj = $this->dossierPieceJointeRepository->findOneBy([
            'pieceJointe' => $idpj,
            'dossier' => $id,
        ]);
        if (null === $dpj) {
            return $this->json(['exists' => false]);
        }

        $filePath = $this->resolvePjFilePath($pj, $dossier);
        if (!file_exists($filePath)) {
            return $this->json(['exists' => false]);
        }

        $html = $this->renderView('dossier/pieces_jointes/_piece_jointe_row.html.twig', [
            'dpj' => $dpj,
            'isNew' => true,
            'isPlatau' => null !== $dossier->getConsultationPlatau(),
            'verrou' => $dossier->hasVerrou(),
            'dossier' => $dossier,
        ]);

        return $this->json(['exists' => true, 'html' => $html]);
    }

    /**
     * Téléchargement de toutes les pièces jointes d'un dossier en ZIP.
     *
     * Port direct de DossierController::getZipAllPjAction().
     *
     * @Route("/dossier/{id}/pieces-jointes/telecharger-tout", name="dossier_pieces_jointes_zip", methods={"GET"})
     *
     * @param int $id ID du dossier
     */
    public function downloadZip(int $id): Response
    {
        $dossier = $this->dossierRepository->find($id);
        if (null === $dossier) {
            throw $this->createNotFoundException('Dossier introuvable.');
        }

        $dossierPjs = $this->dossierPieceJointeRepository->findAllByDossier($id);

        $fichiers = [];
        foreach ($dossierPjs as $dpj) {
            $pj = $dpj->getPieceJointe();
            if (null === $pj) {
                continue;
            }

            $pjPath = $this->resolvePjFilePath($pj, $dossier);
            if (!is_readable($pjPath)) {
                continue;
            }

            $fichiers[] = [
                'path' => $pjPath,
                'name' => $pj->getNom().$pj->getExtension(),
            ];
        }

        if (empty($fichiers)) {
            $this->addFlash('error', 'Aucune pièce jointe disponible au téléchargement.');

            return $this->redirectToRoute('dossier_pieces_jointes', ['id' => $id]);
        }

        $zipPath = $this->realDataPath.'/uploads/'.$dossier->getId().'_pj.zip';

        $zip = new \ZipArchive();
        if (true !== $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            $this->addFlash('error', 'Impossible de créer l\'archive ZIP. Veuillez contacter votre service informatique.');

            return $this->redirectToRoute('dossier_pieces_jointes', ['id' => $id]);
        }

        foreach ($fichiers as $fichier) {
            $zip->addFile($fichier['path'], $fichier['name']);
        }

        $zip->close();

        $response = new BinaryFileResponse($zipPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $dossier->getId().'_pj.zip'
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * Renvoi d'une pièce jointe vers Plat'AU (POST AJAX).
     *
     * Port direct de PieceJointeController::retryExportPlatauAction().
     *
     * @Route("/dossier/{id}/pieces-jointes/{idpj}/renvoyer-platau", name="dossier_pieces_jointes_retry_platau", methods={"POST"}, requirements={"idpj"="\d+"})
     *
     * @param int $id   ID du dossier
     * @param int $idpj ID de la pièce jointe
     */
    public function retryExportPlatau(int $id, int $idpj, Request $request): JsonResponse
    {
        $rawTokenRetry = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_pj_retry_'.$idpj, is_string($rawTokenRetry) ? $rawTokenRetry : null)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        $pj = $this->pieceJointeRepository->find($idpj);
        if (null === $pj) {
            throw $this->createNotFoundException('Pièce jointe introuvable.');
        }

        $dpj = $this->dossierPieceJointeRepository->findOneBy([
            'pieceJointe' => $idpj,
            'dossier' => $id,
        ]);
        if (null === $dpj) {
            throw $this->createNotFoundException('Pièce jointe non rattachée à ce dossier.');
        }

        $statut = $this->pieceJointeStatutRepository->findOneBy(['nomStatut' => 'to_be_exported']);
        if (null === $statut) {
            throw $this->createNotFoundException("Statut 'to_be_exported' introuvable.");
        }

        $pj->setStatut($statut);
        $pj->setMessageErreur(null);

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Fragment HTML listant les pièces jointes exportables vers Plat'AU (GET AJAX).
     *
     * Port direct de PieceJointeController::displayPjPlatauAction().
     *
     * @Route("/dossier/{id}/pieces-jointes/platau/exportables", name="dossier_pieces_jointes_platau_exportables", methods={"GET"})
     *
     * @param int $id ID du dossier
     */
    public function displayPjPlatau(int $id): Response
    {
        $pjs = $this->dossierPieceJointeRepository->findPiecesJointesByStatut($id, 'not_exported');

        if (empty($pjs)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return $this->render('dossier/pieces_jointes/_platau_exportables.html.twig', [
            'piecesJointes' => $pjs,
        ]);
    }
}

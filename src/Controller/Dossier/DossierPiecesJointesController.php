<?php

namespace App\Controller\Dossier;

use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Dossier;
use App\Entity\DossierPieceJointe;
use App\Entity\PieceJointe;
use App\Entity\Utilisateur;
use App\Repository\DossierPieceJointeRepository;
use App\Repository\PieceJointeStatutRepository;
use App\Service\DataStore\DataStore;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Port direct de PieceJointeController (type=dossier) et DossierController (pieceJointeAction, getZipAllPjAction).
 */
class DossierPiecesJointesController extends AbstractController implements DossierLateralNavigationInterface
{
    private const FORBIDDEN_EXTENSIONS = ['.php', '.php4', '.php5', '.sh', '.ksh', '.csh'];

    public function __construct(private DataStore $dataStore, private DossierPieceJointeRepository $dossierPieceJointeRepository, private PieceJointeStatutRepository $pieceJointeStatutRepository, private EntityManagerInterface $entityManager, private NotificationService $notificationService, private ValidatorInterface $validator, private Filesystem $filesystem, private string $realDataPath)
    {
    }

    /**
     * Résout le chemin physique d'une pièce jointe selon que le dossier est Platau ou non.
     *
     * @param bool $createDir Créer le répertoire si inexistant (utile à l'upload)
     */
    private function resolvePjFilePath(PieceJointe $pj, Dossier $dossier, bool $createDir = false): string
    {
        if (null !== $dossier->getConsultationPlatau()) {
            $path = $this->realDataPath.'/uploads/pieces-jointes/'.$pj->getId().$pj->getExtension();
            if ($createDir) {
                $this->filesystem->mkdir(dirname($path));
            }

            return $path;
        }

        return $this->dataStore->getFilePath($pj, 'dossier', (int) $dossier->getId(), $createDir);
    }

    /**
     * Liste des pièces jointes d'un dossier.
     *
     * Port direct de PieceJointeController::indexAction() (type=dossier).
     */
    #[Route(path: '/dossier/{id}/pieces-jointes', name: 'dossier_pieces_jointes', methods: ['GET'])]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function index(Dossier $dossier): Response
    {
        $dossierPjs = $this->dossierPieceJointeRepository->findAllByDossier((int) $dossier->getId());

        /** @var Utilisateur $user */
        $user = $this->getUser();
        $lastVisit = $this->notificationService->getLastVisitDate($user, NotificationService::PIECES_DOSSIER_NAMESPACE);

        $isPlatau = null !== $dossier->getConsultationPlatau();

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
            $isNew = $isPlatau && null !== $dateNotif && $dateNotif > $lastVisit;

            $listePj[] = [
                'dpj' => $dpj,
                'isNew' => $isNew,
            ];
        }

        $this->notificationService->setLastVisitDate($user, NotificationService::PIECES_DOSSIER_NAMESPACE);

        return $this->render('dossier/pieces_jointes/index.html.twig', [
            'dossier' => $dossier,
            'listePj' => $listePj,
            'isPlatau' => $isPlatau,
            'verrou' => $dossier->hasVerrou(),
            'displayDownloadButton' => [] !== $listePj,
        ]);
    }

    /**
     * Formulaire d'ajout de pièce jointe (fragment AJAX, sans layout).
     *
     * Port direct de PieceJointeController::formAction() (type=dossier).
     */
    #[Route(path: '/dossier/{id}/pieces-jointes/formulaire', name: 'dossier_pieces_jointes_form', methods: ['GET'], defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function form(Dossier $dossier): Response
    {
        return $this->render('dossier/pieces_jointes/form.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    /**
     * Ajout d'une pièce jointe (POST AJAX, upload fichier).
     *
     * Port direct de PieceJointeController::addAction() (type=dossier).
     */
    #[Route(path: '/dossier/{id}/pieces-jointes/ajouter', name: 'dossier_pieces_jointes_add', methods: ['POST'], defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function add(Dossier $dossier, Request $request): JsonResponse
    {
        $rawCsrfAdd = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_pj_add', is_string($rawCsrfAdd) ? $rawCsrfAdd : null)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide.'], 403);
        }

        $file = $request->files->get('fichier');
        if (null === $file) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun fichier reçu. Veuillez contacter votre service informatique.',
            ], 400);
        }

        /** @var UploadedFile $file */
        $violations = $this->validator->validate($file, [
            new Assert\Callback(function (UploadedFile $uploadedFile, ExecutionContextInterface $context): void {
                $ext = $uploadedFile->getClientOriginalExtension();
                if ('' === $ext) {
                    $context->buildViolation('Les fichiers sans extension ne sont pas autorisés.')->addViolation();

                    return;
                }

                if (in_array('.'.strtolower($ext), self::FORBIDDEN_EXTENSIONS, true)) {
                    $context->buildViolation("Ce type de fichier n'est pas autorisé en upload")->addViolation();
                }
            }),
        ]);

        if (count($violations) > 0) {
            $firstViolation = $violations[0];
            $message = null !== $firstViolation ? (string) $firstViolation->getMessage() : 'Fichier invalide.';

            return $this->json(['success' => false, 'message' => $message], 400);
        }

        $extension = '.'.strtolower($file->getClientOriginalExtension());

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
        $pj->setDate(new \DateTime('today'));

        // Statut par défaut : 'not_exported' (parité avec le DEFAULT 1 en base de données)
        $statutNotExported = $this->pieceJointeStatutRepository->findOneBy(['nomStatut' => 'not_exported']);
        if (null !== $statutNotExported) {
            $pj->setStatut($statutNotExported);
        }

        $this->entityManager->persist($pj);
        $this->entityManager->flush();

        $destPath = $this->resolvePjFilePath($pj, $dossier, true);

        try {
            $file->move(dirname($destPath), basename($destPath));
        } catch (\Exception) {
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
     */
    #[Route(path: '/dossier/{id}/pieces-jointes/{idpj}/telecharger', name: 'dossier_pieces_jointes_get', methods: ['GET'], requirements: ['idpj' => '\d+'])]
    #[ParamConverter('pj', class: PieceJointe::class, options: ['id' => 'idpj'])]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function download(Dossier $dossier, PieceJointe $pj): Response
    {
        $dpj = $this->dossierPieceJointeRepository->findOneBy([
            'pieceJointe' => $pj,
            'dossier' => $dossier,
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
     */
    #[Route(path: '/dossier/{id}/pieces-jointes/{idpj}/supprimer', name: 'dossier_pieces_jointes_delete', methods: ['POST'], requirements: ['idpj' => '\d+'], defaults: ['_dossier_lateral_nav' => false])]
    #[ParamConverter('pj', class: PieceJointe::class, options: ['id' => 'idpj'])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function delete(Dossier $dossier, PieceJointe $pj, Request $request): JsonResponse
    {
        $rawTokenDelete = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_pj_delete_'.$pj->getId(), is_string($rawTokenDelete) ? $rawTokenDelete : null)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        $dpj = $this->dossierPieceJointeRepository->findOneBy([
            'pieceJointe' => $pj,
            'dossier' => $dossier,
        ]);
        if (null === $dpj) {
            return $this->json(['error' => 'Pièce jointe non rattachée à ce dossier.'], 404);
        }

        $statut = $pj->getStatut();
        if (null !== $statut && in_array($statut->getNomStatut(), ['to_be_exported', 'exported', 'awaiting_status'], true)) {
            return $this->json([
                'error' => "Impossible de supprimer une pièce jointe en cours de traitement Plat'AU.",
            ], 403);
        }

        $filePath = $this->resolvePjFilePath($pj, $dossier);
        $this->filesystem->remove($filePath);

        $this->entityManager->remove($dpj);
        $this->entityManager->remove($pj);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Vérifie qu'une pièce jointe existe physiquement après upload (POST AJAX).
     *
     * Port direct de PieceJointeController::checkAction() (type=dossier).
     */
    #[Route(path: '/dossier/{id}/pieces-jointes/{idpj}/verifier', name: 'dossier_pieces_jointes_check', methods: ['POST'], requirements: ['idpj' => '\d+'], defaults: ['_dossier_lateral_nav' => false])]
    #[ParamConverter('pj', class: PieceJointe::class, options: ['id' => 'idpj'])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function check(Dossier $dossier, PieceJointe $pj, Request $request): JsonResponse
    {
        $rawToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_pj_add', is_string($rawToken) ? $rawToken : null)) {
            return $this->json(['exists' => false]);
        }

        $dpj = $this->dossierPieceJointeRepository->findOneBy([
            'pieceJointe' => $pj,
            'dossier' => $dossier,
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
            'isNew' => false,
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
     */
    #[Route(path: '/dossier/{id}/pieces-jointes/telecharger-tout', name: 'dossier_pieces_jointes_zip', methods: ['GET'])]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function downloadZip(Dossier $dossier): Response
    {
        $dossierPjs = $this->dossierPieceJointeRepository->findAllByDossier((int) $dossier->getId());

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

        if ([] === $fichiers) {
            $this->addFlash('danger', 'Aucune pièce jointe disponible au téléchargement.');

            return $this->redirectToRoute('dossier_pieces_jointes', ['id' => $dossier->getId()]);
        }

        $zipPath = tempnam(sys_get_temp_dir(), $dossier->getId().'_pj_');
        if (false === $zipPath) {
            $this->addFlash('danger', "Impossible de créer l'archive ZIP. Veuillez contacter votre service informatique.");

            return $this->redirectToRoute('dossier_pieces_jointes', ['id' => $dossier->getId()]);
        }

        $zipPath .= '.zip';

        $zip = new \ZipArchive();
        if (true !== $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            $this->addFlash('danger', "Impossible de créer l'archive ZIP. Veuillez contacter votre service informatique.");

            return $this->redirectToRoute('dossier_pieces_jointes', ['id' => $dossier->getId()]);
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
     */
    #[Route(path: '/dossier/{id}/pieces-jointes/{idpj}/renvoyer-platau', name: 'dossier_pieces_jointes_retry_platau', methods: ['POST'], requirements: ['idpj' => '\d+'], defaults: ['_dossier_lateral_nav' => false])]
    #[ParamConverter('pj', class: PieceJointe::class, options: ['id' => 'idpj'])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function retryExportPlatau(Dossier $dossier, PieceJointe $pj, Request $request): JsonResponse
    {
        $rawTokenRetry = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_pj_retry_'.$pj->getId(), is_string($rawTokenRetry) ? $rawTokenRetry : null)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        $dpj = $this->dossierPieceJointeRepository->findOneBy([
            'pieceJointe' => $pj,
            'dossier' => $dossier,
        ]);
        if (null === $dpj) {
            return $this->json(['error' => 'Pièce jointe non rattachée à ce dossier.'], 404);
        }

        $statut = $this->pieceJointeStatutRepository->findOneBy(['nomStatut' => 'to_be_exported']);
        if (null === $statut) {
            return $this->json(['error' => "Statut 'to_be_exported' introuvable."], 500);
        }

        $pj->setStatut($statut);
        $pj->setMessageErreur(null);

        $this->entityManager->flush();

        return $this->json(['success' => true, 'statut' => $statut->getNomLisible()]);
    }
}

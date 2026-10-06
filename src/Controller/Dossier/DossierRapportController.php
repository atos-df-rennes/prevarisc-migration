<?php

namespace App\Controller\Dossier;

use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Dto\Commission\CommissionOdtDto;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Repository\CommissionRepository;
use App\Service\Documents;
use App\Service\DossierRapportOdtService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Onglet Rapport du dossier : affichage du formulaire de génération et génération ODT.
 */
class DossierRapportController extends AbstractController implements DossierLateralNavigationInterface
{
    public function __construct(
        private readonly CommissionRepository $commissionRepository,
        private readonly DossierRapportOdtService $odtService,
        private readonly string $documentsDataPath,
        private readonly string $documentsRealDataPath,
        private readonly LoggerInterface $logger,
        private readonly Documents $documents
    ) {
    }

    /**
     * Affiche l'onglet Rapport du dossier.
     */
    #[Route(path: '/dossier/{id}/rapport', name: 'dossier_rapport', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function rapport(Dossier $dossier): Response
    {
        $idDossier = (int) $dossier->getId();

        // Fichiers disponibles après verrouillage (commission 0)
        $fichierVer = $this->documents->listReportTemplates(0);

        // Liste des commissions avec leur type et leurs fichiers ODT
        /** @var CommissionOdtDto[] $listeCommissions */
        $listeCommissions = [];
        foreach ($this->commissionRepository->findAllWithType() as $commission) {
            $idCommission = (int) $commission->getId();
            $fichiers = $this->documents->listReportTemplates($idCommission);
            $listeCommissions[] = new CommissionOdtDto(
                $idCommission,
                $commission->getLibelle(),
                (int) ($commission->getType() ? $commission->getType()->getId() : 0),
                $commission->getType() ? $commission->getType()->getLibelle() : null,
                $fichiers
            );
        }

        // Commission liée au dossier
        $commission = $dossier->getCommission();

        // Établissements liés au dossier
        $etablissements = $dossier->getEtablissements()->toArray();

        // Type d'activité principal du premier établissement
        $idTypeActivitePrinc = null;
        if (!empty($etablissements)) {
            $firstEtab = $etablissements[0];
            try {
                $latestInfos = $firstEtab->getLatestInformations();
                if (null !== $latestInfos->getTypeActivite()) {
                    $idTypeActivitePrinc = $latestInfos->getTypeActivite()->getId();
                }
            } catch (\OutOfBoundsException) {
                // Établissement sans informations : idTypeActivitePrinc reste null
            }
        }

        return $this->render('dossier/rapport.html.twig', [
            'dossier' => $dossier,
            'commission' => $commission,
            'locked' => (bool) $dossier->hasVerrou(),
            'fichierVer' => $fichierVer,
            'listeCommissions' => $listeCommissions,
            'listeEtablissement' => $etablissements,
            'idTypeActivitePrinc' => $idTypeActivitePrinc,
            'documentsDataPath' => $this->documentsDataPath,
        ]);
    }

    /**
     * Action AJAX POST de génération des rapports ODT.
     */
    #[Route(path: '/dossier/{id}/rapport/generer', name: 'dossier_generationrapport', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted('edit_doss_ignore_verrou', subject: 'dossier')]
    public function generationRapport(Dossier $dossier, Request $request): Response
    {
        $idDossier = (int) $dossier->getId();

        // B2 : protection CSRF
        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_rapport_'.$dossier->getId(), is_string($csrfToken) ? $csrfToken : '')) {
            $this->logger->error('CSRF token validation failed for rapport generation.', [
                'dossier_id' => $idDossier,
                'token_provided' => !empty($csrfToken),
            ]);

            return new Response('', Response::HTTP_FORBIDDEN);
        }

        // B1 : retrouver le modèle dans la bibliothèque proposée par Finder.
        $rawFichier = $request->request->get('file', '');
        $relativeFile = is_scalar($rawFichier) ? (string) $rawFichier : '';
        $resolvedPath = $this->documents->resolveReportTemplate($relativeFile);
        if (null === $resolvedPath) {
            $this->logger->error('Invalid file path for rapport generation.', [
                'dossier_id' => $idDossier,
                'requested_file' => $relativeFile,
                'resolved_path' => $resolvedPath,
                'documents_path' => $this->documentsRealDataPath,
            ]);

            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $fichierSelect = $resolvedPath;

        $repriseCC = $request->request->getBoolean('repriseCC');

        $requestData = $request->request->all();
        $idEtabList = $requestData['idEtab'] ?? [];
        $idEtabList = is_array($idEtabList) ? $idEtabList : [];

        // B4 : filtrer les idEtab soumis contre ceux du dossier (IDOR)
        $allowedIds = array_map(fn (Etablissement $e): int => $e->getId(), $dossier->getEtablissements()->toArray());
        $idEtabList = array_map(
            static fn ($id): int => is_scalar($id) ? (int) $id : 0,
            $idEtabList
        );
        $idEtabList = array_values(array_filter($idEtabList, fn (int $id): bool => in_array($id, $allowedIds, true)));

        $html = '';
        try {
            foreach ($idEtabList as $etablissementId) {
                $html .= $this->odtService->generer($idDossier, $etablissementId, $fichierSelect, $repriseCC);
            }
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la génération du rapport dans le contrôleur.', [
                'dossier_id' => $idDossier,
                'file' => $fichierSelect,
                'exception_message' => $exception->getMessage(),
                'exception_code' => $exception->getCode(),
            ]);

            throw $exception;
        }

        return new Response($html);
    }
}

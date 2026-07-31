<?php

namespace App\Controller\Dossier;

use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Dto\Commission\CommissionOdtDto;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Repository\CommissionRepository;
use App\Service\DossierRapportOdtService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Onglet Rapport du dossier : affichage du formulaire de génération et génération ODT.
 */
class DossierRapportController extends AbstractController implements DossierLateralNavigationInterface
{
    /**
     * Chemin URL des documents de commission (pour les liens de téléchargement).
     */
    private readonly string $documentsDataPath;

    /**
     * Chemin réel (filesystem) des documents de commission (pour lister les fichiers).
     */
    private readonly string $documentsRealDataPath;

    public function __construct(
        private readonly CommissionRepository $commissionRepository,
        private readonly DossierRapportOdtService $odtService,
        string $documentsDataPath,
        string $documentsRealDataPath
    ) {
        $this->documentsDataPath = rtrim($documentsDataPath, '/');
        $this->documentsRealDataPath = rtrim($documentsRealDataPath, '/');
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
        $fichierVer = $this->listFiles($this->documentsRealDataPath.DIRECTORY_SEPARATOR.'0');

        // Liste des commissions avec leur type et leurs fichiers ODT
        /** @var CommissionOdtDto[] $listeCommissions */
        $listeCommissions = [];
        foreach ($this->commissionRepository->findAllWithType() as $commission) {
            $idCommission = (int) $commission->getId();
            $fichiers = $this->listFiles($this->documentsRealDataPath.DIRECTORY_SEPARATOR.$idCommission);
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
        // B2 : protection CSRF
        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_rapport_'.$dossier->getId(), is_string($csrfToken) ? $csrfToken : '')) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $idDossier = (int) $dossier->getId();

        // B1 : reconstruire le chemin depuis l'identifiant relatif et valider qu'il reste sous documentsRealDataPath
        $relativeFile = (string) $request->request->get('file', '');
        $resolvedPath = realpath($this->documentsRealDataPath.DIRECTORY_SEPARATOR.$relativeFile);
        if (
            false === $resolvedPath
            || !str_starts_with($resolvedPath, $this->documentsRealDataPath.DIRECTORY_SEPARATOR)
            || 'odt' !== strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION))
        ) {
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
        foreach ($idEtabList as $etablissementId) {
            $html .= $this->odtService->generer($idDossier, $etablissementId, $fichierSelect, $repriseCC);
        }

        return new Response($html);
    }

    /**
     * Liste les fichiers ODT d'un répertoire (sans dossiers ni fichiers non-.odt).
     * Port direct de l'opendir/readdir legacy, remplacé par Symfony Finder (REF-R-05).
     *
     * @return string[]
     */
    private function listFiles(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($path)->depth('== 0')->name('*.odt')->sortByName();

        $fichiers = [];
        foreach ($finder as $file) {
            $fichiers[] = $file->getFilename();
        }

        return $fichiers;
    }
}

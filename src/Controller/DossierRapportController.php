<?php

namespace App\Controller;

use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Repository\CommissionRepository;
use App\Service\DossierRapportOdtService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Onglet Rapport du dossier : affichage du formulaire de génération et génération ODT.
 */
class DossierRapportController extends AbstractController implements DossierLateralNavigationInterface
{
    /**
     * @var CommissionRepository
     */
    private $commissionRepository;

    /**
     * @var DossierRapportOdtService
     */
    private $odtService;

    /**
     * Chemin URL des documents de commission (pour les liens de téléchargement).
     *
     * @var string
     */
    private $documentsDataPath;

    /**
     * Chemin réel (filesystem) des documents de commission (pour lister les fichiers).
     *
     * @var string
     */
    private $documentsRealDataPath;

    public function __construct(
        CommissionRepository $commissionRepository,
        DossierRapportOdtService $odtService,
        string $documentsDataPath,
        string $documentsRealDataPath
    ) {
        $this->commissionRepository = $commissionRepository;
        $this->odtService = $odtService;
        $this->documentsDataPath = rtrim($documentsDataPath, '/');
        $this->documentsRealDataPath = rtrim($documentsRealDataPath, '/');
    }

    /**
     * Affiche l'onglet Rapport du dossier.
     *
     * @Route("/dossier/{id}/rapport", name="dossier_rapport", requirements={"id"="\d+"}, methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function rapport(Dossier $dossier): Response
    {
        $idDossier = (int) $dossier->getId();

        // Fichiers disponibles après verrouillage (commission 0)
        $fichierVer = $this->listFiles($this->documentsRealDataPath.DIRECTORY_SEPARATOR.'0');

        // Liste des commissions avec leur type et leurs fichiers ODT
        $listeCommissions = [];
        foreach ($this->commissionRepository->findAllWithType() as $commission) {
            $idCommission = $commission['ID_COMMISSION'];
            $fichiers = $this->listFiles($this->documentsRealDataPath.DIRECTORY_SEPARATOR.$idCommission);
            $listeCommissions[] = [
                'ID_COMMISSION' => $idCommission,
                'LIBELLE_COMMISSION' => $commission['LIBELLE_COMMISSION'],
                'ID_COMMISSIONTYPE' => $commission['ID_COMMISSIONTYPE'],
                'LIBELLE_COMMISSIONTYPE' => $commission['LIBELLE_COMMISSIONTYPE'],
                'listeFichier' => $fichiers,
            ];
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
            } catch (\OutOfBoundsException $e) {
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
     *
     * @Route("/dossier/{id}/rapport/generer", name="dossier_generationrapport", requirements={"id"="\d+"}, methods={"POST"}, defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function generationRapport(Dossier $dossier, Request $request): Response
    {
        // B2 : protection CSRF
        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('dossier_rapport_'.$dossier->getId(), is_string($csrfToken) ? $csrfToken : '')) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $idDossier = (int) $dossier->getId();

        // B1 : reconstruire le chemin depuis l'identifiant relatif et valider qu'il reste sous documentsRealDataPath
        $rawFichier = $request->request->get('file', '');
        $relativeFile = is_scalar($rawFichier) ? (string) $rawFichier : '';
        $resolvedPath = realpath($this->documentsRealDataPath.DIRECTORY_SEPARATOR.$relativeFile);
        if (
            false === $resolvedPath
            || 0 !== strpos($resolvedPath, $this->documentsRealDataPath.DIRECTORY_SEPARATOR)
            || 'odt' !== strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION))
        ) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $fichierSelect = $resolvedPath;

        $repriseCC = $request->request->getBoolean('repriseCC');

        /** @var string[]|null $idEtabList */
        $idEtabList = $request->request->get('idEtab', []);
        if (!is_array($idEtabList)) {
            $idEtabList = [];
        }

        // B4 : filtrer les idEtab soumis contre ceux du dossier (IDOR)
        $allowedIds = array_map(function (Etablissement $e): int { return $e->getId(); }, $dossier->getEtablissements()->toArray());
        $idEtabList = array_values(array_filter($idEtabList, function ($id) use ($allowedIds): bool {
            return in_array((int) $id, $allowedIds, true);
        }));

        $html = '';
        foreach ($idEtabList as $etablissementId) {
            $html .= $this->odtService->generer($idDossier, (int) $etablissementId, $fichierSelect, $repriseCC);
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

<?php

namespace App\Controller;

use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Dossier;
use App\Service\DossierRapportOdtService;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Onglet Rapport du dossier : affichage du formulaire de génération et génération ODT.
 */
class DossierRapportController extends AbstractController implements DossierLateralNavigationInterface
{
    /**
     * @var Connection
     */
    private $conn;

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
        Connection $conn,
        DossierRapportOdtService $odtService,
        string $documentsDataPath,
        string $documentsRealDataPath
    ) {
        $this->conn = $conn;
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
        /** @var array<int, array<string, string|null>> $commissionsFlat */
        $commissionsFlat = $this->conn->fetchAllAssociative(
            'SELECT c.ID_COMMISSION, c.LIBELLE_COMMISSION, ct.ID_COMMISSIONTYPE, ct.LIBELLE_COMMISSIONTYPE
             FROM commission c
             JOIN commissiontype ct ON c.ID_COMMISSIONTYPE = ct.ID_COMMISSIONTYPE
             ORDER BY ct.ID_COMMISSIONTYPE ASC, c.LIBELLE_COMMISSION ASC'
        );

        $listeCommissions = [];
        foreach ($commissionsFlat as $commission) {
            $idCommission = (int) ($commission['ID_COMMISSION'] ?? '0');
            $fichiers = $this->listFiles($this->documentsRealDataPath.DIRECTORY_SEPARATOR.$idCommission);
            $listeCommissions[] = [
                'ID_COMMISSION' => $idCommission,
                'LIBELLE_COMMISSION' => $commission['LIBELLE_COMMISSION'],
                'ID_COMMISSIONTYPE' => (int) ($commission['ID_COMMISSIONTYPE'] ?? '0'),
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
            $latestInfos = $firstEtab->getLatestInformations();
            if (null !== $latestInfos->getTypeActivite()) {
                $idTypeActivitePrinc = $latestInfos->getTypeActivite()->getId();
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
            'documentsRealDataPath' => $this->documentsRealDataPath,
        ]);
    }

    /**
     * Action AJAX POST de génération des rapports ODT.
     *
     * @Route("/dossier/{id}/rapport/generer", name="dossier_generationrapport", requirements={"id"="\d+"}, methods={"POST"}, defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function generationRapport(Dossier $dossier, Request $request): Response
    {
        $idDossier = (int) $dossier->getId();
        $rawFichier = $request->request->get('file', '');
        $fichierSelect = is_scalar($rawFichier) ? (string) $rawFichier : '';
        $repriseCC = $request->request->getBoolean('repriseCC');

        /** @var string[]|null $idEtabList */
        $idEtabList = $request->request->get('idEtab', []);
        if (!is_array($idEtabList)) {
            $idEtabList = [];
        }

        $html = '';
        foreach ($idEtabList as $etablissementId) {
            $html .= $this->odtService->generer($idDossier, (int) $etablissementId, $fichierSelect, $repriseCC);
        }

        return new Response($html);
    }

    /**
     * Liste les fichiers d'un répertoire (sans points, dossiers et .gitignore).
     *
     * @return string[]
     */
    private function listFiles(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $dir = opendir($path);
        if (false === $dir) {
            return [];
        }

        $fichiers = [];
        while (false !== ($element = readdir($dir))) {
            if ('.' === $element) {
                continue;
            }

            if ('..' === $element) {
                continue;
            }

            if ('.gitignore' === $element) {
                continue;
            }

            if (!is_dir($path.DIRECTORY_SEPARATOR.$element)) {
                $fichiers[] = $element;
            }
        }

        closedir($dir);
        sort($fichiers);

        return $fichiers;
    }
}

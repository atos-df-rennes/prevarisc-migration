<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Repository\CommissionRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour la génération de documents (rapports ODT).
 * Port direct de DossierController::{rapportAction,generationrapportAction,creationdocAction}.
 */
class DossierRapportController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var CommissionRepository */
    private $commissionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        DossierRepository $dossierRepository,
        EtablissementRepository $etablissementRepository,
        CommissionRepository $commissionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->dossierRepository = $dossierRepository;
        $this->etablissementRepository = $etablissementRepository;
        $this->commissionRepository = $commissionRepository;
    }

    /**
     * Affiche le formulaire de génération de documents (rapports ODT).
     * Port direct de DossierController::rapportAction() (Zend lignes 1951-2043).
     *
     * @Route("/dossier/{id}/rapport", name="dossier_rapport", methods={"GET", "POST"}, requirements={"id"="\d+"})
     *
     * @return Response
     */
    public function rapport(Request $request, Dossier $dossier): Response
    {
        $idDossier = (int) $dossier->getId();
        $locked = $dossier->getVerrou();

        $pathBase = rtrim(getenv('REAL_DATA_PATH') ?: '/data', '/').'/uploads/documents';

        // Récupération des fichiers visibles après verrouillage
        $pathVer = $pathBase.'/0';
        /** @var array<string> $fichierVer */
        $fichierVer = [];

        if (is_dir($pathVer)) {
            $dirVer = opendir($pathVer);
            if (false !== $dirVer) {
                while ($elementVer = readdir($dirVer)) {
                    if ('.' !== $elementVer && '..' !== $elementVer && '.gitignore' !== $elementVer) {
                        if (!is_dir($pathVer.'/'.$elementVer)) {
                            $fichierVer[] = $elementVer;
                        }
                    }
                }
                closedir($dirVer);
            }
        }
        sort($fichierVer);

        // Liste des commissions
        $listeCommissionRaw = $this->commissionRepository->findAll();
        /** @var array<int, array<string, mixed>> $listeCommission */
        $listeCommission = [];

        foreach ($listeCommissionRaw as $commission) {
            $commissionId = $commission->getId();
            $path = $pathBase.'/'.$commissionId;
            /** @var array<string> $fichier */
            $fichier = [];

            if (is_dir($path)) {
                $dir = opendir($path);
                if (false !== $dir) {
                    while ($element = readdir($dir)) {
                        if ('.' !== $element && '..' !== $element && '.gitignore' !== $element) {
                            if (!is_dir($path.'/'.$element)) {
                                $fichier[] = $element;
                            }
                        }
                    }
                    closedir($dir);
                }
            }
            sort($fichier);

            $listeCommission[] = [
                'id' => $commissionId,
                'libelle' => $commission->getLibelle(),
                'listeFichier' => $fichier,
            ];
        }

        $listeEtablissement = $dossier->getEtablissements();
        $idTypeActivitePrinc = null;

        $etablissements = $dossier->getEtablissements();
        if (null !== $etablissements && $etablissements->count() > 0) {
            $firstEtab = $etablissements->first();
            if (false !== $firstEtab) {
                $informations = $firstEtab->getInformations();
                if (null !== $informations && $informations->count() > 0) {
                    $firstInfo = $informations->first();
                    if (false !== $firstInfo) {
                        $typeActivite = $firstInfo->getTypeActivite();
                        if (null !== $typeActivite) {
                            $idTypeActivitePrinc = $typeActivite->getId();
                        }
                    }
                }
            }
        }

        return $this->render('dossier/rapport.html.twig', [
            'dossier' => $dossier,
            'id_dossier' => $idDossier,
            'locked' => $locked,
            'fichierVer' => $fichierVer,
            'infosCommission' => $dossier->getCommission(),
            'liste_commission' => $listeCommission,
            'pathBase' => $pathBase,
            'path' => rtrim(getenv('DATA_PATH') ?: '/app', '/').'/uploads/documents',
            'listeEtablissement' => $listeEtablissement,
            'idTypeActivitePrinc' => $idTypeActivitePrinc,
        ]);
    }

    /**
     * Endpoint AJAX pour la génération de documents.
     * Port direct de DossierController::generationrapportAction() (Zend lignes 2045-2055).
     *
     * @Route("/dossier/generationrapport", name="dossier_generationrapport", methods={"POST"})
     *
     * @return Response
     */
    public function generationRapport(Request $request): Response
    {
        /** @var mixed $idDossier */
        $idDossier = $request->request->get('idDossier');
        /** @var mixed $idEtabsRaw */
        $idEtabsRaw = $request->request->get('idEtab', []);

        if (!is_array($idEtabsRaw)) {
            $idEtabsRaw = [];
        }

        foreach ($idEtabsRaw as $etablissementId) {
            $this->creationdoc((int) $idDossier, (int) $etablissementId, $request);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Documents générés avec succès',
        ]);
    }

    /**
     * Prépare le contexte pour la génération ODT (action principale).
     * Port direct de DossierController::creationdocAction() (Zend lignes 2062-2665+).
     *
     * @param int $idDossier ID du dossier
     * @param int $idEtab ID de l'établissement
     * @param Request $request Request pour récupérer 'file' et 'repriseCC'
     *
     * @return void
     */
    private function creationdoc(int $idDossier, int $idEtab, Request $request): void
    {
        $dossier = $this->dossierRepository->find($idDossier);
        if (null === $dossier) {
            return;
        }

        $etablissement = $this->etablissementRepository->find($idEtab);
        if (null === $etablissement) {
            return;
        }

        /** @var array<string, mixed> $data */
        $data = [];

        // @todo Phases 3-7 : Implémenter la logique complète de creationdocAction()
        // Cette méthode doit collecter tous les paramètres du dossier/établissement/commission
        // et les passer au moteur de fusion ODT.

        // Paramètres de base
        $data['idDossier'] = $idDossier;
        $data['idEtab'] = $idEtab;
        $data['fichierSelect'] = $request->request->get('file');
        $data['repriseCC'] = $request->request->get('repriseCC');

        // Date du jour
        $dateDuJour = new DateTime();
        $data['dateDuJour'] = $dateDuJour->format('d/m/Y');

        // @todo Phases 3-7 : Collecte des informations établissement
        // @todo Phases 3-7 : Collecte des informations dossier
        // @todo Phases 3-7 : Collecte des contacts (11 rôles)
        // @todo Phases 3-7 : Collecte des éléments du dossier
        // @todo Phases 3-7 : Fusion ODT et sauvegarde
    }
}

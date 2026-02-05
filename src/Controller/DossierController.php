<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use App\Form\DossierType;
use App\Repository\AvisRepository;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
use App\Repository\DossierTypeRepository;
use App\Repository\EtablissementRepository;
use App\Repository\UtilisateurRepository;
use App\Service\Commission\DateCommission;
use App\Service\DossierDocumentManquantService;
use App\Service\DossierFields;
use App\Service\Etablissement;
use App\Service\PlatauConsultation;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PlatauConsultation */
    private $platauConsultationService;

    /** @var DateCommission */
    private $dateCommissionService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PlatauConsultation $platauConsultationService,
        DateCommission $dateCommissionService
    ) {
        $this->entityManager = $entityManager;
        $this->platauConsultationService = $platauConsultationService;
        $this->dateCommissionService = $dateCommissionService;
    }

    /**
     * Affichage des informations générales d'un dossier (lecture seule).
     *
     * @Route("/dossier/{id}", name="dossier_index", requirements={"id"="\d+"}, methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function index(Dossier $dossier): Response
    {
        $afficherAvis = !$dossier->hasAbsenceDeQuorum()
            && !$dossier->isHorsDelai()
            && !$dossier->hasAvisDiffere()
            && !$dossier->isIncomplet()
            && !$dossier->hasNePeutSePrononcer()
        ;

        $platauData = $this->platauConsultationService->getPlatauDataForView($dossier->getConsultationPlatau());

        $datesCommissionLiees = $this->dateCommissionService->getDatesLiees($dossier);

        return $this->render('dossier/index.html.twig', [
            'dossier' => $dossier,
            'afficher_avis' => $afficherAvis,
            'platau' => $platauData,
            'datesCommissionLiees' => $datesCommissionLiees,
        ]);
    }

    /* @fixme(bug-legacy): Lorsqu'on met un avis différé sur un dossier donnant avis,
     * cela change l'ID_DOSSIER_DONNANT_AVIS sur l'établissement. (ok)
     * En revanche cela ne remet pas l'ID_DOSSIER_DONNANT_AVIS sur l'ancien dossier si on enlève l'avis différé.
     */
    /**
     * Édition des informations générales d'un dossier.
     *
     * @Route("/dossier/{id}/modifier", name="dossier_edit", requirements={"id"="\d+"}, methods={"GET", "POST"})
     */
    public function edit(
        Request $request,
        Dossier $dossier,
        DossierTypeRepository $dossierTypeRepository,
        AvisRepository $avisRepository,
        CommissionTypeRepository $commissionTypeRepository,
        CommissionRepository $commissionRepository,
        EtablissementRepository $etablissementRepository,
        UtilisateurRepository $utilisateurRepository,
        DossierFields $dossierFieldsService,
        Etablissement $etablissementService,
        DossierDocumentManquantService $documentManquantService
    ): Response {
        // Organiser les documents manquants (tri + séparation actifs/historique)
        $documentManquantService->organizeDocumentsManquants($dossier);

        $form = $this->createForm(DossierType::class, $dossier, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Début transaction explicite pour rollback en cas d'erreur
            $this->entityManager->beginTransaction();

            try {
                // @todo BLOC 1 : Transfert documents consultés (si changement type/nature)

                // @todo BLOC 3 : Traitement POST (dates, booléens) - géré par DataTransformers

                // @todo BLOC 5 : Service instructeur (simplifié)

                // @todo BLOC 6 : Dates spéciales

                // @todo BLOC 7 : Établissements

                // @todo BLOC 8 : Liaison nature

                // @todo BLOC 12 : Export Plat'AU

                // @todo BLOC 13 : Documents manquants

                // @todo BLOC 14 : Documents urbanisme

                // @todo BLOC 15 : Préventionnistes

                // @todo BLOC 16 : Affectations (commissions/visites)

                // @todo BLOC 17 : Dossier donnant avis

                // Persist et flush toutes les modifications
                $this->entityManager->persist($dossier);
                $this->entityManager->flush();

                // Commit transaction
                $this->entityManager->commit();

                // @todo BLOC 18 : Cache + Redirect
                $this->addFlash('success', 'Le dossier a été mis à jour avec succès.');

                return $this->redirectToRoute('dossier_index', ['id' => $dossier->getId()]);
            } catch (\Exception $e) {
                // Rollback en cas d'erreur
                $this->entityManager->rollback();

                // Log de l'erreur pour investigation
                $this->addFlash('error', 'Une erreur est survenue lors de la sauvegarde du dossier : '.$e->getMessage());
            }
        }

        // Récupération des données nécessaires pour l'affichage
        $dossierTypes = $dossierTypeRepository->findAll();
        $avis = $avisRepository->findAll();

        // Récupération des commissions par type
        $commissionsParType = [];
        foreach ($commissionTypeRepository->findAll() as $type) {
            $commissionsParType[$type->getId()] = [
                'libelle' => $type->getLibelle(),
                'commissions' => $commissionRepository->findBy(['type' => $type]),
            ];
        }

        // Récupération des établissements liés au dossier
        $etablissements = $dossier->getEtablissements();
        $enteteEtab = null;
        $lastAvisEtab = '';

        if ($etablissements->count() > 0) {
            $premierEtablissement = $etablissements->first();

            if ($premierEtablissement) {
                $enteteEtab = $etablissementRepository->find($premierEtablissement->getId());

                // Récupération du dernier avis via l'historique des dossiers
                if ($enteteEtab) {
                    $lastAvisEtab = $etablissementService->getLastAvisLibelle($enteteEtab->getId());
                }
            }
        }

        // Récupération des informations de l'utilisateur connecté
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Vérification des permissions
        $isAllowedChangeAvis = $this->isGranted('edit_avis_com', 'avis_commission');
        $isAllowedDeleteDossier = $this->isGranted('delete_dossier', 'suppression');

        // Informations sur le verrouillage
        $userInfoVerrou = null;
        if ($dossier->hasVerrou() && $dossier->getUtilisateurVerrou()) {
            $userInfoVerrou = $utilisateurRepository->find($dossier->getUtilisateurVerrou());
        }

        // Configuration des champs pour JavaScript
        $fieldsConfig = json_encode($dossierFieldsService->getAllFieldsByNature());

        // Informations Plat'AU
        $platauData = $this->platauConsultationService->getPlatauDataForView($dossier->getConsultationPlatau());

        // Dates de commission liées
        $datesCommissionLiees = $this->dateCommissionService->getDatesLiees($dossier);

        return $this->render('dossier/edit.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
            'dossierTypes' => $dossierTypes,
            'avis' => $avis,
            'commissionsParType' => $commissionsParType,
            'etablissements' => $etablissements,
            'enteteEtab' => $enteteEtab,
            'lastAvisEtab' => $lastAvisEtab,
            'user' => $user,
            'isAllowedChangeAvis' => $isAllowedChangeAvis,
            'isAllowedDeleteDossier' => $isAllowedDeleteDossier,
            'userInfoVerrou' => $userInfoVerrou,
            'fieldsConfig' => $fieldsConfig,
            'platau' => $platauData,
            'datesCommissionLiees' => $datesCommissionLiees,
        ]);
    }

    /**
     * @Route("/dossier/{id}/verrouiller", name="dossier_verrouiller")
     *
     * @IsGranted("verrouillage_dossier", subject="dossier")
     */
    public function verrouiller(Request $request, Dossier $dossier): Response
    {
        $referer = $request->headers->get('referer');

        if (null === $referer) {
            throw new \InvalidArgumentException("La route d'origine n'a pas pu être déterminée.");
        }

        $dossier->setVerrou(true);
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $dossier->setUtilisateurVerrou($user->getId());

        $this->entityManager->flush();

        return $this->redirect($referer);
    }

    /**
     * @Route("/dossier/{id}/deverrouiller", name="dossier_deverrouiller")
     *
     * @IsGranted("verrouillage_dossier", subject="dossier")
     */
    public function deverrouiller(Request $request, Dossier $dossier): Response
    {
        $referer = $request->headers->get('referer');

        if (null === $referer) {
            throw new \InvalidArgumentException("La route d'origine n'a pas pu être déterminée.");
        }

        $dossier->setVerrou(false);
        $dossier->setUtilisateurVerrou(null);

        $this->entityManager->flush();

        return $this->redirect($referer);
    }
}

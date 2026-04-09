<?php

namespace App\Controller;

use App\Constant\CommissionTypeEvenement;
use App\Constant\DossierNature;
use App\Constant\NomInterneCapsule;
use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Dossier;
use App\Entity\DossierDocumentConsulte;
use App\Entity\DossierNatureListe;
use App\Entity\ListeDocumentAjoute;
use App\Entity\ListeDocumentConsulte;
use App\Entity\PieceJointe;
use App\Entity\Utilisateur;
use App\Form\Dossier\DossierDescriptifType;
use App\Form\Dossier\DossierDocumentConsulteType;
use App\Form\Dossier\DossierTextesApplicablesType;
use App\Form\Dossier\DossierType;
use App\Form\Dossier\ListeDocumentAjouteType;
use App\Repository\AvisRepository;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
use App\Repository\DossierAffectationRepository;
use App\Repository\DossierRepository;
use App\Repository\DossierTypeRepository;
use App\Repository\EtablissementRepository;
use App\Repository\UtilisateurRepository;
use App\Service\Commission\DateCommission;
use App\Service\Descriptif as DescriptifService;
use App\Service\Descriptif\DescriptifFormBuilder;
use App\Service\Descriptif\DescriptifFormHydrator;
use App\Service\Descriptif\DescriptifFormPersister;
use Symfony\Component\Form\FormFactoryInterface;
use App\Service\Dossier as DossierService;
use App\Service\Dossier\DocumentConsulteTransferService;
use App\Service\Dossier\DossierAffectationService;
use App\Service\Dossier\DossierCreationService;
use App\Service\Dossier\DossierSauvegardeService;
use App\Service\Dossier\DossierDocumentsConsultesService;
use App\Service\DossierDocumentManquantService;
use App\Service\DossierFields;
use App\Service\Etablissement;
use App\Service\Platau\PieceJointeExportService;
use App\Service\PlatauConsultation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierController extends AbstractController implements DossierLateralNavigationInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PlatauConsultation */
    private $platauConsultationService;

    /** @var DateCommission */
    private $dateCommissionService;

    /**
     * @var DossierTypeRepository
     */
    private $dossierTypeRepository;

    /**
     * @var AvisRepository
     */
    private $avisRepository;

    /**
     * @var CommissionTypeRepository
     */
    private $commissionTypeRepository;

    /**
     * @var CommissionRepository
     */
    private $commissionRepository;

    /**
     * @var EtablissementRepository
     */
    private $etablissementRepository;

    /**
     * @var UtilisateurRepository
     */
    private $utilisateurRepository;

    /**
     * @var DossierFields
     */
    private $dossierFieldsService;

    /**
     * @var Etablissement
     */
    private $etablissementService;

    /**
     * @var DossierDocumentManquantService
     */
    private $documentManquantService;

    /**
     * @var PieceJointeExportService
     */
    private $pieceJointeExportService;

    /**
     * @var DossierAffectationService
     */
    private $dossierAffectationService;

    /**
     * @var DossierAffectationRepository
     */
    private $dossierAffectationRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var DossierService */
    private $dossierService;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var DossierCreationService */
    private $dossierCreationService;

    /** @var DossierSauvegardeService */
    private $dossierSauvegardeService;

    /** @var DescriptifService */
    private $descriptifService;

    /** @var DescriptifFormHydrator */
    private $descriptifFormHydrator;

    /** @var DescriptifFormBuilder */
    private $descriptifFormBuilder;

    /** @var DescriptifFormPersister */
    private $descriptifFormPersister;

    /** @var DossierDocumentsConsultesService */
    private $dossierDocumentsConsultesService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PlatauConsultation $platauConsultationService,
        DateCommission $dateCommissionService,
        DossierTypeRepository $dossierTypeRepository,
        AvisRepository $avisRepository,
        CommissionTypeRepository $commissionTypeRepository,
        CommissionRepository $commissionRepository,
        EtablissementRepository $etablissementRepository,
        UtilisateurRepository $utilisateurRepository,
        DossierFields $dossierFieldsService,
        Etablissement $etablissementService,
        DossierDocumentManquantService $documentManquantService,
        PieceJointeExportService $pieceJointeExportService,
        DossierAffectationService $dossierAffectationService,
        DossierAffectationRepository $dossierAffectationRepository,
        LoggerInterface $logger,
        DossierService $dossierService,
        DossierRepository $dossierRepository,
        DossierCreationService $dossierCreationService,
        DossierSauvegardeService $dossierSauvegardeService,
        DescriptifService $descriptifService,
        DescriptifFormHydrator $descriptifFormHydrator,
        DescriptifFormBuilder $descriptifFormBuilder,
        DescriptifFormPersister $descriptifFormPersister,
        DossierDocumentsConsultesService $dossierDocumentsConsultesService
    ) {
        $this->entityManager = $entityManager;
        $this->platauConsultationService = $platauConsultationService;
        $this->dateCommissionService = $dateCommissionService;
        $this->dossierTypeRepository = $dossierTypeRepository;
        $this->avisRepository = $avisRepository;
        $this->commissionTypeRepository = $commissionTypeRepository;
        $this->commissionRepository = $commissionRepository;
        $this->etablissementRepository = $etablissementRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->dossierFieldsService = $dossierFieldsService;
        $this->etablissementService = $etablissementService;
        $this->documentManquantService = $documentManquantService;
        $this->pieceJointeExportService = $pieceJointeExportService;
        $this->dossierAffectationService = $dossierAffectationService;
        $this->dossierAffectationRepository = $dossierAffectationRepository;
        $this->logger = $logger;
        $this->dossierService = $dossierService;
        $this->dossierRepository = $dossierRepository;
        $this->dossierCreationService = $dossierCreationService;
        $this->dossierSauvegardeService = $dossierSauvegardeService;
        $this->descriptifService = $descriptifService;
        $this->descriptifFormHydrator = $descriptifFormHydrator;
        $this->descriptifFormBuilder = $descriptifFormBuilder;
        $this->descriptifFormPersister = $descriptifFormPersister;
        $this->dossierDocumentsConsultesService = $dossierDocumentsConsultesService;
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

    /**
     * Création d'un nouveau dossier.
     *
     * L'établissement est optionnel : /dossier/ajouter?etablissement=42
     *
     * @Route("/dossier/ajouter", name="dossier_new", methods={"GET", "POST"}, defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("add_dossier")
     */
    public function new(
        Request $request,
        bool $mailEnabled
    ): Response {
        $dossier = new Dossier();

        // Récupération de l'établissement si passé en query param
        $idEtablissement = $request->query->getInt('etablissement', 0);
        $etablissement = $idEtablissement > 0
            ? $this->etablissementRepository->find($idEtablissement)
            : null;

        $anciensAvis = [];
        // Actions déclenchées si le dossier est ajouté sur un établissement
        if (null !== $etablissement) {
            $dossier->addEtablissement($etablissement);

            $dossierDonnantAvis = $etablissement->getDossierDonnantAvis();
            $anciensAvis[$etablissement->getId()] = $dossierDonnantAvis
                ? $dossierDonnantAvis->getAvisCommission()
                : null;
        }

        // Définir le créateur (utilisateur connecté)
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $dossier->setCreateur($user);

        $form = $this->createForm(DossierType::class, $dossier, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var int[] $etablissementsIds */
                $etablissementsIds = $this->entityManager->wrapInTransaction(function () use ($dossier, $etablissement, $form): array {
                    // Affectations commissions/visites + dénormalisation dates
                    /** @var string|null $idAffectationCommission */
                    $idAffectationCommission = $form->get('idAffectationDossierCommission')->getData();
                    /** @var string|null $idAffectationVisite */
                    $idAffectationVisite = $form->get('idAffectationDossierVisite')->getData();

                    // Persist du dossier avant les opérations dépendantes de l'ID
                    $this->dossierSauvegardeService->nullifierChampsNonApplicables($dossier);
                    $this->entityManager->persist($dossier);
                    $this->entityManager->flush();

                    // Gestion des affectations commission/visite
                    $this->dossierAffectationService->gererAffectations(
                        $dossier,
                        $idAffectationCommission ? (int) $idAffectationCommission : null,
                        $idAffectationVisite ? (int) $idAffectationVisite : null
                    );

                    // Opérations d'initialisation spécifiques à la création
                    if (null !== $etablissement) {
                        $this->dossierCreationService->initialiserDepuisEtablissement($dossier, $etablissement);
                    }

                    $this->dossierCreationService->creerDocumentsAttestationLevee($dossier);
                    $this->dossierCreationService->initialiserPrescriptionsReglementaires($dossier);

                    $this->entityManager->flush();

                    // Dossier donnant avis : pas de désaffectation possible en création
                    $repercuterAvis = (bool) $form->get('repercuterAvis')->getData();
                    $ids = $this->dossierSauvegardeService->gererDossierDonnantAvis($dossier, $repercuterAvis);

                    $this->entityManager->flush();

                    return $ids;
                });

                $this->dossierSauvegardeService->genererAlertesVAO($dossier);
                $this->dossierSauvegardeService->genererAlertesEmailAvis($dossier, $etablissementsIds, $anciensAvis, $mailEnabled);

                $this->addFlash('success', 'Le dossier a été créé avec succès.');

                return $this->redirect($this->dossierSauvegardeService->construireUrlRedirection($dossier, $etablissementsIds));
            } catch (\Throwable $e) {
                // Purge le cache de l'EM pour éviter de conserver des entités
                // en mémoire avec des IDs invalides après le rollback.
                $this->entityManager->clear();

                $this->logger->error('Erreur lors de la création du dossier', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->addFlash('danger', 'Une erreur est survenue lors de la création du dossier. Veuillez réessayer ou contacter le service informatique.');
            }
        }

        // Données nécessaires à l'affichage du formulaire
        $dossierTypes = $this->dossierTypeRepository->findAll();
        $avis = $this->avisRepository->findAll();

        $commissionsParType = [];
        foreach ($this->commissionTypeRepository->findAll() as $type) {
            $commissionsParType[$type->getId()] = [
                'libelle' => $type->getLibelle(),
                'commissions' => $this->commissionRepository->findBy(['type' => $type]),
            ];
        }

        $fieldsConfig = json_encode($this->dossierFieldsService->getAllFieldsByNature());

        $enteteEtab = $etablissement;
        $lastAvisEtab = '';

        if (null !== $etablissement) {
            $lastAvisEtab = $this->etablissementService->getLastAvisLibelle($etablissement->getId());
        }

        return $this->render('dossier/new.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
            'dossierTypes' => $dossierTypes,
            'avis' => $avis,
            'commissionsParType' => $commissionsParType,
            'etablissement' => $etablissement,
            'enteteEtab' => $enteteEtab,
            'lastAvisEtab' => $lastAvisEtab,
            'fieldsConfig' => $fieldsConfig,
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
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function edit(
        Request $request,
        Dossier $dossier,
        DocumentConsulteTransferService $documentConsulteTransferService,
        bool $mailEnabled
    ): Response {
        // Organiser les documents manquants pour l'affichage (actifs/historiques, tri décroissant)
        $this->documentManquantService->organizeDocumentsManquants($dossier);

        // Sauvegarder les valeurs initiales pour détecter les changements
        $ancienType = $dossier->getType();
        $ancienneNature = $dossier->getNature();

        // Sauvegarder anciens avis établissements pour détecter changements (alertes email)
        $anciensAvis = [];
        foreach ($dossier->getEtablissements() as $etablissement) {
            $dossierDonnantAvis = $etablissement->getDossierDonnantAvis();
            $anciensAvis[$etablissement->getId()] = $dossierDonnantAvis
                ? $dossierDonnantAvis->getAvisCommission()
                : null;
        }

        $form = $this->createForm(DossierType::class, $dossier, [
            'is_edit' => true,
        ]);

        // Pré-remplir les champs Hidden avec IDs affectations existantes
        // Nécessaire pour que la sauvegarde sans modification ne désaffecte pas
        $affectationCommission = $this->dossierAffectationRepository->findAffectationByDossierAndType(
            $dossier,
            CommissionTypeEvenement::TYPE_COMMISSION_SALLE
        );
        $affectationVisite = $this->dossierAffectationRepository->findAffectationByDossierAndType(
            $dossier,
            [CommissionTypeEvenement::TYPE_VISITE, CommissionTypeEvenement::TYPE_GROUPE_VISITE]
        );

        $form->get('idAffectationDossierCommission')->setData(
            $affectationCommission && $affectationCommission->getDateCommission()
                ? (string) $affectationCommission->getDateCommission()->getId()
                : null
        );
        $form->get('idAffectationDossierVisite')->setData(
            $affectationVisite && $affectationVisite->getDateCommission()
                ? (string) $affectationVisite->getDateCommission()->getId()
                : null
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Début transaction explicite pour rollback en cas d'erreur
            $this->entityManager->beginTransaction();

            try {
                // Transfert documents consultés (si changement type/nature)
                $nouveauType = $dossier->getType();
                $nouvelleNature = $dossier->getNature();

                if ($nouveauType && ($ancienType !== $nouveauType || $ancienneNature !== $nouvelleNature)) {
                    $documentConsulteTransferService->transfertOuSupprimeDocumentsConsultes(
                        $dossier,
                        $nouveauType,
                        $nouvelleNature
                    );
                }

                // Export Plat'AU - Marquer PJ sélectionnées pour export
                /** @var Collection<int, PieceJointe>|null $piecesJointesPlatau */
                $piecesJointesPlatau = $form->get('piecesJointesPlatau')->getData();
                if ($piecesJointesPlatau) {
                    $this->pieceJointeExportService->markForExport($piecesJointesPlatau);
                }

                // Affectations commissions/visites + dénormalisation dates
                /** @var string|null $idAffectationCommission */
                $idAffectationCommission = $form->get('idAffectationDossierCommission')->getData();
                /** @var string|null $idAffectationVisite */
                $idAffectationVisite = $form->get('idAffectationDossierVisite')->getData();

                $this->dossierAffectationService->gererAffectations(
                    $dossier,
                    $idAffectationCommission ? (int) $idAffectationCommission : null,
                    $idAffectationVisite ? (int) $idAffectationVisite : null
                );

                // Dossier donnant avis : gestion affectation/désaffectation selon changement de nature
                $repercuterAvis = (bool) $form->get('repercuterAvis')->getData();
                $etablissementsIds = $this->dossierSauvegardeService->gererDossierDonnantAvis($dossier, $repercuterAvis, $ancienneNature);

                // Persist et flush toutes les modifications
                $this->dossierSauvegardeService->nullifierChampsNonApplicables($dossier);
                $this->entityManager->persist($dossier);
                $this->entityManager->flush();

                // Commit transaction
                $this->entityManager->commit();

                $this->dossierSauvegardeService->genererAlertesVAO($dossier);
                $this->dossierSauvegardeService->genererAlertesEmailAvis($dossier, $etablissementsIds, $anciensAvis, $mailEnabled);

                $this->addFlash('success', 'Le dossier a été mis à jour avec succès.');

                return $this->redirect($this->dossierSauvegardeService->construireUrlRedirection($dossier, $etablissementsIds));
            } catch (\Exception $e) {
                // Rollback en cas d'erreur
                $this->entityManager->rollback();

                // Log de l'erreur technique pour le service informatique
                $this->logger->error('Erreur lors de la sauvegarde du dossier', [
                    'dossier_id' => $dossier->getId(),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Message générique pour l'utilisateur
                $this->addFlash('danger', 'Une erreur est survenue lors de la sauvegarde du dossier. Veuillez réessayer ou contacter le service informatique.');
            }
        }

        // Récupération des données nécessaires pour l'affichage
        $dossierTypes = $this->dossierTypeRepository->findAll();
        $avis = $this->avisRepository->findAll();

        // Récupération des commissions par type
        $commissionsParType = [];
        foreach ($this->commissionTypeRepository->findAll() as $type) {
            $commissionsParType[$type->getId()] = [
                'libelle' => $type->getLibelle(),
                'commissions' => $this->commissionRepository->findBy(['type' => $type]),
            ];
        }

        // Récupération des établissements liés au dossier
        $etablissements = $dossier->getEtablissements();
        $enteteEtab = null;
        $lastAvisEtab = '';

        if ($etablissements->count() > 0) {
            $premierEtablissement = $etablissements->first();

            if ($premierEtablissement) {
                $enteteEtab = $this->etablissementRepository->find($premierEtablissement->getId());

                // Récupération du dernier avis via l'historique des dossiers
                if ($enteteEtab) {
                    $lastAvisEtab = $this->etablissementService->getLastAvisLibelle($enteteEtab->getId());
                }
            }
        }

        // @todo: Peut être utilisé directement en Twig ?
        // Récupération des informations de l'utilisateur connecté
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Vérification des permissions
        $isAllowedChangeAvis = $this->isGranted('edit_avis_com', 'avis_commission');
        $isAllowedDeleteDossier = $this->isGranted('delete_dossier', 'suppression');

        // Informations sur le verrouillage
        $userInfoVerrou = null;
        if ($dossier->hasVerrou() && $dossier->getUtilisateurVerrou()) {
            $userInfoVerrou = $this->utilisateurRepository->find($dossier->getUtilisateurVerrou());
        }

        // Configuration des champs pour JavaScript
        $fieldsConfig = json_encode($this->dossierFieldsService->getAllFieldsByNature());

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
     * @Route("/dossier/{id}/verrouiller", name="dossier_verrouiller", defaults={"_dossier_lateral_nav": false})
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
     * @Route("/dossier/{id}/deverrouiller", name="dossier_deverrouiller", defaults={"_dossier_lateral_nav": false})
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

    /**
     * @Route("/dossier/{id}/descriptif", name="dossier_descriptif", methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function descriptif(Dossier $dossier): Response
    {
        return $this->render('dossier/descriptif/descriptif.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    /**
     * @Route("/dossier/{id}/descriptif/modifier", name="dossier_descriptif_edit", methods={"GET", "POST"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function editDescriptif(Request $request, Dossier $dossier): Response
    {
        $form = $this->createForm(DossierDescriptifType::class, $dossier);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $message = 'Le descriptif';

            if (
                $dossier->getNature() instanceof DossierNatureListe
                && in_array($dossier->getNature()->getId(), DossierNature::NATURES_DESCRIPTIF_TRAVAUX, true)
            ) {
                $message .= ' des travaux';
            }

            $message .= ' a bien été modifié.';
            $this->addFlash('success', $message);

            return $this->redirectToRoute('dossier_descriptif', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/descriptif/edit_descriptif.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/dossier/{id}/textes-applicables", name="dossier_textes_applicables", methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function textesApplicables(Dossier $dossier): Response
    {
        $textesApplicablesData = [];

        if (null !== $dossier->getId()) {
            $textesApplicablesData = $this->dossierService->getTextesApplicables($dossier->getId());
        }

        return $this->render('dossier/textes_applicables/textes_applicables.html.twig', [
            'dossier' => $dossier,
            'textesApplicables' => $textesApplicablesData,
        ]);
    }

    /**
     * @Route("/dossier/{id}/textes-applicables/modifier", name="dossier_textes_applicables_edit", methods={"GET", "POST"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function editTextesApplicables(Request $request, Dossier $dossier): Response
    {
        // Pré-charge les relations
        if (null !== $dossier->getId()) {
            $this->dossierRepository->getTextesApplicablesByType($dossier->getId());
        }

        $form = $this->createForm(DossierTextesApplicablesType::class, $dossier);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Les textes applicables ont été mis à jour avec succès.');

            return $this->redirectToRoute('dossier_textes_applicables', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/textes_applicables/edit_textes_applicables.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche l'onglet Vérifications techniques du dossier.
     *
     * @Route("/dossier/{id}/verifications-techniques", name="dossier_verifications_techniques", methods={"GET"})
     *
     * @Security("is_granted('verifications_techniques') and is_granted('view_doss', dossier)")
     */
    public function verificationsTechniques(Dossier $dossier): Response
    {
        $data = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES);

        return $this->render('dossier/_verifications_techniques.html.twig', [
            'dossier' => $dossier,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
        ]);
    }

    /**
     * Affiche et traite le formulaire d'édition des vérifications techniques du dossier.
     *
     * @Route("/dossier/{id}/verifications-techniques/modifier", name="dossier_verifications_techniques_edit", methods={"GET", "POST"})
     *
     * @Security("is_granted('verifications_techniques') and is_granted('edit_doss', dossier)")
     */
    public function editVerificationsTechniques(Dossier $dossier, Request $request, FormFactoryInterface $formFactory): Response
    {
        $data = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES);

        $formData = $this->descriptifFormHydrator->hydrateFromDossier($dossier, $data['rubriques']);
        $form = $this->descriptifFormBuilder->buildForm($formFactory, $formData, $data['rubriques']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var \App\Dto\Descriptif\DescriptifFormData $submittedData */
                $submittedData = $form->getData();
                $this->descriptifFormPersister->persistForDossier($dossier, $submittedData, $data['rubriques']);
                $this->addFlash('success', 'Les vérifications techniques ont bien été mises à jour.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la sauvegarde des vérifications techniques', [
                    'dossier_id' => $dossier->getId(),
                    'exception' => $e->getMessage(),
                ]);
                $this->addFlash('error', 'Les vérifications techniques n\'ont pas été mises à jour. Veuillez réessayer.');
            }

            return $this->redirectToRoute('dossier_verifications_techniques', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/edit_verifications_techniques.html.twig', [
            'dossier' => $dossier,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/dossier/{id}/documents-consultes", name="dossier_documents_consultes", methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function documentsConsultes(Dossier $dossier): Response
    {
        $documentsConsultables = $this->dossierDocumentsConsultesService->getDocumentsConsultables($dossier);

        // La collection documentsAjoutes est LAZY par défaut. Sans cet appel, Doctrine
        // émettra une requête par accès dans le template (N+1). On force le chargement ici.
        $dossier->getDocumentsAjoutes()->toArray();

        // Index des documents consultés par leur ID_DOC pour un lookup O(1) lors de la séparation.
        $documentsConsultesMap = [];
        foreach ($dossier->getDocumentsConsultes()->toArray() as $documentConsulte) {
            $documentsConsultesMap[$documentConsulte->getDocument()] = $documentConsulte;
        }

        // Conformément au legacy, les documents déjà renseignés apparaissent en tête de liste,
        // suivis des documents consultables non encore renseignés.
        $documentsConsultes = [];
        $documentsNonConsultes = [];
        foreach ($documentsConsultables as $doc) {
            $docId = (string) $doc->getId();
            if (isset($documentsConsultesMap[$docId])) {
                $documentsConsultes[] = ['doc' => $doc, 'documentConsulte' => $documentsConsultesMap[$docId]];
            } else {
                $documentsNonConsultes[] = $doc;
            }
        }

        return $this->render('dossier/documents_consultes/documents_consultes.html.twig', [
            'dossier' => $dossier,
            'documentsConsultes' => $documentsConsultes,
            'documentsNonConsultes' => $documentsNonConsultes,
        ]);
    }

    /**
     * @Route("/dossier/{id}/documents-consultes/{idDocumentConsultable}/ajouter",
     *     name="dossier_documents_consultes_add",
     *     methods={"GET", "POST"})
     *
     * @ParamConverter("documentConsultable", options={"id" = "idDocumentConsultable"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function documentsConsultesAdd(Request $request, Dossier $dossier, ListeDocumentConsulte $documentConsultable): Response
    {
        $documentConsulte = new DossierDocumentConsulte();
        $documentConsulte->setDossier($dossier);
        $documentConsulte->setDocument((string) $documentConsultable->getId());
        $documentConsulte->setConsulte(true);
        if (null !== $dossier->getNature()) {
            $documentConsulte->setNature($dossier->getNature());
        }

        $form = $this->createForm(DossierDocumentConsulteType::class, $documentConsulte);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($documentConsulte);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le document consulté a été ajouté avec succès.');

            return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/documents_consultes/edit_documents_consultes.html.twig', [
            'dossier' => $dossier,
            'documentConsulte' => $documentConsulte,
            'libelle' => $documentConsultable->getLibelle(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/dossier/{id}/documents-consultes/{idDocumentConsulte}/supprimer",
     *     name="dossier_documents_consultes_delete",
     *     requirements={"id"="\d+", "idDocumentConsulte"="\d+"},
     *     methods={"DELETE"})
     *
     * @ParamConverter("documentConsulte", options={"id" = "idDocumentConsulte"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function documentsConsultesRemove(Request $request, Dossier $dossier, DossierDocumentConsulte $documentConsulte): Response
    {
        if ($documentConsulte->getDossier() !== $dossier) {
            throw $this->createAccessDeniedException('Ce document ne fait pas partie de ce dossier.');
        }

        if (is_string($request->request->get('token')) && !$this->isCsrfTokenValid('delete_document', $request->request->get('token'))) {
            throw $this->createAccessDeniedException('Token CSRF non valide.');
        }

        $dossier->removeDocumentsConsulte($documentConsulte);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le document consulté a été supprimé avec succès.');

        return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
    }

    /**
     * @Route("/dossier/{id}/documents-consultes/{idDocumentConsulte}/modifier",
     *     name="dossier_documents_consultes_edit",
     *     requirements={"id"="\d+", "idDocumentConsulte"="\d+"},
     *     methods={"GET", "POST"})
     *
     * @ParamConverter("documentConsulte", options={"id" = "idDocumentConsulte"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function documentsConsultesEdit(Request $request, Dossier $dossier, DossierDocumentConsulte $documentConsulte): Response
    {
        if ($documentConsulte->getDossier() !== $dossier) {
            throw $this->createAccessDeniedException('Ce document ne fait pas partie de ce dossier.');
        }

        $libelle = '';
        if (null !== $documentConsulte->getDocument() && 0 !== intval($documentConsulte->getDocument())) {
            $libelle = $this->dossierDocumentsConsultesService->getLibelleDocumentConsulte($documentConsulte->getDocument());
        }

        $form = $this->createForm(DossierDocumentConsulteType::class, $documentConsulte);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le document consulté a été modifié avec succès.');

            return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/documents_consultes/edit_documents_consultes.html.twig', [
            'dossier' => $dossier,
            'documentConsulte' => $documentConsulte,
            'libelle' => $libelle,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/dossier/{id}/documents-ajoutes/{idDocumentAjoute}/supprimer",
     *     name="dossier_documents_ajoutes_delete",
     *     requirements={"id"="\d+", "idDocumentAjoute"="\d+"},
     *     methods={"DELETE"})
     *
     * @ParamConverter("documentAjoute", options={"id" = "idDocumentAjoute"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function documentsAjoutesRemove(Request $request, Dossier $dossier, ListeDocumentAjoute $documentAjoute): Response
    {
        if ($documentAjoute->getDossier() !== $dossier) {
            throw $this->createAccessDeniedException('Ce document ne fait pas partie de ce dossier.');
        }

        if (is_string($request->request->get('token')) && !$this->isCsrfTokenValid('delete_document', $request->request->get('token'))) {
            throw $this->createAccessDeniedException('Token CSRF non valide.');
        }

        $dossier->removeDocumentsAjoute($documentAjoute);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le document a été supprimé avec succès.');

        return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
    }

    /**
     * @Route("/dossier/{id}/documents-ajoutes/ajouter",
     *     name="dossier_documents_ajoutes_add",
     *     requirements={"id"="\d+"},
     *     methods={"GET", "POST"})
     * @Route("/dossier/{id}/documents-ajoutes/{idDocumentAjoute}/modifier",
     *     name="dossier_documents_ajoutes_edit",
     *     requirements={"id"="\d+", "idDocumentAjoute"="\d+"},
     *     methods={"GET", "POST"})
     *
     * @ParamConverter("documentAjoute", options={"id" = "idDocumentAjoute"}, isOptional="true")
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function documentsAjoutesEdit(Request $request, Dossier $dossier, ?ListeDocumentAjoute $documentAjoute = null): Response
    {
        $isNew = null === $documentAjoute;

        if ($isNew) {
            $documentAjoute = new ListeDocumentAjoute();
            $documentAjoute->setDossier($dossier);
            if (null !== $dossier->getNature()) {
                $documentAjoute->setNature($dossier->getNature());
            }

            $this->entityManager->persist($documentAjoute);
        } elseif ($documentAjoute->getDossier() !== $dossier) {
            throw $this->createAccessDeniedException('Ce document ne fait pas partie de ce dossier.');
        }

        $form = $this->createForm(ListeDocumentAjouteType::class, $documentAjoute);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', $isNew ? 'Le document a été ajouté avec succès.' : 'Le document a été modifié avec succès.');

            return $this->redirectToRoute('dossier_documents_consultes', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/documents_consultes/edit_documents_ajoutes.html.twig', [
            'dossier' => $dossier,
            'documentAjoute' => $documentAjoute,
            'form' => $form->createView(),
        ]);
    }
}

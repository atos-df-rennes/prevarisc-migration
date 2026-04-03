<?php

namespace App\Controller;

use App\Constant\CommissionTypeEvenement;
use App\Constant\DossierNature;
use App\Constant\NomInterneCapsule;
use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\PieceJointe;
use App\Entity\Utilisateur;
use App\Form\Dossier\DossierDescriptifType;
use App\Form\Dossier\DossierLiaisonsType;
use App\Form\Dossier\DossierTextesApplicablesType;
use App\Form\Dossier\DossierType;
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
use App\Service\DescriptifCopier;
use App\Service\DescriptifInspector;
use App\Service\Dossier as DossierService;
use App\Service\Dossier\DocumentConsulteTransferService;
use App\Service\Dossier\DossierAffectationService;
use App\Service\Dossier\DossierCreationService;
use App\Service\Dossier\DossierDonnantAvisService;
use App\Service\Dossier\DossierLiaisonsService;
use App\Service\Dossier\DossierSauvegardeService;
use App\Service\Dossier\EffectifsDegagementsRepriseValidator;
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

    /** @var DossierLiaisonsService */
    private $dossierLiaisonsService;

    /** @var DescriptifCopier */
    private $descriptifCopier;

    /** @var DescriptifInspector */
    private $descriptifInspector;

    /** @var EffectifsDegagementsRepriseValidator */
    private $repriseValidator;

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
        DescriptifCopier $descriptifCopier,
        DescriptifInspector $descriptifInspector,
        EffectifsDegagementsRepriseValidator $repriseValidator,
        DossierLiaisonsService $dossierLiaisonsService
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
        $this->dossierLiaisonsService = $dossierLiaisonsService;
        $this->descriptifCopier = $descriptifCopier;
        $this->descriptifInspector = $descriptifInspector;
        $this->repriseValidator = $repriseValidator;
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
     * Affiche l'onglet Effectifs et Dégagements du dossier.
     *
     * @Route("/dossier/{id}/effectifs-degagements", name="dossier_effectifs_degagements", methods={"GET"})
     *
     * @Security("is_granted('effectifs_degagements_doss') and is_granted('view_doss', dossier)")
     */
    public function effectifsDegagements(Dossier $dossier): Response
    {
        $data = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);

        return $this->render('dossier/_effectifs_degagements.html.twig', [
            'dossier' => $dossier,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
            'hasData' => $this->descriptifInspector->rubriquesHaveData($data['rubriques']),
        ]);
    }

    /**
     * @Route("/dossier/{id}/copier-effectifs-degagements-etablissement",
     *        name="dossier_copier_effectifs_degagements_etablissement",
     *        requirements={"id"="\d+"},
     *        methods={"POST"})
     *
     * @Security("is_granted('effectifs_degagements_doss') and is_granted('edit_doss', dossier)")
     */
    public function copierEffectifsDegagementsEtablissement(
        Dossier $dossier,
        Request $request
    ): Response {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('copier_effectifs_degagements_'.$dossier->getId(), $csrfToken)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        $etablissements = $dossier->getEtablissements()->toArray();
        $nbEtablissements = count($etablissements);

        if (1 !== $nbEtablissements) {
            return $this->json([
                'error' => sprintf(
                    'Impossible de copier : %d établissement(s) lié(s) au dossier (1 attendu). Les effectifs et dégagements n\'ont pas été copiés.',
                    $nbEtablissements
                ),
            ], 400);
        }

        $etablissement = $etablissements[0];

        if (!$this->isGranted('edit_ets', $etablissement)) {
            return $this->json(['error' => 'Vous n\'avez pas le droit de modifier cet établissement.'], 403);
        }

        try {
            $sourceDossier = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);
            $targetEtablissement = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT);

            $this->descriptifCopier->copyFromDossierToEtablissement(
                $sourceDossier['rubriques'],
                $targetEtablissement['rubriques'],
                $etablissement
            );
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la copie des effectifs et dégagements dossier→établissement.', [
                'dossier_id' => $dossier->getId(),
                'etablissement_id' => $etablissement->getId(),
                'error' => $exception->getMessage(),
            ]);

            return $this->json(['error' => 'Erreur technique lors de la copie. Veuillez réessayer.'], 500);
        }

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/dossier/{id}/recuperer-effectifs-degagements-etablissement",
     *        name="dossier_recuperer_effectifs_degagements_etablissement",
     *        requirements={"id"="\d+"},
     *        methods={"POST"})
     *
     * @Security("is_granted('effectifs_degagements_doss') and is_granted('edit_doss', dossier)")
     */
    public function recupererEffectifsDegagementsEtablissement(
        Dossier $dossier,
        Request $request
    ): Response {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('recuperer_effectifs_degagements_'.$dossier->getId(), $csrfToken)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        if ($dossier->hasVerrou()) {
            return $this->json(['error' => 'Le dossier est verrouillé.'], 403);
        }

        $etablissements = $dossier->getEtablissements()->toArray();
        $nbEtablissements = count($etablissements);

        if (1 !== $nbEtablissements) {
            return $this->json([
                'error' => sprintf(
                    'Impossible de récupérer : %d établissement(s) lié(s) au dossier (1 attendu). Les effectifs et dégagements n\'ont pas été récupérés.',
                    $nbEtablissements
                ),
            ], 400);
        }

        $etablissement = $etablissements[0];

        if (!$this->isGranted('view_ets', $etablissement)) {
            return $this->json(['error' => 'Vous n\'avez pas le droit de consulter cet établissement.'], 403);
        }

        try {
            $sourceEtablissement = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT);
            $targetDossier = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);

            $this->descriptifCopier->copyFromEtablissementToDossier(
                $sourceEtablissement['rubriques'],
                $targetDossier['rubriques'],
                $dossier
            );
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la récupération des effectifs et dégagements établissement→dossier.', [
                'dossier_id' => $dossier->getId(),
                'etablissement_id' => $etablissement->getId(),
                'error' => $exception->getMessage(),
            ]);

            return $this->json(['error' => 'Erreur technique lors de la récupération. Veuillez réessayer.'], 500);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Retourne le contenu de la modale "Reprendre les effectifs et dégagements d'un dossier précédent".
     *
     * @Route("/dossier/{id}/effectifs-degagements/modal-reprise",
     *        name="dossier_effectifs_degagements_modal_reprise",
     *        requirements={"id"="\d+"},
     *        methods={"GET"})
     *
     * @Security("is_granted('effectifs_degagements_doss') and is_granted('edit_doss', dossier)")
     */
    public function effectifsDegagementsModalReprise(Dossier $dossier): Response
    {
        if ($dossier->hasVerrou()) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $etablissements = $dossier->getEtablissements();

        if (1 !== $etablissements->count()) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $etablissement = $etablissements->first();
        if (false === $etablissement) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $groupes = $this->dossierRepository->findByEtablissementGrouped($etablissement, $dossier);

        return $this->render('dossier/_modal_reprise_effectifs_degagements.html.twig', [
            'dossier' => $dossier,
            'etudes' => $groupes['etudes'],
        ]);
    }

    /**
     * Affichage des établissements et dossiers liés.
     *
     * @Route("/dossier/{id}/liees", name="dossier_liees", requirements={"id"="\d+"}, methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function liees(Dossier $dossier): Response
    {
        return $this->render('dossier/liees.html.twig', [
            'dossier' => $dossier,
            'liaisons' => $this->dossierLiaisonsService->getLiaisons($dossier),
        ]);
    }

    /**
     *
     * @Route("/dossier/{id}/liees/dossiers", name="dossier_liees_doss", requirements={"id"="\d+"}, methods={"GET", "POST"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function lieesDoss(Request $request, Dossier $dossier): Response
    {
        $disponibles = $this->dossierLiaisonsService->getDossiersDisponibles($dossier);

        $form = $this->createForm(DossierLiaisonsType::class, null, [
            'available_dossiers' => $disponibles,
            'action' => $this->generateUrl('dossier_liees_doss', ['id' => $dossier->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Collection<int, Dossier> $dossiersToAdd */
            $dossiersToAdd = $form->get('dossiersToAdd')->getData();
            $this->dossierLiaisonsService->addLiaisons($dossier, $dossiersToAdd->toArray());

            $this->addFlash('success', 'Les liaisons ont bien été enregistrées.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/liees_doss.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
            'hasDisponibles' => [] !== $disponibles,
        ]);
    }

    /**
     * Suppression d'une liaison entre deux dossiers.
     *
     * @Route("/dossier/{id}/liees/{idLie}/supprimer", name="dossier_liees_supprimer",
     *     requirements={"id"="\d+", "idLie"="\d+"},
     *     methods={"GET", "DELETE"},
     *     options={"expose"=true},
     *     defaults={"_dossier_lateral_nav": false}
     * )
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function supprimerLiaison(Request $request, Dossier $dossier, int $idLie): Response
    {
        $dossierLie = $this->dossierRepository->find($idLie);

        if (null === $dossierLie) {
            throw $this->createNotFoundException('Dossier lié introuvable.');
        }

        $liaisons = $this->dossierLiaisonsService->getLiaisons($dossier);
        $isLinked = false;
        foreach ($liaisons as $liaison) {
            if ($liaison->getId() === $dossierLie->getId()) {
                $isLinked = true;
                break;
            }
        }

        if (!$isLinked) {
            $this->addFlash('warning', 'Ce dossier n\'est pas lié au dossier courant.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        $form = $this->createFormBuilder()
            ->setMethod(Request::METHOD_DELETE)
            ->setAction($this->generateUrl('dossier_liees_supprimer', [
                'id' => $dossier->getId(),
                'idLie' => $dossierLie->getId(),
            ]))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dossierLiaisonsService->removeLiaison($dossier, $dossierLie);
            $this->addFlash('success', 'Le lien avec le dossier a bien été supprimé.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/liees_supprimer.html.twig', [
            'dossier' => $dossier,
            'dossierLie' => $dossierLie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Reprend les effectifs et dégagements d'un dossier source vers le dossier courant.
     *
     * @Route("/dossier/{id}/effectifs-degagements/reprendre-depuis-etude",
     *        name="dossier_effectifs_degagements_reprendre_depuis_etude",
     *        requirements={"id"="\d+"},
     *        methods={"POST"})
     *
     * @Security("is_granted('effectifs_degagements_doss') and is_granted('edit_doss', dossier)")
     */
    public function reprendreEffectifsDegagementsDepuisEtude(
        Dossier $dossier,
        Request $request
    ): Response {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('reprendre_effectifs_degagements_'.$dossier->getId(), $csrfToken)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        if ($dossier->hasVerrou()) {
            return $this->json(['error' => 'Le dossier est verrouillé.'], 403);
        }

        $rawDossierSelect = $request->request->get('dossierSelect', 0);
        $idDossierSource = is_scalar($rawDossierSelect) ? (int) $rawDossierSelect : 0;
        if (0 === $idDossierSource) {
            return $this->json(['error' => 'Aucun dossier source sélectionné.'], 400);
        }

        $dossierSource = $this->dossierRepository->find($idDossierSource);
        if (null === $dossierSource || null !== $dossierSource->getDateSuppression()) {
            return $this->json(['error' => 'Dossier source introuvable.'], 404);
        }

        if (!$this->isGranted('view_doss', $dossierSource)) {
            return $this->json(['error' => "Vous n'avez pas le droit de consulter le dossier source."], 403);
        }

        $erreurValidation = $this->repriseValidator->validerSource($dossier, $dossierSource);
        if (null !== $erreurValidation) {
            return $this->json(['error' => $erreurValidation], 400);
        }

        try {
            $sourceData = $this->descriptifService->getForDossier($dossierSource, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);
            $targetData = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);

            $this->descriptifCopier->copyFromDossierToDossier(
                $sourceData['rubriques'],
                $targetData['rubriques'],
                $dossier
            );
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la reprise des effectifs et dégagements depuis un autre dossier.', [
                'dossier_cible_id' => $dossier->getId(),
                'dossier_source_id' => $idDossierSource,
                'error' => $exception->getMessage(),
            ]);

            return $this->json(['error' => 'Erreur technique lors de la reprise. Veuillez réessayer.'], 500);
        }

        return $this->json(['success' => true]);
    }
}

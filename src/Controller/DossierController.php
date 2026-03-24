<?php

namespace App\Controller;

use App\Constant\CommissionTypeEvenement;
use App\Constant\DossierNature;
use App\Constant\TypeChangement;
use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Avis;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\PieceJointe;
use App\Entity\Utilisateur;
use App\Form\Dossier\DossierDescriptifType;
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
use App\Service\Alerte\AlerteService;
use App\Service\Commission\DateCommission;
use App\Service\Dossier as DossierService;
use App\Service\Dossier\DocumentConsulteTransferService;
use App\Service\Dossier\DossierAffectationService;
use App\Service\Dossier\DossierCreationService;
use App\Service\Dossier\DossierDonnantAvisService;
use App\Service\DossierDocumentManquantService;
use App\Service\DossierFields;
use App\Service\Etablissement;
use App\Service\Platau\PieceJointeExportService;
use App\Service\PlatauConsultation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
     * @var DossierDonnantAvisService
     */
    private $dossierDonnantAvisService;

    /**
     * @var AlerteService
     */
    private $alerteService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /** @var DossierService */
    private $dossierService;

    /** @var DossierRepository */
    private $dossierRepository;
    /**
     * @var DossierCreationService
     */
    private $dossierCreationService;

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
        DossierDonnantAvisService $dossierDonnantAvisService,
        AlerteService $alerteService,
        LoggerInterface $logger,
        UrlGeneratorInterface $urlGenerator,
        DossierService $dossierService,
        DossierRepository $dossierRepository,
        DossierCreationService $dossierCreationService
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
        $this->dossierDonnantAvisService = $dossierDonnantAvisService;
        $this->alerteService = $alerteService;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
        $this->dossierService = $dossierService;
        $this->dossierRepository = $dossierRepository;
        $this->dossierCreationService = $dossierCreationService;
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

        if (null !== $etablissement) {
            $dossier->addEtablissement($etablissement);
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
            $this->entityManager->beginTransaction();

            try {
                // Affectations commissions/visites + dénormalisation dates
                /** @var string|null $idAffectationCommission */
                $idAffectationCommission = $form->get('idAffectationDossierCommission')->getData();
                /** @var string|null $idAffectationVisite */
                $idAffectationVisite = $form->get('idAffectationDossierVisite')->getData();

                // Persist du dossier avant les opérations dépendantes de l'ID
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

                // Dossier donnant avis : gestion affectation
                /** @var int[] $etablissementsIds */
                $etablissementsIds = [];

                $repercuterAvis = (bool) $form->get('repercuterAvis')->getData();

                if ($dossier->donneAvis()) {
                    $etablissementsIds = $this->dossierDonnantAvisService->affecterDossierDonnantAvis($dossier, $repercuterAvis);
                }

                $this->entityManager->flush();
                $this->entityManager->commit();

                // Alerte "Visite Avant Ouverture favorable" si établissement en statut "Projet"
                $natureId = $dossier->getNature() instanceof DossierNatureListe ? $dossier->getNature()->getId() : null;
                $avisCommission = $dossier->getAvisCommission() instanceof Avis ? $dossier->getAvisCommission()->getId() : null;

                if (
                    1 === $avisCommission
                    && in_array($natureId, DossierNature::VISITE_AVANT_OUVERTURE, true)
                ) {
                    foreach ($dossier->getEtablissements() as $etab) {
                        $informations = $etab->getLatestInformations();
                        $statut = $informations->getStatut();

                        if ($statut && 1 === $statut->getId()) {
                            $libelle = $informations->getLibelle() ?: 'Établissement #'.$etab->getId();

                            $informationsEtablissement = $this->urlGenerator->generate(
                                'etablissement_informations',
                                ['id' => $etab->getId()],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            );

                            $this->addFlash('warning', sprintf(
                                '<strong>Avertissement</strong><br>'.
                                'La visite d\'avant ouverture étant favorable, vous devriez passer le statut de l\'établissement '.
                                '<a title="Ouvrir" href="%s">%s</a> à \'ouvert\' (statut actuellement à \'projet\').',
                                $informationsEtablissement,
                                htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8')
                            ));
                        }
                    }
                }

                // Alertes email si changement d'avis (flash messages)
                if ($mailEnabled && [] !== $etablissementsIds && $this->isGranted('alerte_avis')) {
                    foreach ($dossier->getEtablissements() as $etab) {
                        $etablissementId = $etab->getId();

                        if (!in_array($etablissementId, $etablissementsIds, true)) {
                            continue;
                        }

                        $dossierDonnantAvis = $etab->getDossierDonnantAvis();
                        $nouvelAvis = $dossierDonnantAvis ? $dossierDonnantAvis->getAvisCommission() : null;

                        $informations = $etab->getLatestInformations();
                        $libelle = $informations->getLibelle() ?: 'Établissement #'.$etablissementId;

                        $lienAlerter = $this->alerteService->getLink(
                            TypeChangement::AVIS,
                            $etablissementId
                        );

                        $this->addFlash('success', sprintf(
                            '<p>L\'établissement %s a bien été mis à jour.</p>%s',
                            $libelle,
                            $lienAlerter
                        ));
                    }
                }

                $this->addFlash('success', 'Le dossier a été créé avec succès.');

                if ([] !== $etablissementsIds) {
                    $etablissementsJson = json_encode($etablissementsIds);
                    if (false === $etablissementsJson) {
                        throw new \RuntimeException('Impossible d\'encoder les IDs établissements en JSON');
                    }

                    return $this->redirect(sprintf(
                        '/legacy/dossier/vider-cache?id=%d&etablissements=%s',
                        $dossier->getId(),
                        urlencode($etablissementsJson)
                    ));
                }

                return $this->redirectToRoute('dossier_index', ['id' => $dossier->getId()]);
            } catch (\Exception $e) {
                $this->entityManager->rollback();

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

                // Dossier donnant avis : gestion affectation/désaffectation
                // Récupérer la valeur de la checkbox repercuterAvis
                $repercuterAvis = (bool) $form->get('repercuterAvis')->getData();

                // Liste des IDs établissements modifiés (pour vidage cache legacy)
                /** @var int[] $etablissementsIds */
                $etablissementsIds = [];

                // Cas 1 : Nouvelle nature donne avis → Affectation
                if ($dossier->donneAvis()) {
                    $etablissementsIds = $this->dossierDonnantAvisService->affecterDossierDonnantAvis($dossier, $repercuterAvis);
                }
                // Cas 2 : Changement de nature donnant avis → non donnant avis → Désaffectation
                // Note : Le elseif garantit qu'on ne passe ici QUE si nouvelle nature ne donne PAS avis
                elseif (null !== $ancienneNature) {
                    $etablissementsIds = $this->dossierDonnantAvisService->desaffecterDossierDonnantAvis($dossier, $ancienneNature);
                }

                // Persist et flush toutes les modifications
                $this->entityManager->persist($dossier);
                $this->entityManager->flush();

                // Commit transaction
                $this->entityManager->commit();

                // Alerte "Visite Avant Ouverture favorable" si établissement en statut "Projet"
                $natureId = $dossier->getNature() instanceof DossierNatureListe ? $dossier->getNature()->getId() : null;
                $avisCommission = $dossier->getAvisCommission() instanceof Avis ? $dossier->getAvisCommission()->getId() : null;

                if (
                    1 === $avisCommission // Avis favorable
                    && in_array($natureId, DossierNature::VISITE_AVANT_OUVERTURE, true) // Nature 47 ou 48
                ) {
                    foreach ($dossier->getEtablissements() as $etablissement) {
                        $informations = $etablissement->getLatestInformations();
                        $statut = $informations->getStatut();

                        // Si établissement en statut "Projet" (ID=1)
                        if ($statut && 1 === $statut->getId()) {
                            $libelle = $informations->getLibelle() ?: 'Établissement #'.$etablissement->getId();

                            $informationsEtablissement = $this->urlGenerator->generate('etablissement_informations', ['id' => $etablissement->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                            $this->addFlash('warning', sprintf(
                                '<strong>Avertissement</strong><br>'.
                                'La visite d\'avant ouverture étant favorable, vous devriez passer le statut de l\'établissement '.
                                '<a title="Ouvrir" href="%s">%s</a> à \'ouvert\' (statut actuellement à \'projet\').',
                                $informationsEtablissement,
                                htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8')
                            ));
                        }
                    }
                }

                // Alertes email si changement d'avis (flash messages)
                if ($mailEnabled && [] !== $etablissementsIds && $this->isGranted('alerte_avis')) {
                    // Vérifier pour chaque établissement modifié si l'avis a changé
                    foreach ($dossier->getEtablissements() as $etablissement) {
                        $etablissementId = $etablissement->getId();

                        // Uniquement établissements qui ont été modifiés par affectation
                        if (!in_array($etablissementId, $etablissementsIds, true)) {
                            continue;
                        }

                        // Récupérer nouvel avis
                        $dossierDonnantAvis = $etablissement->getDossierDonnantAvis();
                        $nouvelAvis = $dossierDonnantAvis ? $dossierDonnantAvis->getAvisCommission() : null;

                        // Comparer avec ancien avis
                        $ancienAvis = $anciensAvis[$etablissementId] ?? null;

                        // Si avis a changé, ajouter flash avec lien "Alerter"
                        if ($ancienAvis !== $nouvelAvis) {
                            $informations = $etablissement->getLatestInformations();
                            $libelle = $informations->getLibelle() ?: 'Établissement #'.$etablissementId;

                            $lienAlerter = $this->alerteService->getLink(
                                TypeChangement::AVIS,
                                $etablissementId
                            );

                            $this->addFlash('success', sprintf(
                                '<p>L\'établissement %s a bien été mis à jour.</p>%s',
                                $libelle,
                                $lienAlerter
                            ));
                        }
                    }
                }

                // Redirect vers legacy pour vidage cache établissements (si modifications)
                $this->addFlash('success', 'Le dossier a été mis à jour avec succès.');

                if ([] !== $etablissementsIds) {
                    // Rediriger vers route legacy pour vidage cache + redirect Symfony
                    $etablissementsJson = json_encode($etablissementsIds);
                    if (false === $etablissementsJson) {
                        throw new \RuntimeException('Impossible d\'encoder les IDs établissements en JSON');
                    }

                    return $this->redirect(sprintf(
                        '/legacy/dossier/vider-cache?id=%d&etablissements=%s',
                        $dossier->getId(),
                        urlencode($etablissementsJson)
                    ));
                }

                // Cas sans modification établissements : redirect direct vers page dossier
                return $this->redirectToRoute('dossier_index', ['id' => $dossier->getId()]);
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
}

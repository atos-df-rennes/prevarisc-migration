<?php

namespace App\Controller\Dossier;

use App\Constant\CommissionTypeEvenement;
use App\Constant\DossierNature;
use App\Constant\NomInterneCapsule;
use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Dto\Descriptif\DescriptifFormData;
use App\Entity\AvisDerogations;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\PieceJointe;
use App\Entity\Utilisateur;
use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;
use App\Form\Dossier\DossierDescriptifType;
use App\Form\Dossier\DossierTextesApplicablesType;
use App\Form\Dossier\DossierType;
use App\Repository\AvisDerogationsRepository;
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
use App\Service\Dossier as DossierService;
use App\Service\Dossier\DocumentConsulteTransferService;
use App\Service\Dossier\DossierAffectationService;
use App\Service\Dossier\DossierCreationService;
use App\Service\Dossier\DossierSauvegardeService;
use App\Service\Dossier\DossierSuppressionService;
use App\Service\DossierDocumentManquantService;
use App\Service\DossierFields;
use App\Service\EtablissementHistoriqueService;
use App\Service\Platau\PieceJointeExportService;
use App\Service\PlatauConsultation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DossierController extends AbstractController implements DossierLateralNavigationInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PlatauConsultation $platauConsultationService, private readonly DateCommission $dateCommissionService, private readonly DossierTypeRepository $dossierTypeRepository, private readonly AvisRepository $avisRepository, private readonly AvisDerogationsRepository $avisDerogationsRepository, private readonly CommissionTypeRepository $commissionTypeRepository, private readonly CommissionRepository $commissionRepository, private readonly EtablissementRepository $etablissementRepository, private readonly UtilisateurRepository $utilisateurRepository, private readonly DossierFields $dossierFieldsService, private readonly DossierDocumentManquantService $documentManquantService, private readonly PieceJointeExportService $pieceJointeExportService, private readonly DossierAffectationService $dossierAffectationService, private readonly DossierAffectationRepository $dossierAffectationRepository, private readonly LoggerInterface $logger, private readonly DossierService $dossierService, private readonly DossierRepository $dossierRepository, private readonly DossierCreationService $dossierCreationService, private readonly DossierSauvegardeService $dossierSauvegardeService, private readonly DossierSuppressionService $dossierSuppressionService, private readonly TagAwareCacheInterface $searchCache, private readonly DescriptifService $descriptifService, private readonly DescriptifFormHydrator $descriptifFormHydrator, private readonly DescriptifFormBuilder $descriptifFormBuilder, private readonly DescriptifFormPersister $descriptifFormPersister, private readonly FormFactoryInterface $formFactory, private readonly EtablissementHistoriqueService $etablissementHistoriqueService, private readonly bool $deactivatePlatau = false)
    {
    }

    /**
     * Affichage des informations générales d'un dossier (lecture seule).
     */
    #[Route(path: '/dossier/{id}', name: 'dossier_index', requirements: ['id' => '\d+'], methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function index(Dossier $dossier): Response
    {
        $afficherAvis = !$dossier->hasAbsenceDeQuorum()
            && !$dossier->isHorsDelai()
            && !$dossier->hasAvisDiffere()
            && !$dossier->isIncomplet()
            && !$dossier->hasNePeutSePrononcer()
        ;

        if ($this->deactivatePlatau && null !== $dossier->getConsultationPlatau()) {
            throw new \Exception("Plat'AU est désactivé, vous ne pouvez pas voir ce dossier");
        }

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
     */
    #[Route(path: '/dossier/ajouter', name: 'dossier_new', methods: ['GET', 'POST'], defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted('add_dossier')]
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
            $commissionsParType[$type->getIdOrFail()] = [
                'libelle' => $type->getLibelle(),
                'commissions' => $this->commissionRepository->findBy(['type' => $type]),
            ];
        }

        $fieldsConfig = $this->dossierFieldsService->getAllFieldsByNature();

        $enteteEtab = $etablissement;
        $lastAvisEtab = '';

        if (null !== $etablissement) {
            $lastAvisEtab = $this->etablissementHistoriqueService->getLastAvisLibelle($etablissement->getId());
        }

        return $this->render('dossier/new.html.twig', [
            'dossier' => $dossier,
            'form' => $form,
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
     */
    #[Route(path: '/dossier/{id}/modifier', name: 'dossier_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function edit(
        Request $request,
        Dossier $dossier,
        DocumentConsulteTransferService $documentConsulteTransferService,
        bool $mailEnabled
    ): Response {
        // Vérification Plat'AU désactivé
        if ($this->deactivatePlatau && null !== $dossier->getConsultationPlatau()) {
            throw new \Exception("Plat'AU est désactivé, vous ne pouvez pas voir ce dossier");
        }

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

                $platauConsultation = $dossier->getConsultationPlatau();
                if (null !== $platauConsultation) {
                    $platauRetryPec = (bool) $form->get('platauRetryPec')->getData();
                    if ($platauRetryPec) {
                        $platauConsultation->setStatutPriseEnCompte(PlatauStatutPecEnum::A_RENVOYER);
                    }

                    $platauRetryAvis = (bool) $form->get('platauRetryAvis')->getData();
                    if ($platauRetryAvis) {
                        $platauConsultation->setStatutAvis(PlatauStatutAvisEnum::A_RENVOYER);
                    }
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
            $commissionsParType[$type->getIdOrFail()] = [
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
                    $lastAvisEtab = $this->etablissementHistoriqueService->getLastAvisLibelle($enteteEtab->getId());
                }
            }
        }

        // @todo: Peut être utilisé directement en Twig ?
        // Récupération des informations de l'utilisateur connecté
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Vérification des permissions
        $isAllowedChangeAvis = $this->isGranted('edit_avis_com');
        $isAllowedDeleteDossier = $this->isGranted('delete_dossier');

        // Informations sur le verrouillage
        $userInfoVerrou = null;
        if ($dossier->hasVerrou() && $dossier->getUtilisateurVerrou()) {
            $userInfoVerrou = $this->utilisateurRepository->find($dossier->getUtilisateurVerrou());
        }

        // Configuration des champs pour JavaScript
        $fieldsConfig = $this->dossierFieldsService->getAllFieldsByNature();

        // Informations Plat'AU
        $platauData = $this->platauConsultationService->getPlatauDataForView($dossier->getConsultationPlatau());

        // Dates de commission liées
        $datesCommissionLiees = $this->dateCommissionService->getDatesLiees($dossier);

        return $this->render('dossier/edit.html.twig', [
            'dossier' => $dossier,
            'form' => $form,
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

    #[Route(path: '/dossier/{id}/verrouiller', name: 'dossier_verrouiller', defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted('verrouillage_dossier', subject: 'dossier')]
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

    #[Route(path: '/dossier/{id}/deverrouiller', name: 'dossier_deverrouiller', defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted('verrouillage_dossier', subject: 'dossier')]
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

    #[Route(path: '/dossier/{id}/descriptif', name: 'dossier_descriptif', methods: ['GET'])]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function descriptif(Dossier $dossier): Response
    {
        return $this->render('dossier/descriptif/descriptif.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    #[Route(path: '/dossier/{id}/descriptif/modifier', name: 'dossier_descriptif_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit_doss', subject: 'dossier')]
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
            'form' => $form,
        ]);
    }

    #[Route(path: '/dossier/{id}/textes-applicables', name: 'dossier_textes_applicables', methods: ['GET'])]
    #[IsGranted('view_doss', subject: 'dossier')]
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

    #[Route(path: '/dossier/{id}/textes-applicables/modifier', name: 'dossier_textes_applicables_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit_doss_ignore_verrou', subject: 'dossier')]
    public function editTextesApplicables(Request $request, Dossier $dossier): Response
    {
        // Pré-charge les relations
        if (null !== $dossier->getId()) {
            $this->dossierRepository->getTextesApplicablesByType($dossier->getId());
        }

        $form = $this->createForm(DossierTextesApplicablesType::class, $dossier);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Synchronise les textes applicables vers le premier établissement (pour visites et groupes de visites)
            // avant le flush pour éviter les transactions multiples
            $this->dossierCreationService->synchroniserTextesApplicablesVersEtablissement($dossier);
            $this->entityManager->flush();

            $this->addFlash('success', 'Les textes applicables ont été mis à jour avec succès.');

            return $this->redirectToRoute('dossier_textes_applicables', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/textes_applicables/edit_textes_applicables.html.twig', [
            'dossier' => $dossier,
            'form' => $form,
        ]);
    }

    /**
     * Affiche l'onglet Vérifications techniques du dossier.
     */
    #[Route(path: '/dossier/{id}/verifications-techniques', name: 'dossier_verifications_techniques', methods: ['GET'])]
    #[IsGranted('verifications_techniques')]
    #[IsGranted('view_doss', subject: 'dossier')]
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
     */
    #[Route(path: '/dossier/{id}/verifications-techniques/modifier', name: 'dossier_verifications_techniques_edit', methods: ['GET', 'POST'])]
    #[IsGranted('verifications_techniques')]
    #[IsGranted('edit_doss_ignore_verrou', subject: 'dossier')]
    public function editVerificationsTechniques(Dossier $dossier, Request $request): Response
    {
        $data = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES);

        $formData = $this->descriptifFormHydrator->hydrateFromDossier($dossier, $data['rubriques']);
        $form = $this->descriptifFormBuilder->buildForm($this->formFactory, $formData, $data['rubriques']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var DescriptifFormData $submittedData */
                $submittedData = $form->getData();
                $this->descriptifFormPersister->persistForDossier($dossier, $submittedData, $data['rubriques']);
                $this->addFlash('success', 'Les vérifications techniques ont bien été mises à jour.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la sauvegarde des vérifications techniques', [
                    'dossier_id' => $dossier->getId(),
                    'exception' => $e->getMessage(),
                ]);
                $this->addFlash('danger', 'Les vérifications techniques n\'ont pas été mises à jour. Veuillez réessayer.');
            }

            return $this->redirectToRoute('dossier_verifications_techniques', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/edit_verifications_techniques.html.twig', [
            'dossier' => $dossier,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
            'form' => $form,
        ]);
    }

    /**
     * Affiche et gère les avis et dérogations d'un dossier.
     */
    #[Route(path: '/dossier/{id}/avis-et-derogations', name: 'dossier_avis_et_derogations', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('avis_derogations')]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function avisEtDerogations(Request $request, Dossier $dossier): Response
    {
        $listeAvis = $this->avisRepository->findAll();
        $etablissement = $dossier->getEtablissements()->first() ?: null;
        $dossiersGroupes = $etablissement
            ? $this->dossierRepository->findByEtablissementGrouped($etablissement, $dossier)
            : ['etudes' => [], 'visites' => [], 'autres' => []];

        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('edit_doss_ignore_verrou', $dossier);

            $data = $request->request->all();

            /** @var string $csrfToken */
            $csrfToken = $data['_token'] ?? '';
            if (!$this->isCsrfTokenValid('avis_derogation_create_'.$dossier->getId(), $csrfToken)) {
                throw $this->createAccessDeniedException('Token de sécurité invalide.');
            }

            $avisDerogation = new AvisDerogations();
            $avisDerogation->setDossier($dossier);

            /** @var string $type */
            $type = $data['TYPE'] ?? 'Avis';
            $avisDerogation->setType($type);

            /** @var string|null $titre */
            $titre = $data['TITRE'] ?? null;
            $avisDerogation->setTitre($titre);

            /** @var string|null $objet */
            $objet = $data['OBJET'] ?? null;
            $avisDerogation->setObjet($objet);

            /** @var string|null $justification */
            $justification = $data['JUSTIFICATION'] ?? null;
            $avisDerogation->setJustification($justification);

            /** @var string|null $articlesConcernes */
            $articlesConcernes = $data['ARTICLES_CONCERNES'] ?? null;
            $avisDerogation->setArticlesConcernes($articlesConcernes);

            /** @var string|null $mesuresCompensatoiresProposees */
            $mesuresCompensatoiresProposees = $data['MESURES_COMPENSATOIRES_PROPOSEES'] ?? null;
            $avisDerogation->setMesuresCompensatoiresProposees($mesuresCompensatoiresProposees);

            /** @var string|null $analyseDeRisque */
            $analyseDeRisque = $data['ANALYSE_DE_RISQUE'] ?? null;
            $avisDerogation->setAnalyseDeRisque($analyseDeRisque);

            $avisDerogation->setDisplayHistorique((bool) ($data['DISPLAY_HISTORIQUE'] ?? false));

            $rawAvisId = $data['AVIS'] ?? null;
            $avis = is_numeric($rawAvisId) ? $this->avisRepository->find((int) $rawAvisId) : null;
            $avisDerogation->setAvis($avis);

            $rawDossierLieId = $data['ID_DOSSIER_LIE'] ?? null;
            $dossierLie = is_numeric($rawDossierLieId) ? $this->dossierRepository->find((int) $rawDossierLieId) : null;
            if (null !== $dossierLie && (null === $etablissement || !$dossierLie->getEtablissements()->contains($etablissement))) {
                $dossierLie = null;
            }

            $avisDerogation->setDossierLie($dossierLie);

            $this->entityManager->persist($avisDerogation);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'avis ou la dérogation a été ajouté(e) avec succès.');

            return $this->redirectToRoute('dossier_avis_et_derogations', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/avis_et_derogations/index.html.twig', [
            'dossier' => $dossier,
            'arrayAvisDerogations' => $this->avisDerogationsRepository->findByDossier($dossier),
            'listeAvis' => $listeAvis,
            'dossiersGroupes' => $dossiersGroupes,
        ]);
    }

    /**
     * Édite un avis ou une dérogation.
     */
    #[Route(path: '/dossier/{id}/avis-et-derogations/{avisDerogationId}/modifier', name: 'dossier_avis_et_derogations_edit', requirements: ['id' => '\d+', 'avisDerogationId' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('avis_derogations')]
    #[IsGranted('edit_doss_ignore_verrou', subject: 'dossier')]
    public function avisEtDerogationsEdit(Request $request, Dossier $dossier, int $avisDerogationId): Response
    {
        $avisDerogation = $this->avisDerogationsRepository->findOneBy(['id' => $avisDerogationId, 'dossier' => $dossier]);
        if (null === $avisDerogation) {
            throw $this->createNotFoundException('Avis ou dérogation introuvable.');
        }

        $listeAvis = $this->avisRepository->findAll();
        $etablissement = $dossier->getEtablissements()->first() ?: null;
        $dossiersGroupes = $etablissement
            ? $this->dossierRepository->findByEtablissementGrouped($etablissement, $dossier)
            : ['etudes' => [], 'visites' => [], 'autres' => []];

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            /** @var string $csrfToken */
            $csrfToken = $data['_token'] ?? '';
            if (!$this->isCsrfTokenValid('avis_derogation_edit_'.$avisDerogation->getId(), $csrfToken)) {
                throw $this->createAccessDeniedException('Token de sécurité invalide.');
            }

            /** @var string $type */
            $type = $data['TYPE'] ?? $avisDerogation->getType();
            $avisDerogation->setType($type);

            /** @var string|null $titre */
            $titre = $data['TITRE'] ?? null;
            $avisDerogation->setTitre($titre);

            /** @var string|null $objet */
            $objet = $data['OBJET'] ?? null;
            $avisDerogation->setObjet($objet);

            /** @var string|null $justification */
            $justification = $data['JUSTIFICATION'] ?? null;
            $avisDerogation->setJustification($justification);

            /** @var string|null $articlesConcernes */
            $articlesConcernes = $data['ARTICLES_CONCERNES'] ?? null;
            $avisDerogation->setArticlesConcernes($articlesConcernes);

            /** @var string|null $mesuresCompensatoiresProposees */
            $mesuresCompensatoiresProposees = $data['MESURES_COMPENSATOIRES_PROPOSEES'] ?? null;
            $avisDerogation->setMesuresCompensatoiresProposees($mesuresCompensatoiresProposees);

            /** @var string|null $analyseDeRisque */
            $analyseDeRisque = $data['ANALYSE_DE_RISQUE'] ?? null;
            $avisDerogation->setAnalyseDeRisque($analyseDeRisque);

            $avisDerogation->setDisplayHistorique((bool) ($data['DISPLAY_HISTORIQUE'] ?? false));

            $rawAvisId = $data['AVIS'] ?? null;
            $avis = is_numeric($rawAvisId) ? $this->avisRepository->find((int) $rawAvisId) : null;
            $avisDerogation->setAvis($avis);

            // ID_DOSSIER_LIE absent du POST = désélectionné
            $rawDossierLieId = $data['ID_DOSSIER_LIE'] ?? null;
            $dossierLie = is_numeric($rawDossierLieId) ? $this->dossierRepository->find((int) $rawDossierLieId) : null;
            if (null !== $dossierLie && (null === $etablissement || !$dossierLie->getEtablissements()->contains($etablissement))) {
                $dossierLie = null;
            }

            $avisDerogation->setDossierLie($dossierLie);

            $this->entityManager->flush();

            $this->addFlash('success', 'L\'avis ou la dérogation a été modifié(e) avec succès.');

            return $this->redirectToRoute('dossier_avis_et_derogations', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/avis_et_derogations/edit.html.twig', [
            'dossier' => $dossier,
            'avisDerogation' => $avisDerogation,
            'listeAvis' => $listeAvis,
            'dossiersGroupes' => $dossiersGroupes,
        ]);
    }

    /**
     * Supprime un avis ou une dérogation (appel AJAX POST).
     */
    #[Route(path: '/dossier/{id}/avis-et-derogations/{avisDerogationId}/supprimer', name: 'dossier_avis_et_derogations_delete', requirements: ['id' => '\d+', 'avisDerogationId' => '\d+'], methods: ['POST'])]
    #[IsGranted('avis_derogations')]
    #[IsGranted('edit_doss_ignore_verrou', subject: 'dossier')]
    public function avisEtDerogationsDelete(Request $request, Dossier $dossier, int $avisDerogationId): Response
    {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_avis_derogation_'.$avisDerogationId, $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        $avisDerogation = $this->avisDerogationsRepository->findOneBy(['id' => $avisDerogationId, 'dossier' => $dossier]);
        if (null !== $avisDerogation) {
            $this->entityManager->remove($avisDerogation);
            $this->entityManager->flush();
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Suppression logique d'un dossier.
     *
     * Port direct de DossierController::deleteAction() (legacy).
     * Suppression logique (date de suppression + auteur), nettoyage des affectations commission,
     * recalcul du dossier donnant avis pour les établissements concernés, invalidation du cache.
     */
    #[Route(path: '/dossier/{id}/supprimer', name: 'dossier_supprimer', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted('delete_dossier')]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function supprimer(Request $request, Dossier $dossier): Response
    {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('dossier_suppression_'.$dossier->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Ce dossier est verrouillé et ne peut pas être supprimé.');
        }

        /** @var Utilisateur $user */
        $user = $this->getUser();

        try {
            // @fixme: variable non utilisée mais conservée une fois les caches entité implémentés à nouveau
            /** @var int[] $etablissementsIds */
            $etablissementsIds = $this->entityManager->wrapInTransaction(
                fn (): array => $this->dossierSuppressionService->supprimer($dossier, $user)
            );

            $this->addFlash('success', 'Le dossier a bien été supprimé.');

            $this->searchCache->invalidateTags(['search']);

            return $this->redirectToRoute('search_dossier', ['objet' => '']);
        } catch (\Throwable $throwable) {
            $this->logger->error('Erreur lors de la suppression du dossier', [
                'dossier_id' => $dossier->getId(),
                'exception' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            $this->addFlash('danger', 'Le dossier n\'a pas été supprimé. Veuillez réessayer ou contacter le service informatique.');

            return $this->redirectToRoute('dossier_index', ['id' => $dossier->getId()]);
        }
    }
}

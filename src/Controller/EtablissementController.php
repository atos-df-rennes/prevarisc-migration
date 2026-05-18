<?php

namespace App\Controller;

use App\Constant\DossierType;
use App\Constant\GenreEtablissement;
use App\Constant\NomInterneCapsule;
use App\Controller\Contract\EtablissementLateralNavigationInterface;
use App\Dto\Descriptif\DescriptifFormData;
use App\Entity\Commission;
use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\Utilisateur;
use App\Form\DescriptifOriginalType;
use App\Form\TextesApplicablesEtablissementType;
use App\Repository\AvisRepository;
use App\Repository\ClassementRepository;
use App\Repository\CommissionRepository;
use App\Repository\CoucheCartoRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementActiviteRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\EtablissementCategorieRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\EtablissementRepository;
use App\Repository\EtablissementStatutRepository;
use App\Repository\EtablissementTypeRepository;
use App\Repository\FamilleRepository;
use App\Repository\GenreRepository;
use App\Repository\GroupementRepository;
use App\Repository\IghClasseRepository;
use App\Repository\PeriodiciteRepository;
use App\Repository\TypePlanRepository;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\Alerte\AlerteService;
use App\Service\DataStore\DataStore;
use App\Service\Descriptif as DescriptifService;
use App\Service\Descriptif\DescriptifFormBuilder;
use App\Service\Descriptif\DescriptifFormHydrator;
use App\Service\Descriptif\DescriptifFormPersister;
use App\Service\Dossier;
use App\Service\Etablissement as EtablissementService;
use App\Service\EtablissementHistoriqueService;
use App\Service\EtablissementPieceJointeService;
use App\Service\Mail\MailerConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class EtablissementController extends AbstractController implements EtablissementLateralNavigationInterface
{
    /** @var EtablissementPieceJointeRepository */
    private $etablissementPieceJointeRepository;

    /** @var EtablissementAdresseRepository */
    private $etablissementAdresseRepository;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var EtablissementService */
    private $etablissementService;

    /** @var EtablissementHistoriqueService */
    private $etablissementHistoriqueService;

    /** @var EtablissementPieceJointeService */
    private $etablissementPieceJointeService;

    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @var CoucheCartoRepository
     */
    private $coucheCartoRepository;

    /**
     * @var CommissionRepository
     */
    private $commissionRepository;

    /**
     * @var PeriodiciteRepository
     */
    private $periodiciteRepository;

    /**
     * @var GroupementRepository
     */
    private $groupementRepository;

    /**
     * @var DataStore
     */
    private $dataStore;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /** @var Dossier */
    private $dossierService;

    /**
     * @var MailerConfiguration
     */
    private $mailerConfig;

    /**
     * @var AlerteService
     */
    private $alerteService;

    /** @var int */
    private $nbDossiersAAfficher;

    /** @var DescriptifService */
    private $descriptifService;

    /** @var DescriptifFormHydrator */
    private $descriptifFormHydrator;

    /** @var DescriptifFormBuilder */
    private $descriptifFormBuilder;

    /** @var DescriptifFormPersister */
    private $descriptifFormPersister;

    /** @var bool */
    private $descriptifPersonnalise;

    /** @var LoggerInterface */
    private $logger;

    /** @var LoggerInterface */
    private $pieceJointeLogger;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /** @var GenreRepository */
    private $genreRepository;

    /** @var EtablissementStatutRepository */
    private $statutRepository;

    /** @var AvisRepository */
    private $avisRepository;

    /** @var EtablissementCategorieRepository */
    private $categorieRepository;

    /** @var EtablissementTypeRepository */
    private $typeRepository;

    /** @var EtablissementActiviteRepository */
    private $activiteRepository;

    /** @var TypePlanRepository */
    private $typePlanRepository;

    /** @var FamilleRepository */
    private $familleRepository;

    /** @var IghClasseRepository */
    private $ighClasseRepository;

    /** @var ClassementRepository */
    private $classementRepository;

    public function __construct(
        EtablissementPieceJointeRepository $etablissementPieceJointeRepository,
        EtablissementAdresseRepository $etablissementAdresseRepository,
        DossierRepository $dossierRepository,
        EtablissementService $etablissementService,
        EtablissementRepository $etablissementRepository,
        EntityManagerInterface $entityManager,
        Dossier $dossierService,
        CoucheCartoRepository $coucheCartoRepository,
        CommissionRepository $commissionRepository,
        PeriodiciteRepository $periodiciteRepository,
        GroupementRepository $groupementRepository,
        DataStore $dataStore,
        SerializerInterface $serializer,
        MailerConfiguration $mailerConfig,
        AlerteService $alerteService,
        int $nbDossiersAAfficher,
        DescriptifService $descriptifService,
        DescriptifFormHydrator $descriptifFormHydrator,
        DescriptifFormBuilder $descriptifFormBuilder,
        DescriptifFormPersister $descriptifFormPersister,
        bool $descriptifPersonnalise,
        LoggerInterface $logger,
        FormFactoryInterface $formFactory,
        LoggerInterface $pieceJointeLogger,
        EtablissementHistoriqueService $etablissementHistoriqueService,
        EtablissementPieceJointeService $etablissementPieceJointeService,
        GenreRepository $genreRepository,
        EtablissementStatutRepository $statutRepository,
        AvisRepository $avisRepository,
        EtablissementCategorieRepository $categorieRepository,
        EtablissementTypeRepository $typeRepository,
        EtablissementActiviteRepository $activiteRepository,
        TypePlanRepository $typePlanRepository,
        FamilleRepository $familleRepository,
        IghClasseRepository $ighClasseRepository,
        ClassementRepository $classementRepository
    ) {
        $this->etablissementPieceJointeRepository = $etablissementPieceJointeRepository;
        $this->etablissementAdresseRepository = $etablissementAdresseRepository;
        $this->dossierRepository = $dossierRepository;
        $this->etablissementService = $etablissementService;
        $this->etablissementRepository = $etablissementRepository;
        $this->entityManager = $entityManager;
        $this->dossierService = $dossierService;
        $this->coucheCartoRepository = $coucheCartoRepository;
        $this->commissionRepository = $commissionRepository;
        $this->periodiciteRepository = $periodiciteRepository;
        $this->groupementRepository = $groupementRepository;
        $this->dataStore = $dataStore;
        $this->serializer = $serializer;
        $this->mailerConfig = $mailerConfig;
        $this->alerteService = $alerteService;
        $this->nbDossiersAAfficher = $nbDossiersAAfficher;
        $this->descriptifService = $descriptifService;
        $this->descriptifFormHydrator = $descriptifFormHydrator;
        $this->descriptifFormBuilder = $descriptifFormBuilder;
        $this->descriptifFormPersister = $descriptifFormPersister;
        $this->descriptifPersonnalise = $descriptifPersonnalise;
        $this->logger = $logger;
        $this->formFactory = $formFactory;
        $this->pieceJointeLogger = $pieceJointeLogger;
        $this->etablissementHistoriqueService = $etablissementHistoriqueService;
        $this->etablissementPieceJointeService = $etablissementPieceJointeService;
        $this->genreRepository = $genreRepository;
        $this->statutRepository = $statutRepository;
        $this->avisRepository = $avisRepository;
        $this->categorieRepository = $categorieRepository;
        $this->typeRepository = $typeRepository;
        $this->activiteRepository = $activiteRepository;
        $this->typePlanRepository = $typePlanRepository;
        $this->familleRepository = $familleRepository;
        $this->ighClasseRepository = $ighClasseRepository;
        $this->classementRepository = $classementRepository;
    }

    /**
     * Affiche le formulaire d'ajout d'un établissement (GET) et traite la soumission (POST).
     *
     * @Route("/etablissement/ajouter", name="etablissement_ajouter", methods={"GET","POST"}, defaults={"_etablissement_lateral_nav": false})
     */
    public function add(Request $request): Response
    {
        $this->denyAccessUnlessGranted(EtablissementVoter::CREATE);

        // Données de référence pour le formulaire
        $genres = $this->genreRepository->findAll();
        $statuts = $this->statutRepository->findAll();
        $avis = $this->avisRepository->findAll();
        $categories = $this->categorieRepository->findAll();
        $types = $this->typeRepository->findAll();
        $activites = $this->activiteRepository->findAll();
        // JSON des activités au format legacy pour le JS de add.html.twig
        $activitesJson = [];
        foreach ($activites as $activite) {
            $activiteType = $activite->getType();
            $activitesJson[] = [
                'ID_TYPE' => $activiteType ? $activiteType->getId() : null,
                'ID_TYPEACTIVITE' => $activite->getId(),
                'LIBELLE_ACTIVITE' => $activite->getLibelle(),
            ];
        }

        // Commissions groupées par type pour les optgroups du select
        /** @var array<int, array{libelle: string, items: Commission[]}> $commissionsData */
        $commissionsData = [];
        foreach ($this->commissionRepository->findAll() as $commission) {
            $type = $commission->getType();
            $typeId = $type ? (int) $type->getId() : 0;
            $typeLibelle = $type ? (string) $type->getLibelle() : '';
            if (!isset($commissionsData[$typeId])) {
                $commissionsData[$typeId] = ['libelle' => $typeLibelle, 'items' => []];
            }

            $commissionsData[$typeId]['items'][] = $commission;
        }

        $typesPlans = $this->typePlanRepository->findAll();
        $familles = $this->familleRepository->findAll();
        $ighClasses = $this->ighClasseRepository->findAll();
        $classements = $this->classementRepository->findAllOrderedByLibelle();
        $couchesCartographiques = $this->coucheCartoRepository->findAllOrdered();

        // Vérifie si l'utilisateur a le droit de modifier le statut
        $canEditStatut = $this->isGranted('edit_statut');

        // Paramètres de géolocalisation IGN
        $keyIgn = $this->getParameter('app.ign_key');
        $geoconceptUrl = $this->getParameter('app.geoconcept_url');
        $defaultLon = $this->getParameter('app.carto_default_lon');
        $defaultLat = $this->getParameter('app.carto_default_lat');

        if ($request->isMethod('POST')) {
            // F4 : vérification du jeton CSRF
            /** @var string|null $csrfToken */
            $csrfToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('add_etablissement', $csrfToken)) {
                $this->addFlash('danger', 'Token CSRF invalide. Veuillez réessayer.');

                return $this->render('etablissement/add.html.twig', [
                    'genres' => $genres,
                    'statuts' => $statuts,
                    'avis' => $avis,
                    'categories' => $categories,
                    'types' => $types,
                    'activites' => $activites,
                    'activites_json' => json_encode($activitesJson),
                    'commissions' => $commissionsData,
                    'types_plans' => $typesPlans,
                    'familles' => $familles,
                    'igh_classes' => $ighClasses,
                    'classements' => $classements,
                    'couches_cartographiques' => $couchesCartographiques,
                    'can_edit_statut' => $canEditStatut,
                    'key_ign' => $keyIgn,
                    'geoconcept_url' => $geoconceptUrl,
                    'default_lon' => $defaultLon,
                    'default_lat' => $defaultLat,
                ]);
            }

            /** @var array<string, mixed> $post */
            $post = $request->request->all();
            $rawGenre = $post['ID_GENRE'] ?? null;
            $idGenre = is_scalar($rawGenre) ? (int) $rawGenre : GenreEtablissement::ERP;

            try {
                /** @var Utilisateur|null $user */
                $user = $this->getUser();
                $idEtablissement = $this->etablissementService->save($idGenre, $post, $user);

                // Cas particulier : site avec un seul fils → rediriger vers édition legacy
                // TODO: remplacer par redirectToRoute('etablissement_edit', ['id' => $idEtablissement])
                //       quand l'action edit sera migrée.
                if (GenreEtablissement::SITE === $idGenre
                    && isset($post['ID_FILS_ETABLISSEMENT'])
                    && is_array($post['ID_FILS_ETABLISSEMENT'])
                    && 1 === count(array_filter($post['ID_FILS_ETABLISSEMENT'], static function ($v): bool {
                        return is_scalar($v) && (int) $v > 0;
                    }))
                ) {
                    $this->addFlash(
                        'warning',
                        "Les droits d'accès au site sont déterminés par les droits d'accès aux établissements qui le compose. Veillez à ajouter des établissements afin de garantir l'accès au site dans Prevarisc."
                    );

                    return $this->redirect('/etablissement/edit/id/'.$idEtablissement);
                }

                // F9 : message aligné sur le legacy
                $this->addFlash('success', "L'établissement a bien été ajouté.");

                return $this->redirectToRoute('etablissement_informations', ['id' => $idEtablissement]);
            } catch (\Exception $e) {
                // F9 : logger l'exception, ne pas exposer $e->getMessage() à l'utilisateur
                $this->logger->error('Erreur ajout établissement', ['error' => $e->getMessage()]);
                // F9 : 'error' → 'danger' + message générique aligné legacy
                $this->addFlash('danger', "L'établissement n'a pas été ajouté. Veuillez rééssayez.");
            }
        }

        return $this->render('etablissement/add.html.twig', [
            'genres' => $genres,
            'statuts' => $statuts,
            'avis' => $avis,
            'categories' => $categories,
            'types' => $types,
            'activites' => $activites,
            'activites_json' => json_encode($activitesJson),
            'commissions' => $commissionsData,
            'types_plans' => $typesPlans,
            'familles' => $familles,
            'igh_classes' => $ighClasses,
            'classements' => $classements,
            'couches_cartographiques' => $couchesCartographiques,
            'can_edit_statut' => $canEditStatut,
            'key_ign' => $keyIgn,
            'geoconcept_url' => $geoconceptUrl,
            'default_lon' => $defaultLon,
            'default_lat' => $defaultLat,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/informations", name="etablissement_informations", options={"expose"=true})
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function informations(
        Etablissement $etablissement
    ): Response {
        // Récupération des couches cartographiques
        $couchesCartographiques = $this->coucheCartoRepository->findAll();
        $couchesCartographiquesJson = $this->serializer->serialize($couchesCartographiques, 'json');

        // Récupération de la périodicité par défaut
        $defaultPeriodicite = $this->periodiciteRepository->gn4ForEtablissement($etablissement);

        // Récupération de la périodicité affichée
        $periodicity = $this->etablissementService->getDisplayedPeriodicity($etablissement);

        // Récupération des adresses de l'établissement
        $adresses = $this->etablissementAdresseRepository->get($etablissement->getId());

        // Récupération des groupements de communes liés à la commune de l'établissement
        $groupementsDeCommunes = [];
        if (0 !== count($adresses)) {
            $firstAddress = current($adresses);
            if ($firstAddress instanceof EtablissementAdresse && $firstAddress->getCommune() instanceof Commune) {
                $groupementsDeCommunes = $this->groupementRepository->findByNumInsee(
                    $firstAddress->getCommune()->getId()
                );
            }
        }

        // Récupération des pièces jointes de type plan et diaporama
        $diapoPlans = $this->etablissementPieceJointeRepository->getPlans($etablissement);
        $diapo = $this->etablissementPieceJointeRepository->getDiaporama($etablissement);

        // Récupération des dates de visite
        $datesVisites = $this->etablissementService->getDatesVisites($etablissement);

        // Récupération des fils triés par libellé de leur dernière information
        $filsOrdonnes = $this->etablissementRepository->findFilsOrderedByLibelle($etablissement);

        return $this->render('etablissement/informations.html.twig', [
            'etablissement' => $etablissement,
            'couches_cartographiques' => $couchesCartographiquesJson,
            'adresses' => $adresses,
            'default_periodicite' => $defaultPeriodicite,
            'periodicity' => $periodicity,
            'groupements_de_communes' => $groupementsDeCommunes,
            'store' => $this->dataStore,
            'diapo_plans' => $diapoPlans,
            'diapo' => $diapo,
            'last_visite' => $datesVisites['lastVisite'],
            'next_visite' => $datesVisites['nextVisite'],
            'pc_initial' => $this->dossierRepository->getPCInitial($etablissement),
            'presence_dus' => $this->etablissementService->hasContactDus($etablissement),
            'donnees_pratiques' => $this->etablissementService->calculateDonneesPratiques($etablissement),
            'fils_ordonnes' => $filsOrdonnes,
        ]);
    }

    /**
     * Point d'entrée Symfony après modification d'un établissement depuis le legacy.
     *
     * Reçoit le type d'alerte déterminé par le legacy (checkAlerte), génère le flash
     * Symfony avec le lien "Alerter" si les conditions sont réunies, puis redirige
     * vers la page d'informations de l'établissement.
     *
     * Équivalent legacy : fin de EtablissementController::editAction() (flash + redirect)
     *
     * @Route("/symfony/etablissement/{id}/apres-modification", name="etablissement_apres_modification", methods={"GET"}, defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("view_ets", subject="etablissement")
     *
     * @param int           $id            Identifiant de l'établissement
     * @param Request       $request       Requête HTTP (query param type_alerte)
     * @param Etablissement $etablissement Établissement modifié
     */
    public function apresModification(
        int $id,
        Request $request,
        Etablissement $etablissement
    ): Response {
        $typeAlerte = $request->query->getInt('type_alerte', 0);
        $lienAlerte = '';

        if ($typeAlerte > 0 && $this->mailerConfig->isEnabled()) {
            $privilege = AlerteService::PRIVILEGES_CHANGEMENT[$typeAlerte] ?? null;

            if (null !== $privilege && $this->isGranted($privilege)) {
                $lienAlerte = $this->alerteService->getLink($typeAlerte, $id);
            }
        }

        $this->addFlash('success', 'L\'établissement a bien été mis à jour.'.$lienAlerte);

        return $this->redirectToRoute('etablissement_informations', ['id' => $id]);
    }

    /**
     * @Route("/etablissement/{id}/historique", name="etablissement_historique")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function historique(Etablissement $etablissement): Response
    {
        $historique = $this->etablissementHistoriqueService->getHistorique($etablissement->getId());

        return $this->render('etablissement/historique.html.twig', [
            'etablissement' => $etablissement,
            'historique' => $historique,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/textes-applicables", name="etablissement_textes_applicables")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function textesApplicables(Etablissement $etablissement): Response
    {
        $etablissementData = $this->etablissementService->getTextesApplicables($etablissement->getId());

        return $this->render('etablissement/textes_applicables.html.twig', [
            'etablissement' => $etablissement,
            'textes_applicables' => $etablissementData,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/textes-applicables/modifier", name="etablissement_textes_applicables_edit")
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function editTextesApplicables(Request $request, Etablissement $etablissement): Response
    {
        $this->etablissementRepository->getTextesApplicablesByType($etablissement->getId());
        $form = $this->createForm(TextesApplicablesEtablissementType::class, $etablissement);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Les textes applicables ont bien été mis à jour.');

            return $this->redirectToRoute('etablissement_textes_applicables', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/edit_textes_applicables.html.twig', [
            'form' => $form->createView(),
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/avis-derogations", name="etablissement_avis_derogations")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function avisDerogations(Etablissement $etablissement): Response
    {
        $historiqueAvisDerogations = $this->etablissementService->getAvisDerogations($etablissement);
        $derogationsOriginales = $etablissement->getDescriptifDerogation();

        return $this->render('etablissement/avis_derogations.html.twig', [
            'etablissement' => $etablissement,
            'historiqueAvisDerogations' => $historiqueAvisDerogations,
            'derogationsOriginales' => $derogationsOriginales,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/pieces-jointes", name="etablissement_pieces_jointes")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function piecesJointes(Etablissement $etablissement): Response
    {
        $image_pjs = $this->etablissementPieceJointeService->getPJImages($etablissement->getId());
        $doc_pjs = $this->etablissementPieceJointeService->getAutresPJs($etablissement->getId());

        return $this->render('etablissement/pieces_jointes.html.twig', [
            'image_pjs' => $image_pjs,
            'doc_pjs' => $doc_pjs,
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * Affiche la liste de gestion des pièces jointes d'un établissement.
     *
     * @Route("/etablissement/{id}/pieces-jointes/modifier", name="etablissement_pieces_jointes_modifier", methods={"GET"})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function editPiecesJointes(Etablissement $etablissement): Response
    {
        $image_pjs = $this->etablissementPieceJointeService->getPJImages($etablissement->getId());
        $doc_pjs = $this->etablissementPieceJointeService->getAutresPJs($etablissement->getId());

        return $this->render('etablissement/edit_pieces_jointes.html.twig', [
            'image_pjs' => $image_pjs,
            'doc_pjs' => $doc_pjs,
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * Affiche le formulaire modal d'ajout de pièce jointe (chargé via AJAX).
     *
     * @Route("/etablissement/{id}/pieces-jointes/ajouter/formulaire", name="etablissement_pieces_jointes_ajouter_formulaire", methods={"GET"}, defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function addPieceJointeForm(Etablissement $etablissement): Response
    {
        return $this->render('etablissement/add_piece_jointe.html.twig', [
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * Traite l'ajout d'une pièce jointe à un établissement (formulaire modal ou Dropzone).
     *
     * @Route("/etablissement/{id}/pieces-jointes/ajouter", name="etablissement_pieces_jointes_ajouter", methods={"POST"}, defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function addPieceJointe(Etablissement $etablissement, Request $request): Response
    {
        $rawToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_pj_etablissement', is_string($rawToken) ? $rawToken : null)) {
            $this->addFlash('danger', 'Token de sécurité invalide. Veuillez réessayer.');

            return $this->redirectToRoute('etablissement_pieces_jointes_modifier', ['id' => $etablissement->getId()]);
        }

        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            $this->addFlash('danger', 'Aucun fichier reçu. Veuillez réessayer.');

            return $this->redirectToRoute('etablissement_pieces_jointes_modifier', ['id' => $etablissement->getId()]);
        }

        $nameParam = $request->request->get('name', '');
        $name = is_string($nameParam) ? $nameParam : '';
        $descriptionParam = $request->request->get('description', '');
        $description = is_string($descriptionParam) ? $descriptionParam : '';
        $miseEnAvantParam = $request->request->get('mise_en_avant', 0);
        $mise_en_avant = is_numeric($miseEnAvantParam) ? (int) $miseEnAvantParam : 0;

        try {
            $this->etablissementPieceJointeService->addPJ($etablissement, $file, $name, $description, $mise_en_avant);
            $this->addFlash('success', 'La pièce jointe a bien été ajoutée.');
        } catch (\Exception $exception) {
            $this->pieceJointeLogger->error('Erreur lors de l\'ajout d\'une pièce jointe', [
                'etablissement_id' => $etablissement->getId(),
                'error' => $exception->getMessage(),
            ]);
            $this->addFlash('danger', 'La pièce jointe n\'a pas été ajoutée. Veuillez réessayer.');
        }

        if ($request->isXmlHttpRequest()) {
            return new Response('', Response::HTTP_OK);
        }

        return $this->redirectToRoute('etablissement_pieces_jointes_modifier', ['id' => $etablissement->getId()]);
    }

    /**
     * Supprime une pièce jointe d'un établissement.
     *
     * @Route("/etablissement/{id}/pieces-jointes/supprimer", name="etablissement_pieces_jointes_supprimer", methods={"POST"}, defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function deletePieceJointe(Etablissement $etablissement, Request $request): Response
    {
        $idPj = $request->request->getInt('id_pj');

        $rawToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_pj_'.$idPj, is_string($rawToken) ? $rawToken : null)) {
            $this->addFlash('danger', 'Token de sécurité invalide. Veuillez réessayer.');

            return $this->redirectToRoute('etablissement_pieces_jointes_modifier', ['id' => $etablissement->getId()]);
        }

        try {
            $this->etablissementPieceJointeService->deletePJ($etablissement, $idPj);
            $this->addFlash('success', 'La pièce jointe a bien été supprimée.');
        } catch (\Exception $exception) {
            $this->addFlash('danger', 'La pièce jointe n\'a pas été supprimée. Veuillez réessayer.');
        }

        return $this->redirectToRoute('etablissement_pieces_jointes_modifier', ['id' => $etablissement->getId()]);
    }

    /**
     * @Route("/etablissement/{id}/dossiers", name="etablissement_dossiers", methods={"GET"})
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function getDossiers(
        Etablissement $etablissement
    ): Response {
        $id = $etablissement->getId();

        // Récupération des dossiers par type (limités à nbDossiersAAfficher en SQL)
        $etudes = $this->dossierRepository->getDossiersEtablissement($id, [DossierType::ETUDE], false, $this->nbDossiersAAfficher);
        $visites = $this->dossierRepository->getDossiersEtablissement($id, DossierType::TYPES_VISITE, false, $this->nbDossiersAAfficher);
        $autres = $this->dossierRepository->getDossiersEtablissement($id, DossierType::TYPES_PRINCIPAUX, true, $this->nbDossiersAAfficher);

        // Comptage total par type
        $nbEtudes = $this->dossierRepository->countDossiersEtablissement($id, [DossierType::ETUDE]);
        $nbVisites = $this->dossierRepository->countDossiersEtablissement($id, DossierType::TYPES_VISITE);
        $nbAutres = $this->dossierRepository->countDossiersEtablissement($id, DossierType::TYPES_PRINCIPAUX, true);

        return $this->render('etablissement/dossiers.html.twig', [
            'etablissement' => $etablissement,
            'etudes' => $this->dossierService->extraireDonneesUtiles($etudes),
            'visites' => $this->dossierService->extraireDonneesUtiles($visites),
            'autres' => $this->dossierService->extraireDonneesUtiles($autres),
            'nbEtudes' => $nbEtudes,
            'nbVisites' => $nbVisites,
            'nbAutres' => $nbAutres,
            'nbElemMax' => $this->nbDossiersAAfficher,
        ]);
    }

    /**
     * @Route("/etablissement/get-dossiers-after-n", name="etablissement_get_dossiers_after_n", methods={"POST"}, defaults={"_etablissement_lateral_nav": false})
     */
    public function getDossiersAfterN(Request $request): Response
    {
        $id = $request->request->getInt('id');
        if (0 === $id) {
            return new Response('');
        }

        $typeDossier = $request->request->get('typeDossier');

        // Récupération des dossiers selon le type, en démarrant après les N premiers (offset SQL)
        switch ($typeDossier) {
            case 'etudes':
                $dossiers = $this->dossierRepository->getDossiersEtablissement($id, [DossierType::ETUDE], false, null, $this->nbDossiersAAfficher);
                break;
            case 'visites':
                $dossiers = $this->dossierRepository->getDossiersEtablissement($id, DossierType::TYPES_VISITE, false, null, $this->nbDossiersAAfficher);
                break;
            case 'autres':
                $dossiers = $this->dossierRepository->getDossiersEtablissement($id, DossierType::TYPES_PRINCIPAUX, true, null, $this->nbDossiersAAfficher);
                break;
            default:
                throw new \InvalidArgumentException('Type de dossier invalide.');
        }

        if ([] === $dossiers) {
            return new Response('');
        }

        $etablissement = $this->etablissementRepository->find($id);
        $dossierDonnantAvisId = null !== $etablissement && null !== $etablissement->getDossierDonnantAvis()
            ? $etablissement->getDossierDonnantAvis()->getId()
            : null;

        // Génération du HTML
        $html = '';
        foreach ($this->dossierService->extraireDonneesUtiles($dossiers) as $dossier) {
            $html .= $this->renderView('etablissement/dossiers/_item.html.twig', [
                'dossier' => $dossier,
                'dossierDonnantAvisId' => $dossierDonnantAvisId,
            ]);
        }

        return new Response($html);
    }

    /**
     * Affiche l'onglet Descriptif de l'établissement.
     * Si PREVARISC_DESCRIPTIF_PERSONNALISE est activé, affiche les formulaires personnalisés.
     * Sinon, affiche le descriptif original (champs fixes).
     *
     * @Route("/etablissement/{id}/descriptif", name="etablissement_descriptif", methods={"GET"})
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function descriptif(Etablissement $etablissement): Response
    {
        if (!$this->descriptifPersonnalise) {
            $data = $this->etablissementService->getDescriptifOriginal($etablissement);

            return $this->render('etablissement/descriptif_original.html.twig', [
                'etablissement' => $etablissement,
                'descriptif' => $data['descriptif'],
                'historique' => $data['historique'],
                'derogation' => $data['derogation'],
                'techniquesGroupes' => $data['techniquesGroupes'],
            ]);
        }

        $data = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT);

        return $this->render('etablissement/_informations/descriptif_personnalise.html.twig', [
            'etablissement' => $etablissement,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
        ]);
    }

    /**
     * Retourne le contenu du descriptif original pour la modale (sans layout).
     *
     * @Route("/etablissement/{id}/descriptif-original", name="etablissement_descriptif_original", methods={"GET"}, defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function descriptifOriginal(Etablissement $etablissement, Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $data = $this->etablissementService->getDescriptifOriginal($etablissement);

        return $this->render('etablissement/_descriptif_original_content.html.twig', [
            'descriptif' => $data['descriptif'],
            'historique' => $data['historique'],
            'derogation' => $data['derogation'],
            'techniquesGroupes' => $data['techniquesGroupes'],
        ]);
    }

    /**
     * Affiche et traite le formulaire d'édition du descriptif.
     * Si PREVARISC_DESCRIPTIF_PERSONNALISE est activé, gère les formulaires personnalisés (POST).
     * Sinon, gère le descriptif original (champs fixes).
     *
     * @Route("/etablissement/{id}/descriptif/modifier", name="etablissement_descriptif_modifier", methods={"GET", "POST"})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function editDescriptifOriginal(Etablissement $etablissement, Request $request): Response
    {
        if ($this->descriptifPersonnalise) {
            return $this->editDescriptifPersonnalise($etablissement, $request, $this->formFactory);
        }

        $form = $this->createForm(DescriptifOriginalType::class, $etablissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->etablissementService->saveDescriptifOriginal();
                $this->addFlash('success', 'Les descriptifs ont bien été mis à jour.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la sauvegarde du descriptif original', [
                    'etablissement_id' => $etablissement->getId(),
                    'exception' => $e->getMessage(),
                ]);
                $this->addFlash('error', 'Les descriptifs n\'ont pas été mis à jour. Veuillez réessayer.');
            }

            return $this->redirectToRoute('etablissement_descriptif', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/edit_descriptif_original.html.twig', [
            'etablissement' => $etablissement,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gère l'édition du descriptif personnalisé (formulaire personnalisé).
     * Appelée par editDescriptifOriginal lorsque PREVARISC_DESCRIPTIF_PERSONNALISE est activé.
     */
    private function editDescriptifPersonnalise(Etablissement $etablissement, Request $request, FormFactoryInterface $formFactory): Response
    {
        $data = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT);

        $formData = $this->descriptifFormHydrator->hydrateFromEtablissement($etablissement, $data['rubriques']);
        $form = $this->descriptifFormBuilder->buildForm($formFactory, $formData, $data['rubriques']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var DescriptifFormData $submittedData */
                $submittedData = $form->getData();
                $this->descriptifFormPersister->persistForEtablissement($etablissement, $submittedData, $data['rubriques']);
                $this->addFlash('success', 'Les descriptifs ont bien été mis à jour.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la sauvegarde du descriptif personnalisé', [
                    'etablissement_id' => $etablissement->getId(),
                    'exception' => $e->getMessage(),
                ]);
                $this->addFlash('error', 'Les descriptifs n\'ont pas été mis à jour. Veuillez réessayer.');
            }

            return $this->redirectToRoute('etablissement_descriptif', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/_informations/edit_descriptif_personnalise.html.twig', [
            'etablissement' => $etablissement,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche et traite le formulaire d'édition des effectifs et dégagements de l'établissement.
     *
     * @Route("/etablissement/{id}/effectifs-degagements/modifier", name="etablissement_effectifs_degagements_edit", methods={"GET", "POST"})
     *
     * @Security("is_granted('effectifs_degagements_ets') and is_granted('edit_ets', etablissement)")
     */
    public function editEffectifsDegagements(Etablissement $etablissement, Request $request): Response
    {
        $data = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT);

        $formData = $this->descriptifFormHydrator->hydrateFromEtablissement($etablissement, $data['rubriques']);
        $form = $this->descriptifFormBuilder->buildForm($this->formFactory, $formData, $data['rubriques']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var DescriptifFormData $submittedData */
                $submittedData = $form->getData();
                $this->descriptifFormPersister->persistForEtablissement($etablissement, $submittedData, $data['rubriques']);
                $this->addFlash('success', 'Les effectifs et dégagements ont bien été mis à jour.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la sauvegarde des effectifs et dégagements', [
                    'etablissement_id' => $etablissement->getId(),
                    'exception' => $e->getMessage(),
                ]);
                $this->addFlash('error', 'Les effectifs et dégagements n\'ont pas été mis à jour. Veuillez réessayer.');
            }

            return $this->redirectToRoute('etablissement_effectifs_degagements', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/_informations/edit_effectifs_degagements.html.twig', [
            'etablissement' => $etablissement,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche l'onglet Effectifs et Dégagements de l'établissement.
     *
     * @Route("/etablissement/{id}/effectifs-degagements", name="etablissement_effectifs_degagements", methods={"GET"})
     *
     * @Security("is_granted('effectifs_degagements_ets') and is_granted('view_ets', etablissement)")
     */
    public function effectifsDegagements(Etablissement $etablissement): Response
    {
        $data = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT);

        return $this->render('etablissement/_informations/effectifs_degagements.html.twig', [
            'etablissement' => $etablissement,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
        ]);
    }
}

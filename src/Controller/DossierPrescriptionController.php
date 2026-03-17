<?php

namespace App\Controller;

use App\Constant\TypePrescription;
use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Dossier;
use App\Entity\PrescriptionDossier;
use App\Entity\PrescriptionType;
use App\Form\PrescriptionDossierType;
use App\Repository\DossierRepository;
use App\Repository\PrescriptionArticleRepository;
use App\Repository\PrescriptionCategorieRepository;
use App\Repository\PrescriptionTexteRepository;
use App\Repository\PrescriptionTypeRepository;
use App\Service\DossierPrescriptionService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierPrescriptionController extends AbstractController implements DossierLateralNavigationInterface
{
    /** @var DossierPrescriptionService */
    private $service;

    /** @var PrescriptionCategorieRepository */
    private $categorieRepo;

    /** @var PrescriptionTypeRepository */
    private $prescriptionTypeRepo;

    /** @var PrescriptionTexteRepository */
    private $texteRepo;

    /** @var PrescriptionArticleRepository */
    private $articleRepo;

    /** @var DossierRepository */
    private $dossierRepo;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        DossierPrescriptionService $service,
        PrescriptionCategorieRepository $categorieRepo,
        PrescriptionTypeRepository $prescriptionTypeRepo,
        PrescriptionTexteRepository $texteRepo,
        PrescriptionArticleRepository $articleRepo,
        DossierRepository $dossierRepo,
        LoggerInterface $logger
    ) {
        $this->service = $service;
        $this->categorieRepo = $categorieRepo;
        $this->prescriptionTypeRepo = $prescriptionTypeRepo;
        $this->texteRepo = $texteRepo;
        $this->articleRepo = $articleRepo;
        $this->dossierRepo = $dossierRepo;
        $this->logger = $logger;
    }

    /**
     * @Route("/dossier/{id}/prescriptions", name="dossier_prescriptions", requirements={"id"="\d+"}, methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function index(Dossier $dossier): Response
    {
        return $this->render('dossier/prescriptions/index.html.twig', [
            'dossier' => $dossier,
            'prescriptionsRegl' => $this->service->getPrescriptionsParType($dossier, TypePrescription::REGLEMENTAIRE),
            'prescriptionsExploitation' => $this->service->getPrescriptionsParType($dossier, TypePrescription::EXPLOITATION),
            'prescriptionsAmelioration' => $this->service->getPrescriptionsParType($dossier, TypePrescription::AMELIORATION),
        ]);
    }

    /**
     * @Route("/dossier/{id}/prescriptions/reordonner", name="dossier_prescriptions_reordonner", requirements={"id"="\d+"}, methods={"POST"}, options={"expose"=true}, defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function reordonner(Request $request, Dossier $dossier): Response
    {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Le dossier est verrouillé.');
        }

        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('reorder_prescriptions', $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        $rawIdsRegl = $request->request->get('idsRegl');
        $rawIdsAutres = $request->request->get('idsAutres');

        $idsRegl = array_filter(array_map('intval', explode(',', is_string($rawIdsRegl) ? $rawIdsRegl : '')));
        $idsAutres = array_filter(array_map('intval', explode(',', is_string($rawIdsAutres) ? $rawIdsAutres : '')));

        $this->service->reordonner($dossier, array_values($idsRegl), array_values($idsAutres));

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/dossier/{id}/prescriptions/ajouter", name="dossier_prescriptions_ajouter", requirements={"id"="\d+"})
     * @Route("/dossier/{id}/prescriptions/{idPrescription}/modifier", name="dossier_prescriptions_modifier", requirements={"id"="\d+", "idPrescription"="\d+"})
     *
     * @ParamConverter("prescription", options={"id" = "idPrescription"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function form(Request $request, Dossier $dossier, ?PrescriptionDossier $prescription = null): Response
    {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Le dossier est verrouillé.');
        }

        $isNew = null === $prescription;
        $ancienType = null;

        if ($isNew) {
            $prescription = new PrescriptionDossier();
            $prescription->setType(TypePrescription::REGLEMENTAIRE);
        } else {
            $ancienType = $prescription->getType();
            $this->service->preparerPourEdition($prescription);
        }

        $form = $this->createForm(PrescriptionDossierType::class, $prescription, [
            'action' => $this->generateUrl(
                $isNew ? 'dossier_prescriptions_ajouter' : 'dossier_prescriptions_modifier',
                $isNew
                    ? ['id' => $dossier->getId()]
                    : ['id' => $dossier->getId(), 'idPrescription' => $prescription->getId()]
            ),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->service->sauvegarder($dossier, $prescription, $ancienType);

            $this->addFlash(
                'success',
                $isNew ? 'La prescription a bien été ajoutée.' : 'La prescription a bien été modifiée.'
            );

            return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/prescriptions/form.html.twig', [
            'dossier' => $dossier,
            'prescription' => $prescription,
            'form' => $form->createView(),
            'is_new' => $isNew,
        ]);
    }

    /**
     * @Route("/dossier/{id}/prescriptions/{idPrescription}/supprimer", name="dossier_prescriptions_supprimer", requirements={"id"="\d+", "idPrescription"="\d+"}, methods={"DELETE"})
     *
     * @ParamConverter("prescription", options={"id" = "idPrescription"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function supprimer(Request $request, Dossier $dossier, PrescriptionDossier $prescription): Response
    {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Le dossier est verrouillé.');
        }

        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_prescription_'.$prescription->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        $this->service->supprimer($prescription);

        $this->addFlash('success', 'La prescription a bien été supprimée.');

        return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
    }

    /**
     * Retourne le contenu de la modale "Ajouter une prescription type".
     *
     * @Route("/dossier/{id}/prescriptions/modal-type", name="dossier_prescriptions_modal_type", requirements={"id"="\d+"}, methods={"GET"}, options={"expose"=true}, defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function ajouterTypeModal(Dossier $dossier): Response
    {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Le dossier est verrouillé.');
        }

        return $this->render('dossier/prescriptions/_modal_ajout_type.html.twig', [
            'dossier' => $dossier,
            'categories' => $this->categorieRepo->findBy([], ['numero' => 'ASC']),
            'prescriptionsTypesInitiales' => $this->prescriptionTypeRepo->findByClassement(0, 0, 0),
        ]);
    }

    /**
     * Recherche des prescriptions types par mots-clés (AJAX).
     * Retourne un fragment HTML avec la liste des résultats.
     *
     * @Route("/dossier/{id}/prescriptions/recherche-mots-cles", name="dossier_prescriptions_recherche_mots_cles", requirements={"id"="\d+"}, methods={"POST"}, options={"expose"=true}, defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function rechercheMotsCles(Request $request, Dossier $dossier): Response
    {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Le dossier est verrouillé.');
        }

        $rawMotsCles = $request->request->get('motsCles', '');
        $motsCles = [] !== ($parts = array_filter(explode(' ', is_string($rawMotsCles) ? trim($rawMotsCles) : '')))
            ? array_values($parts)
            : [];

        $prescriptionsTypes = [] !== $motsCles
            ? $this->prescriptionTypeRepo->findByMotsCles($motsCles)
            : [];

        return $this->render('dossier/prescriptions/_prescription_type_list.html.twig', [
            'dossier' => $dossier,
            'prescriptionsTypes' => $prescriptionsTypes,
            'motsCles' => $motsCles,
        ]);
    }

    /**
     * Navigation hiérarchique par classement (catégorie → texte → article) (AJAX).
     * Retourne un fragment HTML adapté au niveau de navigation courant.
     *
     * @Route("/dossier/{id}/prescriptions/recherche-classement", name="dossier_prescriptions_recherche_classement", requirements={"id"="\d+"}, methods={"POST"}, options={"expose"=true}, defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function rechercheClassement(
        Request $request,
        Dossier $dossier
    ): Response {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Le dossier est verrouillé.');
        }

        $categorieId = $request->request->getInt('categorieId', 0);
        $texteId = $request->request->getInt('texteId', 0);
        $articleId = $request->request->getInt('articleId', 0);

        if (0 === $categorieId) {
            return $this->render('dossier/prescriptions/_emplacement_navigation.html.twig', [
                'dossier' => $dossier,
                'niveau' => 'categories',
                'categories' => $this->categorieRepo->findBy([], ['numero' => 'ASC']),
                'prescriptionsTypes' => $this->prescriptionTypeRepo->findByClassement(0, 0, 0),
            ]);
        }

        $categorie = $this->categorieRepo->find($categorieId);

        if (0 === $texteId) {
            return $this->render('dossier/prescriptions/_emplacement_navigation.html.twig', [
                'dossier' => $dossier,
                'niveau' => 'textes',
                'categorie' => $categorie,
                'textes' => null !== $categorie ? $this->texteRepo->findByCategorie($categorie) : [],
                'prescriptionsTypes' => $this->prescriptionTypeRepo->findByClassement($categorieId, 0, 0),
            ]);
        }

        $texte = $this->texteRepo->find($texteId);

        if (0 === $articleId) {
            return $this->render('dossier/prescriptions/_emplacement_navigation.html.twig', [
                'dossier' => $dossier,
                'niveau' => 'articles',
                'categorie' => $categorie,
                'texte' => $texte,
                'articles' => null !== $texte ? $this->articleRepo->findByTexte($texte) : [],
                'prescriptionsTypes' => $this->prescriptionTypeRepo->findByClassement($categorieId, $texteId, 0),
            ]);
        }

        $article = $this->articleRepo->find($articleId);

        return $this->render('dossier/prescriptions/_emplacement_navigation.html.twig', [
            'dossier' => $dossier,
            'niveau' => 'prescriptions',
            'categorie' => $categorie,
            'texte' => $texte,
            'article' => $article,
            'prescriptionsTypes' => $this->prescriptionTypeRepo->findByClassement($categorieId, $texteId, $articleId),
        ]);
    }

    /**
     * Ajoute une prescription type au dossier (AJAX).
     * Retourne le fragment HTML de la prescription ajoutée.
     *
     * @Route("/dossier/{id}/prescriptions/{idPrescType}/ajouter-type", name="dossier_prescriptions_ajouter_type", requirements={"id"="\d+", "idPrescType"="\d+"}, methods={"POST"}, options={"expose"=true}, defaults={"_dossier_lateral_nav": false})
     *
     * @ParamConverter("prescriptionType", options={"id" = "idPrescType"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function ajouterDepuisType(Request $request, Dossier $dossier, PrescriptionType $prescriptionType): Response
    {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Le dossier est verrouillé.');
        }

        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('add_prescription_type_'.$dossier->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        $typePrescription = $request->request->getInt('typePrescriptionDossier', TypePrescription::REGLEMENTAIRE);

        try {
            $prescription = $this->service->ajouterDepuisType($dossier, $prescriptionType, $typePrescription);
        } catch (\Exception $exception) {
            $this->logger->error("Erreur lors de l'ajout de la prescription type au dossier.", [
                'message' => $exception->getMessage(),
                'dossier' => $dossier->getId(),
            ]);

            return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->render('dossier/prescriptions/_prescription_item.html.twig', [
            'prescription' => $prescription,
            'dossier' => $dossier,
        ]);
    }

    /**
     * Retourne le contenu de la modale "Reprendre les prescriptions d'autres dossiers".
     *
     * Disponible uniquement si le dossier est lié à un seul établissement.
     *
     * @Route("/dossier/{id}/prescriptions/modal-reprise", name="dossier_prescriptions_modal_reprise", requirements={"id"="\d+"}, methods={"GET"}, options={"expose"=true}, defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function repriseModal(Dossier $dossier): Response
    {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Le dossier est verrouillé.');
        }

        $etablissements = $dossier->getEtablissements();

        if (1 !== $etablissements->count()) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $etablissement = $etablissements->first();
        if (false === $etablissement) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $groupes = $this->dossierRepo->findByEtablissementGrouped($etablissement, $dossier);

        return $this->render('dossier/prescriptions/_modal_reprise.html.twig', [
            'dossier' => $dossier,
            'etudes' => $groupes['etudes'],
            'visites' => $groupes['visites'],
            'autres' => $groupes['autres'],
        ]);
    }

    /**
     * Reprend les prescriptions des dossiers sélectionnés vers le dossier courant.
     *
     * @Route("/dossier/{id}/prescriptions/reprendre", name="dossier_prescriptions_reprendre", requirements={"id"="\d+"}, methods={"POST"}, defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function reprendre(Request $request, Dossier $dossier): Response
    {
        if ($dossier->hasVerrou()) {
            throw $this->createAccessDeniedException('Le dossier est verrouillé.');
        }

        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('reprendre_prescriptions_'.$dossier->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        $dossierIds = $request->request->get('dossierIds', []);
        $dossierIds = is_array($dossierIds) ? array_map(function ($v): int { return (int) (is_scalar($v) ? $v : 0); }, $dossierIds) : [];
        $dossierIds = array_filter($dossierIds);

        if ([] === $dossierIds) {
            $this->addFlash('warning', 'Aucun dossier sélectionné.');

            return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
        }

        $sources = array_filter(
            array_map(function (int $id) { return $this->dossierRepo->find($id); }, $dossierIds)
        );

        try {
            $this->service->reprendrePrescriptions($dossier, array_values($sources));
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la reprise des prescriptions.', [
                'message' => $exception->getMessage(),
                'dossier' => $dossier->getId(),
            ]);

            $this->addFlash('danger', 'Une erreur est survenue lors de la reprise des prescriptions. Veuillez réessayer.');

            return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
        }

        $this->addFlash('success', 'Les prescriptions ont bien été reprises.');

        return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
    }
}

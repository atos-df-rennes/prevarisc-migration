<?php

namespace App\Controller;

use App\Entity\PrescriptionArticle;
use App\Entity\PrescriptionCategorie;
use App\Entity\PrescriptionTexte;
use App\Entity\PrescriptionType;
use App\Form\PrescriptionTypeArticleType;
use App\Form\PrescriptionTypeCategorieType;
use App\Form\PrescriptionTypePrescriptionType;
use App\Form\PrescriptionTypeTexteType;
use App\Repository\PrescriptionArticleRepository;
use App\Repository\PrescriptionCategorieRepository;
use App\Repository\PrescriptionTexteRepository;
use App\Repository\PrescriptionTypeRepository;
use App\Service\Prescriptions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class PrescriptionsTypesController extends AbstractController
{
    /** @var PrescriptionCategorieRepository */
    private $prescriptionCategorieRepository;

    /** @var PrescriptionTexteRepository */
    private $prescriptionTexteRepository;

    /** @var PrescriptionArticleRepository */
    private $prescriptionArticleRepository;

    /** @var PrescriptionTypeRepository */
    private $prescriptionTypeRepository;

    /** @var Prescriptions */
    private $prescriptionsService;

    public function __construct(
        PrescriptionCategorieRepository $prescriptionCategorieRepository,
        PrescriptionTexteRepository $prescriptionTexteRepository,
        PrescriptionArticleRepository $prescriptionArticleRepository,
        PrescriptionTypeRepository $prescriptionTypeRepository,
        Prescriptions $prescriptionsService
    ) {
        $this->prescriptionCategorieRepository = $prescriptionCategorieRepository;
        $this->prescriptionTexteRepository = $prescriptionTexteRepository;
        $this->prescriptionArticleRepository = $prescriptionArticleRepository;
        $this->prescriptionTypeRepository = $prescriptionTypeRepository;
        $this->prescriptionsService = $prescriptionsService;
    }

    /**
     * @Route("/admin/prescriptions", name="admin_prescriptions")
     */
    public function index(): Response
    {
        return $this->render('prescriptions_types/index.html.twig', [
            'categories' => $this->prescriptionCategorieRepository->findBy([], ['numero' => 'ASC']),
            'sans_categories' => $this->prescriptionsService->showPrescriptionType(0, 0, 0),
        ]);
    }

    /**
     * @Route("/admin/prescriptions/changement-ordre", name="admin_prescriptions_types_changement_ordre", methods={"POST"})
     */
    public function reOrder(Request $request): Response
    {
        $orders = $request->get('order');
        $type = $request->get('type');

        if (!is_array($orders)) {
            throw new BadRequestHttpException("Le paramètre order n'est pas valide");
        }

        /** @var array<int, string> $orders */
        $order = array_map(function (string $identifiant) {
            return (int) $identifiant;
        }, $orders);

        if ('categorie' === $type) {
            $this->prescriptionCategorieRepository->setOrder($order);
        } elseif ('texte' === $type) {
            $this->prescriptionTexteRepository->setOrder($order);
        } elseif ('article' === $type) {
            $this->prescriptionArticleRepository->setOrder($order);
        } elseif ('prescription' === $type) {
            $this->prescriptionTypeRepository->setOrder($order);
        }

        return $this->json([]);
    }

    /**
     * @Route("/admin/prescriptions/categorie/ajouter", name="admin_prescriptions_types_categorie_ajouter")
     */
    public function categoryAdd(Request $request): Response
    {
        $form = $this->createForm(PrescriptionTypeCategorieType::class, null, [
            'action' => $this->generateUrl('admin_prescriptions_types_categorie_ajouter'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $maxNumCat = $this->prescriptionCategorieRepository->recupMaxNumCat();

            /** @var PrescriptionCategorie $categorie */
            $categorie = $form->getData();
            $categorie->setNumero($maxNumCat + 1);

            $em->persist($categorie);
            $em->flush();

            return $this->redirectToRoute('admin_prescriptions');
        }

        return $this->render('prescriptions_types/ajout_categorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/prescriptions/categorie/{categorie}/textes", name="admin_prescriptions_types_textes_voir")
     */
    public function textsView(PrescriptionCategorie $categorie): Response
    {
        $textes = $this->prescriptionTexteRepository->findBy(['categorie' => $categorie], ['numero' => 'ASC']);

        return $this->render('prescriptions_types/voir_categorie.html.twig', [
            'categorie' => $categorie,
            'sans_textes' => $this->prescriptionsService->showPrescriptionType($categorie->getId() ?? 0, 0, 0),
            'prescription_textes' => $textes
        ]);
    }

    /**
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/ajouter", name="admin_prescriptions_types_textes_ajouter")
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/modifier", name="admin_prescriptions_types_textes_modifier")
     *
     * @ParamConverter("categorie", options={"id" = "categorie"})
     * @ParamConverter("texte", options={"id" = "texte"})
     */
    public function textsForm(Request $request, PrescriptionCategorie $categorie, ?PrescriptionTexte $texte = null): Response
    {
        $actionUrl = $this->getCurrentUrl($request);

        if (null === $texte) {
            $maxNumCat = $this->prescriptionTexteRepository->recupMaxNumCat();

            $texte = new PrescriptionTexte();
            $texte->setCategorie($categorie);
            $texte->setNumero($maxNumCat + 1);
        }

        $form = $this->createForm(PrescriptionTypeTexteType::class, $texte, [
            'action' => $actionUrl,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($texte);
            $em->flush();

            return $this->redirectToRoute('admin_prescriptions');
        }

        return $this->render('prescriptions_types/form_texte.html.twig', [
            'categorie' => $categorie,
            'texte' => $texte,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}", name="admin_prescriptions_types_texte_voir_articles")
     */
    public function categoryTextsArticles(PrescriptionCategorie $categorie, PrescriptionTexte $texte, PrescriptionArticleRepository $prescriptionArticleRepository, Prescriptions $prescriptions): Response
    {
        $articles = $prescriptionArticleRepository->findBy(['texte' => $texte], ['numero' => 'ASC']);

        return $this->render('prescriptions_types/voir_texte.html.twig', [
            'articles' => $articles,
            'sans_articles' => $this->prescriptionsService->showPrescriptionType($categorie->getId() ?? 0, $texte->getId() ?? 0, 0),
            'categorie' => $categorie,
            'texte' => $texte,
        ]);
    }

    /**
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/articles/ajouter", name="admin_prescriptions_types_article_ajouter")
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/articles/{article}/modifier", name="admin_prescriptions_types_articles_modifier")
     *
     * @ParamConverter("categorie", options={"id" = "categorie"})
     * @ParamConverter("texte", options={"id" = "texte"})
     * @ParamConverter("article", options={"id" = "article"})
     */
    public function articlesForm(Request $request, PrescriptionCategorie $categorie, PrescriptionTexte $texte, ?PrescriptionArticle $article = null): Response
    {
        $actionUrl = $this->getCurrentUrl($request);

        if (null === $article) {
            $maxNumCat = $this->prescriptionArticleRepository->recupMaxNumCat();

            $article = new PrescriptionArticle();
            $article->setTexte($texte);
            $article->setNumero($maxNumCat + 1);
        }

        $form = $this->createForm(PrescriptionTypeArticleType::class, $article, [
            'action' => $actionUrl,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute('admin_prescriptions');
        }

        return $this->render('prescriptions_types/form_article.html.twig', [
            'texte' => $texte,
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/article/{article}", name="admin_prescriptions_types_texte_voir_article_contenu")
     */
    public function articlesAssociation(PrescriptionCategorie $categorie, PrescriptionTexte $texte, PrescriptionArticle $article): Response
    {
        $associations = $this->prescriptionsService->showPrescriptionType($categorie->getId() ?? 0, $texte->getId() ?? 0, $article->getId() ?? 0);

        return $this->render('prescriptions_types/voir_article.html.twig', [
            'prescription_associations' => $associations,
            'categorie' => $categorie,
            'texte' => $texte,
            'article' => $article,
        ]);
    }

    /**
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/prescriptions-types/ajouter", name="admin_prescriptions_types_prescriptions_textes_ajouter")
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/prescriptions-types/{prescription}/modifier", name="admin_prescriptions_types_prescriptions_texte_modifier")
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/articles/{article}/prescriptions-types/ajouter", name="admin_prescriptions_types_prescriptions_ajouter")
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/articles/{article}/prescriptions-types/{prescription}/modifier", name="admin_prescriptions_types_prescriptions_modifier")
     *
     * @ParamConverter("categorie", options={"id" = "categorie"})
     * @ParamConverter("texte", options={"id" = "texte"})
     * @ParamConverter("article", options={"id" = "article"})
     * @ParamConverter("prescription", options={"id" = "prescription"})
     */
    public function articlesAssociationForm(
        Request $request,
        PrescriptionCategorie $categorie,
        PrescriptionTexte $texte,
        ?PrescriptionArticle $article = null,
        ?PrescriptionType $prescription = null
    ): Response {
        $actionUrl = $this->getCurrentUrl($request);

        if (null === $prescription) {
            $maxNumCat = $this->prescriptionTypeRepository->recupMaxNumCat();

            $articleId = 0;
            if (null !== $article) {
                $articleId = $article->getId();
            }

            $prescription = new PrescriptionType();
            $prescription
                ->setCategorie($categorie->getId())
                ->setTexte($texte->getId())
                ->setArticle($articleId)
                ->setNumero($maxNumCat + 1)
            ;
        }

        $form = $this->createForm(PrescriptionTypePrescriptionType::class, $prescription, [
            'action' => $actionUrl,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($prescription);
            $em->flush();

            return $this->redirectToRoute('admin_prescriptions');
        }

        return $this->render('prescriptions_types/form_prescription.html.twig', [
            'texte' => $texte,
            'article' => $article,
            'prescription' => $prescription,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/prescriptions-types/{prescription}/supprimer", name="admin_prescriptions_types_prescriptions_texte_supprimer")
     * @Route("/admin/prescriptions/categorie/{categorie}/textes/{texte}/articles/{article}/prescriptions-types/{prescription}/supprimer", name="admin_prescriptions_types_prescriptions_supprimer")
     *
     * @ParamConverter("categorie", options={"id" = "categorie"})
     * @ParamConverter("texte", options={"id" = "texte"})
     * @ParamConverter("article", options={"id" = "article"})
     * @ParamConverter("prescription", options={"id" = "prescription"})
     */
    public function prescriptionTypeRemove(
        Request $request,
        PrescriptionCategorie $categorie,
        PrescriptionTexte $texte,
        PrescriptionType $prescription,
        ?PrescriptionArticle $article = null
    ): Response {
        $form = $this->createFormBuilder(null, [
            'action' => $this->getCurrentUrl($request),
        ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($prescription);

            $em->flush();

            return $this->redirectToRoute('admin_prescriptions');
        }

        return $this->render('prescriptions_types/remove_prescription.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Génère l'URL actuelle en fonction de la requête donnée.
     *
     * @param Request $request la requête contenant les informations de route
     *
     * @return string L'URL actuelle
     *
     * @throws BadRequestHttpException si les paramètres _route ou _route_params ne sont pas valides
     */
    private function getCurrentUrl(Request $request): string
    {
        $routeName = $request->attributes->get('_route');
        $routeParams = $request->attributes->get('_route_params');
        if (!is_string($routeName) || !is_array($routeParams)) {
            throw new BadRequestHttpException('Les paramètres _route et _route_params ne sont pas valide');
        }

        return $this->generateUrl($routeName, $routeParams);
    }
}

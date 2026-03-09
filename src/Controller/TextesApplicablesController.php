<?php

namespace App\Controller;

use App\Entity\TexteApplicable;
use App\Form\TexteApplicableType;
use App\Repository\TexteApplicableRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_textes_applicables")
 */
class TextesApplicablesController extends AbstractController
{
    /**
     * @var TexteApplicableRepository
     */
    private $texteApplicableRepository;

    /**
     * @var \App\Service\TexteApplicable
     */
    private $serviceTexteApplicable;

    public function __construct(TexteApplicableRepository $texteApplicableRepository, \App\Service\TexteApplicable $serviceTexteApplicable)
    {
        $this->texteApplicableRepository = $texteApplicableRepository;
        $this->serviceTexteApplicable = $serviceTexteApplicable;
    }

    /**
     * @Route("/admin/textes-applicables", name="admin_textes_applicables")
     */
    public function index(): Response
    {
        $textesApplicables = $this->texteApplicableRepository->findAllSorted();

        return $this->render('textes_applicables/index.html.twig', [
            'textesApplicables' => $textesApplicables,
        ]);
    }

    /**
     * @Route("/admin/textes-applicables/add", name="admin_textes_applicables_add")
     */
    public function ajouter(Request $request): Response
    {
        $texteApplicable = new TexteApplicable();

        $form = $this->createForm(TexteApplicableType::class, $texteApplicable, [
            'action' => $this->generateUrl('admin_textes_applicables_add'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // @todo: gestion de l'affichage prescription
            // la valeur oui/non n'est pas conservée, peut-être le gérer mais quid des conséquences d'affichage/masquaque
            $prescription = filter_var($form->get('prescription')->getData(), FILTER_VALIDATE_BOOLEAN);
            $this->serviceTexteApplicable->save($texteApplicable, $prescription);

            $this->addFlash('success', 'Texte applicable ajouté.');

            return $this->redirectToRoute('admin_textes_applicables');
        }

        return $this->render('textes_applicables/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/textes-applicables/{id}/edit", name="admin_textes_applicables_edit")
     */
    public function modifier(Request $request, TexteApplicable $texteApplicable): Response
    {
        $form = $this->createForm(TexteApplicableType::class, $texteApplicable, [
            'action' => $this->generateUrl('admin_textes_applicables_edit', ['id' => $texteApplicable->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $prescription = filter_var($form->get('prescription')->getData(), FILTER_VALIDATE_BOOLEAN);
            $this->serviceTexteApplicable->save($texteApplicable, $prescription);

            $this->addFlash('success', 'Texte applicable modifié.');

            return $this->redirectToRoute('admin_textes_applicables');
        }

        return $this->render('textes_applicables/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/textes-applicables/updateorder", name="admin_textes_applicables_updateorder")
     */
    public function ordonner(Request $request): Response
    {
        $tableUpdate = $request->request->get('tableUpdate');

        if (!\is_string($tableUpdate)) {
            throw new \Exception('Le format de la table de mise à jour est incorrect.');
        }

        $taIds = explode(',', $tableUpdate);
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($taIds as $key => $id) {
            $texteApplicable = $this->texteApplicableRepository->find($id);

            if (null === $texteApplicable) {
                continue;
            }

            $texteApplicable->setNum($key);
            $entityManager->persist($texteApplicable);
        }

        $entityManager->flush();

        return $this->redirectToRoute('admin_textes_applicables');
    }
}

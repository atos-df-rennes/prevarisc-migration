<?php

namespace App\Controller;

use App\Entity\PrescriptionCategorie;
use App\Form\PrescriptionTypeCategorieType;
use App\Repository\PrescriptionCategorieRepository;
use App\Service\Prescriptions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class PrescriptionsTypesController extends AbstractController
{
    /** @var PrescriptionCategorieRepository */
    private $prescriptionCategorieRepository;

    public function __construct(
        PrescriptionCategorieRepository $prescriptionCategorieRepository
    ) {
        $this->prescriptionCategorieRepository = $prescriptionCategorieRepository;
    }

    /**
     * @Route("/admin/prescriptions", name="admin_prescriptions")
     */
    public function index(Prescriptions $prescriptions): Response
    {
        return $this->render('prescriptions_types/index.html.twig', [
            'categories' => $this->prescriptionCategorieRepository->findBy([], ['numero' => 'ASC']),
            'sans_categories' => $prescriptions->showPrescriptionType(0, 0, 0),
        ]);
    }

    /**
     * @Route("/admin/prescriptions/changement-ordre", name="admin_prescriptions_types_changement_ordre", methods={"POST"})
     */
    public function reOrder(Request $request): Response
    {
        $order = $request->get('order');
        $type = $request->get('type');

        if ('categorie' === $type) {
            $this->prescriptionCategorieRepository->setOrder($order);
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
}

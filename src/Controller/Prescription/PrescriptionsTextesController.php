<?php

namespace App\Controller\Prescription;

use App\Entity\PrescriptionTexteListe;
use App\Form\PrescriptionTexteListeType;
use App\Form\ReplacePrescriptionTexteListeType;
use App\Repository\PrescriptionTexteListeRepository;
use App\Service\Prescriptions;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_prescriptions")
 */
class PrescriptionsTextesController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Prescriptions */
    private $servicePrescTextes;

    /** @var PrescriptionTexteListeRepository */
    private $prescriptionTexteListeRepository;

    public function __construct(EntityManagerInterface $entityManager, Prescriptions $servicePrescTextes, PrescriptionTexteListeRepository $prescriptionTexteListeRepository)
    {
        $this->entityManager = $entityManager;
        $this->servicePrescTextes = $servicePrescTextes;
        $this->prescriptionTexteListeRepository = $prescriptionTexteListeRepository;
    }

    /**
     * @var Prescriptions
     */
    private $servicePrescTextes;

    /**
     * @var PrescriptionTexteListeRepository
     */
    private $prescriptionTexteListeRepository;

    public function __construct(Prescriptions $servicePrescTextes, PrescriptionTexteListeRepository $prescriptionTexteListeRepository)
    {
        $this->servicePrescTextes = $servicePrescTextes;
        $this->prescriptionTexteListeRepository = $prescriptionTexteListeRepository;
    }

    /**
     * @Route("/admin/prescriptions/textes/", name="admin_prescriptions_textes")
     */
    public function gestionTextes(): Response
    {
        $listeTextes = $this->servicePrescTextes->getTextesListe();

        return $this->render('prescriptions_textes/textes.html.twig', [
            'liste_textes' => $listeTextes,
        ]);
    }

    /**
     * @Route("/admin/prescriptions/textes/ajouter", name="admin_prescriptions_textes_add")
     * @Route("/admin/prescriptions/textes/modifier/{texteListe}", name="admin_prescriptions_textes_edit")
     */
    public function formTexte(Request $request, ?PrescriptionTexteListe $texteListe = null): Response
    {
        if (null === $texteListe) {
            $texteListe = new PrescriptionTexteListe();
        }

        $form = $this->createForm(PrescriptionTexteListeType::class, $texteListe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($texteListe);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le texte a bien été enregistré');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur s\'est produite lors de l\enregistrement de la prescription ('.$e->getMessage().')');
            }

            return $this->redirectToRoute('admin_prescriptions_textes');
        }

        return $this->render('prescriptions_textes/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/prescriptions/textes/suppression/{texteListe}", name="admin_prescriptions_textes_suppression")
     */
    public function suppressionTexte(
        Request $request,
        PrescriptionTexteListe $texteListe
    ): Response {
        $form = $this->createForm(ReplacePrescriptionTexteListeType::class, null, ['excluded' => $texteListe]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newTexte = $form->get('replacementText')->getData();

            if (!$newTexte instanceof PrescriptionTexteListe) {
                throw new \Exception("Le texte de remplacement n'est pas correcte.");
            }

            try {
                $this->prescriptionTexteListeRepository->replaceAndRemove($texteListe, $newTexte);
                $this->addFlash('success', 'Le texte a bien été supprimé');
            } catch (\Exception $e) {
                $this->addFlash('danger', "Une erreur s'est produite lors de la suppression du texte (".$e->getMessage().')');
            }

            return $this->redirectToRoute('admin_prescriptions_textes');
        }

        return $this->render('prescriptions_textes/replacement_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

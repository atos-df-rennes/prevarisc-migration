<?php

namespace App\Controller;

use App\Form\MessagesAlerteType;
use App\Repository\ChangementRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class ChangementController extends AbstractController
{
    /**
     * @Route("/admin/changement", name="admin_changement")
     */
    public function index(Request $request, ChangementRepository $changementRepository): Response
    {
        $messages = $changementRepository->findAll();

        $form = $this->createForm(MessagesAlerteType::class, ['messages' => $messages]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            foreach ($messages as $message) {
                $entityManager->persist($message);
            }

            $entityManager->flush();

            $this->addFlash('success', "Les messages d'alerte ont bien été mis à jour.");

            return $this->redirectToRoute('admin_changement');
        }

        return $this->render('changement/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

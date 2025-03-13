<?php

namespace App\Controller;

use App\Form\MessagesAlerteType;
use App\Repository\ChangementRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function index(ChangementRepository $changementRepository): Response
    {
        $messages = $changementRepository->findAll();

        $form = $this->createForm(MessagesAlerteType::class, ['messages' => $messages]);

        return $this->render('changement/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

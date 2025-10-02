<?php

namespace App\Controller;

use App\Entity\Configurations;
use App\Form\ConfigurationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationController extends AbstractController
{
    /**
     * @Route("/admin/configuration", name="admin_configuration")
     */
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $configuration = new Configurations();

        $form = $this->createForm(ConfigurationType::class, $configuration);

        return $this->render('configuration/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentsManquantsController extends AbstractController
{
    /**
     * @Route("/admin/documents-manquants", name="admin_documents_manquants")
     */
    public function index(): Response
    {
        return $this->render('documents_manquants/index.html.twig');
    }
}

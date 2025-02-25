<?php

namespace App\Controller;

use App\Builder\VersionBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{
    public function footer(VersionBuilder $versionBuilder): Response
    {
        $versionService = $versionBuilder->make();

        $response = $this->render('_footer.html.twig', [
            'link' => $versionService->link(),
            'version' => $versionService->version(),
        ]);
        $response->setPublic();
        // Une semaine
        $response->setMaxAge(604800);

        return $response;
    }
}

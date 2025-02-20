<?php

namespace App\Controller;

use App\Service\Version;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{
    public function footer(): Response
    {
        $version = Version::create();

        $response = $this->render('_footer.html.twig', [
            'link' => $version->link(),
            'version' => $version->version(),
        ]);
        $response->setPublic();
        // Une semaine
        $response->setMaxAge(604800);

        return $response;
    }
}

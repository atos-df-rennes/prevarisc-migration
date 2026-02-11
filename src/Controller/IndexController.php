<?php

namespace App\Controller;

use App\Builder\VersionBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{
    /**
     * @var VersionBuilder
     */
    private $versionBuilder;

    public function __construct(VersionBuilder $versionBuilder)
    {
        $this->versionBuilder = $versionBuilder;
    }

    public function footer(): Response
    {
        $versionService = $this->versionBuilder->build();
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

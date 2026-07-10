<?php

namespace App\Controller;

use App\Constant\AboutDocument;
use App\Service\Doc;
use Michelf\Markdown;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AboutController extends AbstractController
{
    private Doc $docService;

    public function __construct(Doc $docService)
    {
        $this->docService = $docService;
    }

    /**
     * @Route("/about/{doc}", name="about")
     */
    public function index(string $doc = AboutDocument::ABOUT): Response
    {
        if (!in_array($doc, AboutDocument::ALL)) {
            throw $this->createNotFoundException("Le document que vous avez demandé n'a pas été trouvé.");
        }

        $text = Markdown::defaultTransform($this->docService->get($doc));
        $title = 'À propos';

        switch ($doc) {
            case AboutDocument::DEVELOPERS:
                $title .= ' / Développeurs';
                break;
            case AboutDocument::SUPPORT:
                $title .= ' / Aide';
                break;
            case AboutDocument::TERMS_OF_SERVICE:
                $title .= " / Conditions d'utilisation";
                break;
            default:
                break;
        }

        return $this->render('about/index.html.twig', [
            'text' => $text,
            'title' => $title,
        ]);
    }
}

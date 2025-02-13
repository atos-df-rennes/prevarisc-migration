<?php

namespace App\Controller;

use App\Services\Doc;
use Michelf\Markdown;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/about", name="about_")
 */
class AboutController extends AbstractController
{
    /** @var Doc */
    private $docService;

    public function __construct(Doc $docService)
    {
        $this->docService = $docService;
    }

    /**
     * @Route("/{doc}", name="index")
     */
    public function index(string $doc = 'about'): Response
    {
        $text = Markdown::defaultTransform($this->docService->get($doc));
        $title = 'À propos';

        switch ($doc) {
            case 'dev':
                $title .= ' / Dévelopeurs';
                break;
            case 'support':
                $title .= ' / Aide';
                break;
            case 'tos':
                $title .= " / Conditions d'utilisation";
                break;
            default:
                break;
        }

        return $this->render('about/index.html.twig', [
            'controller_name' => 'AboutController',
            'text' => $text,
            'title' => $title,
        ]);
    }
}

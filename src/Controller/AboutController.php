<?php

namespace App\Controller;

use App\Service\Doc;
use Michelf\Markdown;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @IsGranted("view_doss")
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
     * @Route("/about/{doc}", name="about")
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
            'text' => $text,
            'title' => $title,
        ]);
    }
}

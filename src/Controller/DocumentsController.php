<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class DocumentsController extends AbstractController
{
    /**
     * @Route("/admin/documents", name="admin_documents")
     */
    public function index(string $dataPath, string $realDataPath): Response
    {
        $finder = new Finder();

        $visiblesApresVerrouillage = [];

        $finder
            ->files()
            ->in($realDataPath.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'documents'.DIRECTORY_SEPARATOR.'0')
            ->ignoreVCS(true)
            ->sortByName()
        ;

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $visiblesApresVerrouillage[$file->getFilename()] = $dataPath.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'documents'.DIRECTORY_SEPARATOR.'0'.DIRECTORY_SEPARATOR.$file->getFilename();
            }
        }

        return $this->render('documents/index.html.twig', [
            'visiblesApresVerrouillage' => $visiblesApresVerrouillage,
        ]);
    }
}

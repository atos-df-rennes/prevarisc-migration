<?php

namespace App\Controller;

use App\Repository\CommissionRepository;
use App\Service\Documents;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
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
    public function index(Documents $documents, CommissionRepository $commissionRepository, LoggerInterface $logger): Response
    {
        $commissions = $commissionRepository->findAll();
        $commissionsAsArray = [];

        foreach ($commissions as $commission) {
            if (
                null === $commission->getId()
                || null === $commission->getLibelle()
                || null === $commission->getType()
                || null === $commission->getType()->getLibelle()
            ) {
                $logger->error("La commission n'a pas été trouvée.");

                continue;
            }

            try {
                $commissionsAsArray[$commission->getId()] = [
                    'nom' => $commission->getLibelle(),
                    'type' => $commission->getType()->getLibelle(),
                    'documents' => $documents->findForCommission($commission->getId())
                ];
            } catch (DirectoryNotFoundException $directoryNotFoundException) {
                $logger->error($directoryNotFoundException->getMessage());

                $commissionsAsArray[$commission->getId()] = [
                    'nom' => $commission->getLibelle(),
                    'type' => $commission->getType()->getLibelle(),
                    'documents' => null,
                ];
            }
        }

        return $this->render('documents/index.html.twig', [
            'visibles_apres_verrouillage' => $documents->findForCommission(0),
            'commissions' => $commissionsAsArray,
        ]);
    }
}

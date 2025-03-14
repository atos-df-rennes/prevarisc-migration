<?php

namespace App\Controller;

use App\Dto\FormDocument;
use App\Form\DocumentType;
use App\Repository\CommissionRepository;
use App\Service\Documents;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("admin")
 */
class DocumentsController extends AbstractController
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * @Route("/admin/documents", name="admin_documents")
     */
    public function index(Documents $documents, CommissionRepository $commissionRepository): Response
    {
        $commissions = $commissionRepository->findAll();
        $commissionsAsArray = [];

        // @TODO: Faire ça dans un service
        foreach ($commissions as $commission) {
            if (
                null === $commission->getId()
                || null === $commission->getLibelle()
                || null === $commission->getType()
                || null === $commission->getType()->getLibelle()
            ) {
                $this->logger->error("La commission n'a pas été trouvée.");

                continue;
            }

            try {
                $commissionsAsArray[$commission->getId()] = [
                    'nom' => $commission->getLibelle(),
                    'type' => $commission->getType()->getLibelle(),
                    'documents' => $documents->findForCommission($commission->getId())
                ];
            } catch (DirectoryNotFoundException $directoryNotFoundException) {
                $this->logger->error($directoryNotFoundException->getMessage());

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

    /**
     * @Route("/admin/documents/ajouter", name="admin_documents_add")
     */
    public function ajouter(Request $request, CommissionRepository $commissionRepository, string $documentsRealDataPath): Response
    {
        $commissions = $commissionRepository->findAll();
        $commissionsAsArray = [
            new FormDocument(0, 'Disponibles après verrouillage'),
        ];

        foreach ($commissions as $commission) {
            $commissionsAsArray[] = new FormDocument($commission->getId(), $commission->getLibelle(), $commission->getType()->getLibelle());
        }

        $form = $this->createForm(DocumentType::class, $commissionsAsArray, [
            'action' => $this->generateUrl('admin_documents_add'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $commission = $form->get('commission')->getData();
            $fichier = $form->get('file')->getData();

            if (!$commission instanceof FormDocument) {
                throw new \Exception("La commission n'est pas correcte.");
            }

            if (!$fichier instanceof UploadedFile) {
                throw new \Exception("Le fichier n'est pas correct.");
            }

            try {
                $fichier->move($documentsRealDataPath . $commission->id(), $fichier->getClientOriginalName());
            } catch (FileException $fileException) {
                $this->logger->error("Erreur lors de l'ajout du document dans la Gestion des documents : ".$fileException->getMessage());

                $this->addFlash('danger', "Erreur lors de l'ajout du document.");
            }

            $this->addFlash('success', 'Le document a bien été ajouté.');

            return $this->redirectToRoute("admin_documents");
        }

        return $this->render('documents/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

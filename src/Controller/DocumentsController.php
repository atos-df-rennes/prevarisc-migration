<?php

namespace App\Controller;

use App\Dto\FormDocument;
use App\Form\DocumentType;
use App\Repository\CommissionRepository;
use App\Service\Commission\CommissionDocumentBuilder;
use App\Service\Documents;
use App\Utils\File;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("docs")
 */
class DocumentsController extends AbstractController
{
    public function __construct(private LoggerInterface $logger, private CommissionRepository $commissionRepository, private CommissionDocumentBuilder $commissionDocumentBuilder, private Filesystem $filesystem)
    {
    }

    /**
     * @Route("/admin/documents", name="admin_documents")
     */
    public function index(Documents $documents): Response
    {
        $commissions = $this->commissionRepository->findAll();

        return $this->render('documents/index.html.twig', [
            'visibles_apres_verrouillage' => $documents->findForCommission(0),
            'commissions' => $this->commissionDocumentBuilder->buildWithCommissions($commissions),
        ]);
    }

    /**
     * @Route("/admin/documents/ajouter", name="admin_documents_add", options={"expose"=true})
     */
    public function ajouter(Request $request, string $documentsRealDataPath): Response
    {
        $commissions = $this->commissionRepository->findAll();
        $commissionsAsArray = [
            new FormDocument(0, 'Disponibles après verrouillage'),
        ];

        foreach ($commissions as $commission) {
            if (
                in_array(null, [$commission->getId(), $commission->getLibelle()], true)
                || null === $commission->getType()
                || null === $commission->getType()->getLibelle()
            ) {
                $this->logger->error("La commission n'a pas été trouvée.");

                continue;
            }

            $commissionsAsArray[] = new FormDocument($commission->getId(), $commission->getLibelle(), $commission->getType()->getLibelle());
        }

        $form = $this->createForm(DocumentType::class, $commissionsAsArray, [
            'action' => $this->generateUrl('admin_documents_add'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $commission = $form->get('commission')->getData();
                $fichier = $form->get('file')->getData();

                if (!$commission instanceof FormDocument) {
                    throw new \Exception("La commission n'est pas correcte.");
                }

                if (!$fichier instanceof UploadedFile) {
                    throw new \Exception("Le fichier n'est pas correct.");
                }

                try {
                    // @todo: Générer un nom de fichier plus sûr
                    $fichier->move($documentsRealDataPath.$commission->id(), File::sanitize($fichier->getClientOriginalName()));

                    $this->addFlash('success', 'Le document a bien été ajouté.');
                } catch (FileException $fileException) {
                    $this->logger->error('Erreur lors du déplacement du document dans la Gestion des documents : '.$fileException->getMessage());

                    $this->addFlash('danger', "Erreur lors de l'ajout du document.");
                } catch (\InvalidArgumentException $invalidArgumentException) {
                    $this->logger->error('Erreur lors du nommage du document dans la Gestion des documents : '.$invalidArgumentException->getMessage());

                    $this->addFlash('danger', "Erreur lors de l'ajout du document.");
                }

                return $this->redirectToRoute('admin_documents');
            }

            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }

            return $this->redirectToRoute('admin_documents');
        }

        return $this->render('documents/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/documents/supprimer", name="admin_documents_delete", methods={"POST"}, options={"expose"=true})
     */
    public function supprimer(Request $request, string $documentsRealDataPath): Response
    {
        // @todo: Générer un nom de fichier plus sûr
        $nomFichier = $request->request->get('nomFichier');

        if (!\is_string($nomFichier)) {
            $this->logger->error("Le nom du fichier n'est pas correct.", ['nomFichier' => $nomFichier]);
            $this->addFlash('danger', 'Erreur lors de la suppression du document.');

            return $this->json([], 400);
        }

        $nomFichier = File::sanitize($nomFichier);
        $idCommission = filter_var($request->request->get('idCommission'), FILTER_VALIDATE_INT);

        if (false === $idCommission) {
            $this->logger->error("L'identifiant de la commission n'est pas correct.", ['idCommission' => $request->request->get('idCommission')]);
            $this->addFlash('danger', 'Erreur lors de la suppression du document.');

            return $this->json([], 400);
        }

        $filepath = $documentsRealDataPath.$idCommission.DIRECTORY_SEPARATOR.$nomFichier;

        $this->logger->debug('Suppression du document.', ['chemin' => $filepath]);

        try {
            $this->filesystem->remove($filepath);
        } catch (IOException $ioException) {
            $this->logger->error($ioException->getMessage());
            $this->addFlash('danger', 'Erreur lors de la suppression du document.');

            return $this->json([], 500);
        }

        $this->addFlash('success', 'Le document a bien été supprimé.');

        return $this->json([]);
    }
}

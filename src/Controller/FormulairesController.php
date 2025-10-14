<?php

namespace App\Controller;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Rubrique;
use App\Form\RubriqueType;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\ChampValeurListeRepository;
use App\Service\Formulaires\FormulairesImporter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @IsGranted("gestion_formulaires")
 */
class FormulairesController extends AbstractController
{
    /**
     * @Route("/admin/formulaires", name="admin_formulaires")
     */
    public function index(Request $request, CapsuleRubriqueRepository $capsuleRubriqueRepository, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $formulaires = $capsuleRubriqueRepository->findAllInformations();

        /** @var array<string, FormInterface<Rubrique>> $forms */
        $forms = [];
        foreach ($formulaires as $formulaire) {
            $rubrique = new Rubrique();
            $rubrique->setIdx($formulaire->getRubriques()->count() + 1);
            $rubrique->setCapsuleRubrique($formulaire);

            $forms[$formulaire->getNomInterne()] = $formFactory->createNamed($formulaire->getNomInterne(), RubriqueType::class, $rubrique);
        }

        $submittedForm = $request->request->get('formulaire');

        if (null !== $submittedForm) {
            if (!\is_string($submittedForm)) {
                throw new \InvalidArgumentException('Le nom du formulaire doit être une chaîne de caractère.');
            }

            if (!array_key_exists($submittedForm, $forms)) {
                throw new \OutOfBoundsException(sprintf("Le formulaire %s n'existe pas.", $submittedForm));
            }

            $form = $forms[$submittedForm];
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $submittedRubrique = $form->getData();

                $entityManager->persist($submittedRubrique);
                $entityManager->flush();

                return $this->redirectToRoute('admin_formulaires');
            }
        }

        return $this->render('formulaires/index.html.twig', [
            'formulaires' => $formulaires,
            'forms' => array_map(function (FormInterface $form) {
                return $form->createView();
            }, $forms),
        ]);
    }

    /**
     * @Route("/admin/formulaires/modifier-nom-capsule-rubrique", name="admin_formulaire_edit_capsule_rubrique_nom")
     */
    public function editCapsuleRubriqueNom(Request $request, EntityManagerInterface $entityManager, CapsuleRubriqueRepository $capsuleRubriqueRepository): JsonResponse
    {
        $idCapsuleRubrique = filter_var($request->request->get('id'), FILTER_VALIDATE_INT);

        $capsuleRubrique = $capsuleRubriqueRepository->find($idCapsuleRubrique);

        if (null === $capsuleRubrique) {
            throw new NotFoundHttpException(\sprintf("La capsule rubrique d'identifiant %d n'existe pas.", $idCapsuleRubrique));
        }

        $nom = $request->request->get('nom');

        if (!\is_string($nom)) {
            throw new \InvalidArgumentException('Le nom du formulaire doit être une chaîne de caractère.');
        }

        $capsuleRubrique->setNom($nom);
        $entityManager->flush();

        return $this->json(['status' => 'success']);
    }

    /**
     * @Route("/admin/formulaires/export", name="admin_formulaires_export")
     */
    public function export(SerializerInterface $serializer, CapsuleRubriqueRepository $capsuleRubriqueRepository): JsonResponse
    {
        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context): ?int {
                if (
                    !$object instanceof CapsuleRubrique
                    && !$object instanceof Rubrique
                    && !$object instanceof Champ
                ) {
                    throw new CircularReferenceException('A circular reference has been detected when serializing the object of class "'.get_debug_type($object).'".');
                }

                return $object->getId();
            },
            AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
        ];
        $dispositions = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'configuration_formulaires_personnalises.json');

        return $this->json($capsuleRubriqueRepository->findAll(), 200, ['Content-Disposition' => $dispositions], $context);
    }

    /**
     * @Route("/admin/formulaires/import", name="admin_formulaires_import")
     */
    public function import(
        Request $request,
        SerializerInterface $serializer,
        FormulairesImporter $formulairesImporter,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        /** @var UploadedFile $configurationFile */
        $configurationFile = $request->files->get('configuration-file');
        $deleteExistingConfiguration = $request->request->get('delete-existing-configuration');

        try {
            $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($configurationFile, $deleteExistingConfiguration, $serializer, $formulairesImporter): void {
                if ($deleteExistingConfiguration) {
                    $formulairesImporter->deleteExistingConfiguration($entityManager);
                }

                $configurationFileJsonContent = file_get_contents($configurationFile->getPathname());
                /** @var CapsuleRubrique[] $capsulesRubriques */
                $capsulesRubriques = $serializer->deserialize($configurationFileJsonContent, CapsuleRubrique::class.'[]', 'json');

                $formulairesImporter->importConfiguration($entityManager, $capsulesRubriques);
            });

            $this->addFlash('success', 'La configuration a bien été importée.');
        } catch (\Exception $exception) {
            $logger->error($exception->getMessage(), ['service' => 'Importeur de configuration de formulaires']);

            $this->addFlash('error', "Un problème a eu lieu lors de l'import de la configuration.");
        }

        return $this->redirectToRoute('admin_formulaires');
    }
}

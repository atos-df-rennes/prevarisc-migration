<?php

namespace App\Controller;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Rubrique;
use App\Form\RubriqueType;
use App\Repository\CapsuleRubriqueRepository;
use App\Service\Formulaires\FormulairesImporter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('gestion_formulaires')]
final class FormulairesController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly CapsuleRubriqueRepository $capsuleRubriqueRepository, private readonly FormFactoryInterface $formFactory, private readonly SerializerInterface $serializer, private readonly FormulairesImporter $formulairesImporter, private readonly LoggerInterface $logger)
    {
    }

    #[Route(path: '/admin/formulaires', name: 'admin_formulaires')]
    public function index(Request $request): Response
    {
        $formulaires = $this->capsuleRubriqueRepository->findAllInformations();

        /** @var array<string, FormInterface> $forms */
        $forms = [];
        foreach ($formulaires as $formulaire) {
            $nomInterne = $formulaire->getNomInterne();
            if (null === $nomInterne) {
                continue;
            }

            $rubrique = new Rubrique();
            $rubrique->setIdx($formulaire->getRubriques()->count() + 1);
            $rubrique->setCapsuleRubrique($formulaire);

            $forms[$nomInterne] = $this->formFactory->createNamed($nomInterne, RubriqueType::class, $rubrique);
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

                if (!$submittedRubrique instanceof Rubrique) {
                    throw new \LogicException(sprintf('Le formulaire de rubrique doit retourner une Rubrique, %s reçu.', get_debug_type($submittedRubrique)));
                }

                $this->entityManager->persist($submittedRubrique);
                $this->entityManager->flush();

                $this->addFlash('success', 'La rubrique a bien été ajoutée.');

                return $this->redirectToRoute('admin_formulaires');
            }
        }

        return $this->render('formulaires/index.html.twig', [
            'formulaires' => $formulaires,
            'forms' => array_map(fn (FormInterface $form): FormView => $form->createView(), $forms),
        ]);
    }

    #[Route(path: '/admin/formulaires/modifier-nom-capsule-rubrique', name: 'admin_formulaires_edit_capsule_rubrique_nom')]
    public function editCapsuleRubriqueNom(Request $request): JsonResponse
    {
        $idCapsuleRubrique = filter_var($request->request->get('id'), FILTER_VALIDATE_INT);

        $capsuleRubrique = $this->capsuleRubriqueRepository->find($idCapsuleRubrique);

        if (null === $capsuleRubrique) {
            throw new NotFoundHttpException(\sprintf("La capsule rubrique d'identifiant %d n'existe pas.", $idCapsuleRubrique));
        }

        $nom = $request->request->get('nom');

        if (!\is_string($nom)) {
            throw new \InvalidArgumentException('Le nom du formulaire doit être une chaîne de caractère.');
        }

        $capsuleRubrique->setNom($nom);
        $this->entityManager->flush();

        return $this->json(['status' => 'success']);
    }

    #[Route(path: '/admin/formulaires/export', name: 'admin_formulaires_export')]
    public function export(): JsonResponse
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
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['valeurs', '__initializer__', '__cloner__', '__isInitialized__'],
        ];
        $dispositions = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'configuration_formulaires_personnalises.json');

        return $this->json($this->capsuleRubriqueRepository->findAll(), 200, ['Content-Disposition' => $dispositions], $context);
    }

    #[Route(path: '/admin/formulaires/import', name: 'admin_formulaires_import')]
    public function import(
        Request $request
    ): Response {
        /** @var UploadedFile $configurationFile */
        $configurationFile = $request->files->get('configuration-file');
        $deleteExistingConfiguration = $request->request->get('delete-existing-configuration');

        try {
            $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($configurationFile, $deleteExistingConfiguration): void {
                if ($deleteExistingConfiguration) {
                    $this->formulairesImporter->deleteExistingConfiguration($entityManager);
                }

                $configurationFileJsonContent = file_get_contents($configurationFile->getPathname());
                /** @var CapsuleRubrique[] $capsulesRubriques */
                $capsulesRubriques = $this->serializer->deserialize($configurationFileJsonContent, CapsuleRubrique::class.'[]', 'json');

                $this->formulairesImporter->importConfiguration($entityManager, $capsulesRubriques);
            });

            $this->addFlash('success', 'La configuration a bien été importée.');
        } catch (\Exception $exception) {
            $this->logger->error("Erreur lors de l'import de la configuration des formulaires.", [
                'exception' => $exception,
            ]);

            $this->addFlash('danger', "Un problème a eu lieu lors de l'import de la configuration.");
        }

        return $this->redirectToRoute('admin_formulaires');
    }
}

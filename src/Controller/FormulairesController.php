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

        /** @var array<string, \Symfony\Component\Form\FormView> $forms */
        $forms = [];
        foreach ($formulaires as $formulaire) {
            $rubrique = new Rubrique();
            $rubrique->setIdx($formulaire->getRubriques()->count() + 1);
            $rubrique->setCapsuleRubrique($formulaire);

            $forms[$formulaire->getNomInterne()] = $formFactory->createNamed($formulaire->getNomInterne(), RubriqueType::class, $rubrique);
        }

        $submittedForm = $request->request->get('formulaire');

        if (null !== $submittedForm) {
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
        $nom = $request->request->get('nom');

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

    // @todo: A mettre dans ChampController
    /**
     * @Route("/admin/formulaires/update-champ-valeur-liste-idx", name="admin_formulaires_order_champ_valeur_liste")
     */
    public function orderChampValeurListe(Request $request, ChampValeurListeRepository $champValeurListeRepository, EntityManagerInterface $entityManager): void
    {
        $champValeurListeId = filter_var($request->request->get('ID'), FILTER_VALIDATE_INT);

        if (false === $champValeurListeId) {
            throw new \InvalidArgumentException("L'identifiant de la valeur de liste n'a pas pu être récupéré correctement.");
        }

        $champValeurListe = $champValeurListeRepository->find($champValeurListeId);

        if (null === $champValeurListe) {
            throw new NotFoundHttpException(\sprintf("La valeur de liste d'identifiant %d n'existe pas.", $champValeurListeId));
        }

        $idx = filter_var($request->request->get('idx'), FILTER_VALIDATE_INT);

        if (false === $idx) {
            throw new \InvalidArgumentException("L'index de la valeur de liste n'a pas pu être récupéré correctement.");
        }

        $champValeurListe->setIdx($idx);

        $entityManager->flush();
    }
}

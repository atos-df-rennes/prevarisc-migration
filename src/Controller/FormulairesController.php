<?php

namespace App\Controller;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Rubrique;
use App\Repository\CapsuleRubriqueRepository;
use App\Service\Formulaires\FormulairesImporter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        // @todo: Message de succès/échec
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

            $importStatus = 'success';
        } catch (\Exception $exception) {
            $logger->error($exception->getMessage(), ['service' => 'Importeur de configuration de formulaires']);
            $importStatus = 'error';
        }

        // @todo: Lorsque la page des formulaires sera migrée, faire le $importStatus avec un flashMessage
        return $this->redirect('/formulaire?importStatus='.$importStatus);
    }
}

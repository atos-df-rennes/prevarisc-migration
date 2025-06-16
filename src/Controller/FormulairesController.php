<?php

namespace App\Controller;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\Rubrique;
use App\Repository\CapsuleRubriqueRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    // @todo: Permettre de pouvoir choisir entre supprimer ou non la configuration existante
    /**
     * @Route("/admin/formulaires/export", name="admin_formulaires_export")
     */
    public function index(SerializerInterface $serializer, CapsuleRubriqueRepository $capsuleRubriqueRepository): JsonResponse
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
        $jsonContent = $serializer->serialize($capsuleRubriqueRepository->findAllElements(), 'json', $context);

        $response = new JsonResponse($jsonContent);
        $dispositions = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'configuration_formulaires_personnalises.json');
        $response->headers->set('Content-Disposition', $dispositions);

        return $response;
    }
}

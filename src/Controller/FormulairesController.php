<?php

namespace App\Controller;

use App\Entity\CapsuleRubrique;
use App\Entity\Champ;
use App\Entity\ChampValeurListe;
use App\Entity\Rubrique;
use App\Repository\CapsuleRubriqueRepository;
use App\Repository\TypeChampRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function import(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, CapsuleRubriqueRepository $capsuleRubriqueRepository, TypeChampRepository $typeChampRepository): Response
    {
        // @todo: A déplacer dans un service
        // @todo: Message de succès/échec
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $configurationFile */
        $configurationFile = $request->files->get('configuration-file');
        $deleteExistingConfiguration = $request->request->get('delete-existing-configuration');

        // @todo: Mettre le tout dans une transaction pour pouvoir rollback si l'ajout ne fonctionne pas ?
        if ($deleteExistingConfiguration) {
            $existingCapsulesRubriques = $capsuleRubriqueRepository->findAll();

            foreach ($existingCapsulesRubriques as $existingCapsuleRubrique) {
                $existingCapsuleRubrique->getRubriques()->clear();

                $entityManager->persist($existingCapsuleRubrique);
            }

            $entityManager->flush();
        }

        $configurationFileJsonContent = file_get_contents($configurationFile->getPathname());
        /** @var CapsuleRubrique[] $capsulesRubriques */
        $capsulesRubriques = $serializer->deserialize($configurationFileJsonContent, CapsuleRubrique::class.'[]', 'json');

        foreach ($capsulesRubriques as $capsuleRubrique) {
            $managedCapsuleRubrique = $capsuleRubriqueRepository->findOneBy(['nomInterne' => $capsuleRubrique->getNomInterne()]);

            foreach ($capsuleRubrique->getRubriques() as $rubrique) {
                $newRubrique = new Rubrique();
                $newRubrique->setNom($rubrique->getNom());
                $newRubrique->setDefaultDisplay($rubrique->getDefaultDisplay());
                $newRubrique->setIdx($rubrique->getIdx());
                $newRubrique->setCapsuleRubrique($managedCapsuleRubrique);

                $entityManager->persist($newRubrique);
                $entityManager->flush();

                foreach ($rubrique->getChamps() as $champ) {
                    if ($champ->getParent() instanceof Champ) {
                        continue;
                    }

                    $managedType = $typeChampRepository->findOneBy(['type' => $champ->getType()->getType()]);

                    $newChamp = new Champ();
                    $newChamp->setNom($champ->getNom());
                    $newChamp->setType($managedType);
                    $newChamp->setTableau($champ->getTableau());
                    $newChamp->setIdx($champ->getIdx());
                    $newChamp->setRubrique($newRubrique);

                    $entityManager->persist($newChamp);
                    $entityManager->flush();

                    foreach ($champ->getValeursListe() as $valeurListe) {
                        $newValeurListe = new ChampValeurListe();
                        $newValeurListe->setValeur($valeurListe->getValeur());
                        $newValeurListe->setChamp($newChamp);

                        $entityManager->persist($newValeurListe);
                        $entityManager->flush();

                        $newChamp->addValeursListe($newValeurListe);
                    }

                    if (!$champ->getEnfants()->isEmpty()) {
                        foreach ($champ->getEnfants() as $enfant) {
                            $enfantManagedType = $typeChampRepository->findOneBy(['type' => $enfant->getType()->getType()]);

                            $enfantNewChamp = new Champ();
                            $enfantNewChamp->setNom($enfant->getNom());
                            $enfantNewChamp->setType($enfantManagedType);
                            $enfantNewChamp->setTableau($enfant->getTableau());
                            $enfantNewChamp->setIdx($enfant->getIdx());
                            $enfantNewChamp->setRubrique($newRubrique);
                            $enfantNewChamp->setParent($newChamp);

                            $entityManager->persist($enfantNewChamp);
                            $entityManager->flush();

                            foreach ($enfant->getValeursListe() as $enfantValeurListe) {
                                $enfantNewValeurListe = new ChampValeurListe();
                                $enfantNewValeurListe->setValeur($enfantValeurListe->getValeur());
                                $enfantNewValeurListe->setChamp($enfantNewChamp);

                                $entityManager->persist($enfantNewValeurListe);
                                $entityManager->flush();

                                $enfantNewChamp->addValeursListe($enfantNewValeurListe);
                            }

                            $entityManager->persist($enfantNewChamp);
                            $entityManager->flush();

                            $newChamp->addEnfant($enfantNewChamp);
                        }
                    }

                    $entityManager->persist($newChamp);
                    $entityManager->flush();
                }
            }
        }

        return $this->redirect('/formulaire');
    }
}

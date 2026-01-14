<?php

namespace App\Controller;

use App\Dto\Commission\CommissionDatesDto;
use App\Dto\Commission\DateCommissionItemDto;
use App\Form\CalendrierDesCommissions\CommissionDatesType;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeEvenementRepository;
use App\Service\Commission\CommissionDatesTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller de test pour le formulaire de création de dates de commission.
 *
 * @Route("/test/commission")
 */
class CommissionTestController extends AbstractController
{
    /**
     * Route de test affichant le formulaire avec des données simulées.
     *
     * @Route("/form-test", name="commission_form_test", methods={"GET", "POST"})
     */
    public function formTest(
        Request $request,
        CommissionTypeEvenementRepository $typeEvenementRepository,
        CommissionRepository $commissionRepository,
        CommissionDatesTransformer $transformer,
        EntityManagerInterface $entityManager
    ): Response {
        // Création d'un DTO avec données simulées
        $dto = $this->createMockDto($typeEvenementRepository);

        // Création du formulaire
        $form = $this->createForm(CommissionDatesType::class, $dto);
        $form->handleRequest($request);

        // Traitement de la soumission
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération d'une commission de test (première de la liste)
            $commission = $commissionRepository->findOneBy([]);

            if (!$commission) {
                $this->addFlash('error', 'Aucune commission trouvée en base pour le test');

                return $this->redirectToRoute('commission_form_test');
            }

            // Transformation DTO → Entités
            $dateCommissions = $transformer->transformToEntities($dto, $commission);

            // Persiste et flush (commenté pour éviter de polluer la BDD en test)
            // foreach ($dateCommissions as $dateCommission) {
            //     $entityManager->persist($dateCommission);
            // }
            // $entityManager->flush();

            $this->addFlash('success', sprintf(
                '%d date(s) de commission créée(s) (simulation - non persisté)',
                count($dateCommissions)
            ));

            // Affichage des dates créées pour vérification
            return $this->render('test/commission/result.html.twig', [
                'dto' => $dto,
                'dateCommissions' => $dateCommissions,
                'commission' => $commission,
            ]);
        }

        return $this->render('test/commission/form.html.twig', [
            'form' => $form->createView(),
            'dto' => $dto,
        ]);
    }

    /**
     * Crée un DTO avec des données de test simulées.
     */
    private function createMockDto(CommissionTypeEvenementRepository $typeEvenementRepository): CommissionDatesDto
    {
        $dto = new CommissionDatesDto();

        // Libellé simulé
        $dto->setLibelle('Visite périodique - Établissement TEST');

        // Type événement (prendre le premier en base)
        $typeEvenement = $typeEvenementRepository->findOneBy([]);
        if ($typeEvenement) {
            $dto->setTypeEvenement($typeEvenement);
        }

        // Ajout de 3 dates de test
        $baseDate = new \DateTime('2026-01-20');
        for ($i = 0; $i < 3; ++$i) {
            $date = clone $baseDate;
            $date->modify('+' . ($i * 7) . ' days');

            $dateItem = new DateCommissionItemDto();
            $dateItem->setDate($date);
            $dateItem->setHeureDebut(new \DateTime('09:00'));
            $dateItem->setHeureFin(new \DateTime('18:00'));

            $dto->addDate($dateItem);
        }

        // Périodicité désactivée par défaut
        $dto->setWithPeriodicite(false);
        $dto->setPeriodicite(1);

        return $dto;
    }
}

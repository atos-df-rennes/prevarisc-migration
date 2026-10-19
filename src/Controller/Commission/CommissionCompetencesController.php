<?php

namespace App\Controller\Commission;

use App\Entity\Commission;
use App\Form\CommissionReglesCollectionType;
use App\Repository\EtablissementInformationsRepository;
use App\Service\Commission\CommissionRegleCompetenceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('gestion_commissions')]
final class CommissionCompetencesController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly CommissionRegleCompetenceService $competenceService, private readonly EtablissementInformationsRepository $etablissementInformationsRepository)
    {
    }

    /**
     * Onglet Compétences — édition des règles de compétence d'une commission.
     */
    #[Route(path: '/admin/commissions/{id}/competences', name: 'admin_commissions_competences', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function competences(Commission $commission, Request $request): Response
    {
        /*
         * Le fait de passer uniquement la commission sans requête avec select et jointures au repository
         * crée des requêtes N+1. Dans ce cas on l'accepte car il y a un nombre limité de règles pour chaque commission et
         * le fait de créer une requête custom pour éviter les N+1 dégrade grandement les temps de réponse.
         */
        $form = $this->createForm(CommissionReglesCollectionType::class, $commission, [
            'commission' => $commission,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Les règles de compétence ont été sauvegardées avec succès.');

            return $this->redirectToRoute('admin_commissions_competences', ['id' => $commission->getId()]);
        }

        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'competences',
            'form' => $form,
        ]);
    }

    /**
     * Applique les règles de compétence d'une commission à l'ensemble des établissements ERP et IGH.
     * Met à jour ID_COMMISSION sur les établissements correspondants, puis vide le cache legacy.
     */
    #[Route(path: '/admin/commissions/{id}/competences/appliquer-regles', name: 'admin_commissions_apply_regles', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function applyRegles(Request $request, Commission $commission): Response
    {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('apply_regles_competence', $csrfToken)) {
            $this->addFlash('danger', 'Token de sécurité invalide. Veuillez réessayer.');

            return $this->redirectToRoute('admin_commissions_competences', ['id' => $commission->getId()]);
        }

        $etablissements = $this->etablissementInformationsRepository->findForCompetenceApplication();
        $matchingIds = $this->competenceService->findMatchingEtablissementIds($commission, $etablissements);

        if ([] === $matchingIds) {
            $this->addFlash('info', 'Aucun établissement ne correspond aux règles de compétence définies.');

            return $this->redirectToRoute('admin_commissions_competences', ['id' => $commission->getId()]);
        }

        $numberOfUpdatedEtablissements = $this->etablissementInformationsRepository->updateCommissionForEtablissements(
            (int) $commission->getId(),
            $matchingIds
        );

        if (0 === $numberOfUpdatedEtablissements) {
            $this->addFlash('info', 'Les règles de compétence ont déjà été appliquées à tous les établissements concernés.');
        } else {
            $this->addFlash('success', \sprintf('Les règles de compétence ont été appliquées à %d établissement(s).', $numberOfUpdatedEtablissements));
        }

        // @todo Cache standard : invalider le cache $cache (équivalent Zend cache->clean()) une fois
        // qu'un mécanisme Symfony de cache standard (miroir du $cache legacy) sera mis en place.
        // Ne pas confondre avec le TagAwareCacheInterface ($cacheSearch) qui ne semble pas utilisé ici.
        return $this->redirectToRoute('admin_commissions_competences', ['id' => $commission->getId()]);
    }
}

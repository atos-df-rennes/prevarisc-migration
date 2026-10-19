<?php

namespace App\Controller;

use App\Constant\Fonction;
use App\Entity\Commune;
use App\Form\CommuneType;
use App\Repository\CommuneRepository;
use App\Repository\FonctionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(requirements: ['id' => '\d+'])]
#[IsGranted('communes')]
final class CommunesController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly CommuneRepository $communeRepository, private readonly FonctionRepository $fonctionRepository)
    {
    }

    #[Route(path: '/admin/communes', name: 'admin_communes')]
    public function index(Request $request): Response
    {
        $communes = $this->communeRepository->findBy([], ['libelle' => 'ASC']);
        $selectedId = $request->query->getInt('id');

        return $this->render('communes/index.html.twig', [
            'communes' => $communes,
            'selectedId' => $selectedId,
        ]);
    }

    #[Route(path: '/admin/communes/{id}', name: 'admin_commune_infos', options: ['expose' => true])]
    public function informations(Request $request, Commune $commune): Response
    {
        // Force la fonction à maire pour les communes
        $utilisateurInformations = $commune->getUtilisateurInformations();
        if (null !== $utilisateurInformations) {
            $fonctionMaire = $this->fonctionRepository->find(Fonction::MAIRE);
            if ($fonctionMaire) {
                $utilisateurInformations->setFonction($fonctionMaire);
            }
        }

        $form = $this->createForm(CommuneType::class, $commune);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($commune);
            $this->entityManager->flush();

            $this->addFlash('success', \sprintf('La commune %s a bien été enregistrée.', $commune->getLibelle()));

            return $this->redirectToRoute('admin_communes', ['id' => $commune->getId()]);
        }

        return $this->render('communes/_informations.html.twig', [
            'form' => $form,
            'commune' => $commune,
        ]);
    }
}

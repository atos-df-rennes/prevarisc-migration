<?php

namespace App\Controller;

use App\Entity\Commune;
use App\Form\CommuneType;
use App\Repository\CommuneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(requirements={"id"="\d+"})
 *
 * @IsGranted("communes")
 */
class CommunesController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CommuneRepository */
    private $communeRepository;

    public function __construct(EntityManagerInterface $entityManager, CommuneRepository $communeRepository)
    {
        $this->entityManager = $entityManager;
        $this->communeRepository = $communeRepository;
    }

    /**
     * @Route("/admin/communes", name="admin_communes")
     */
    public function index(): Response
    {
        $communes = $this->communeRepository->findBy([], ['libelle' => 'ASC']);

        return $this->render('communes/index.html.twig', [
            'communes' => $communes,
        ]);
    }

    /**
     * @Route("/admin/communes/{id}", name="admin_commune_infos", options={"expose"=true})
     */
    public function informations(Request $request, Commune $commune): Response
    {
        $form = $this->createForm(CommuneType::class, $commune);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($commune);
            $this->entityManager->flush();

            $this->addFlash('success', \sprintf('La commune %s a bien été enregistrée.', $commune->getLibelle()));

            return $this->redirectToRoute('admin_communes');
        }

        return $this->render('communes/_informations.html.twig', [
            'form' => $form->createView(),
            'commune' => $commune,
        ]);
    }
}

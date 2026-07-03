<?php

namespace App\Controller;

use App\Constant\Fonction;
use App\Entity\Commune;
use App\Form\CommuneType;
use App\Repository\CommuneRepository;
use App\Repository\FonctionRepository;
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

    /** @var FonctionRepository */
    private $fonctionRepository;

    public function __construct(EntityManagerInterface $entityManager, CommuneRepository $communeRepository, FonctionRepository $fonctionRepository)
    {
        $this->entityManager = $entityManager;
        $this->communeRepository = $communeRepository;
        $this->fonctionRepository = $fonctionRepository;
    }

    /**
     * @Route("/admin/communes", name="admin_communes")
     */
    public function index(Request $request): Response
    {
        $communes = $this->communeRepository->findBy([], ['libelle' => 'ASC']);
        $selectedId = $request->query->getInt('id');

        return $this->render('communes/index.html.twig', [
            'communes' => $communes,
            'selectedId' => $selectedId,
        ]);
    }

    /**
     * @Route("/admin/communes/{id}", name="admin_commune_infos", options={"expose"=true})
     */
    public function informations(Request $request, Commune $commune): Response
    {
        // Initialize fonction to MAIRE if not set
        $utilisateurInformations = $commune->getUtilisateurInformations();
        if ($utilisateurInformations && !$utilisateurInformations->getFonction()) {
            $mairiFonction = $this->fonctionRepository->find(Fonction::MAIRE);
            if ($mairiFonction) {
                $utilisateurInformations->setFonction($mairiFonction);
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
            'form' => $form->createView(),
            'commune' => $commune,
        ]);
    }
}

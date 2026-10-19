<?php

namespace App\Controller;

use App\Form\MessagesAlerteType;
use App\Repository\ChangementRepository;
use App\Service\Changement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression("is_granted('alerte_statut') or is_granted('alerte_avis') or is_granted('alerte_classement')"))]
final class ChangementController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly ChangementRepository $changementRepository, private readonly Changement $changement)
    {
    }

    #[Route(path: '/admin/changement', name: 'admin_changement')]
    public function index(Request $request): Response
    {
        $messages = $this->changementRepository->findAll();

        $form = $this->createForm(MessagesAlerteType::class, ['messages' => $messages]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($messages as $message) {
                $this->entityManager->persist($message);
            }

            $this->entityManager->flush();

            $this->addFlash('success', "Les messages d'alerte ont bien été mis à jour.");

            return $this->redirectToRoute('admin_changement');
        }

        return $this->render('changement/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/changement/balises', name: 'changement_balises', options: ['expose' => true])]
    public function balises(): Response
    {
        return $this->render('changement/balises.html.twig', [
            'balises' => $this->changement->getBalises(),
        ]);
    }
}

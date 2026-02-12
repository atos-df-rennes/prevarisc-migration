<?php

namespace App\Controller;

use App\Entity\UtilisateurInformations;
use App\Form\ContactType;
use App\Repository\UtilisateurInformationsRepository;
use App\Service\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @var UtilisateurInformationsRepository
     */
    private $utilisateurInformationsRepository;

    public function __construct(UtilisateurInformationsRepository $utilisateurInformationsRepository)
    {
        $this->utilisateurInformationsRepository = $utilisateurInformationsRepository;
    }

    /**
     * @Route("/contact/{id}/modifier", name="contact_edit")
     */
    public function edit(Request $request, UtilisateurInformations $contact, EntityManagerInterface $entityManager): Response
    {
        $elementLie = $request->query->get('elementLie');
        $elementId = $request->query->get('elementId');

        switch ($elementLie) {
            case Contact::ETABLISSEMENT:
                $redirectRoute = 'etablissement_contacts';

                break;
            case Contact::GROUPEMENT:
                $redirectRoute = 'admin_groupement';

                break;
            default:
                $redirectRoute = null;

                break;
        }

        if (null === $redirectRoute) {
            throw new \InvalidArgumentException("La route de redirection n'est pas définie.");
        }

        $form = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('contact_edit', [
                'id' => $contact->getId(),
                'elementLie' => $elementLie,
                'elementId' => $elementId,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été modifié.');

            return $this->redirectToRoute($redirectRoute, ['id' => $elementId]);
        }

        return $this->render('contact/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
        ]);
    }

    /**
     * @Route("/contacts/{search}", name="contact_autocomplete")
     */
    public function autocomplete(string $search): JsonResponse
    {
        return $this->json($this->utilisateurInformationsRepository->findByNameNotInUser($search));
    }
}

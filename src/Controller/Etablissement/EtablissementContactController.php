<?php

namespace App\Controller\Etablissement;

use App\Controller\Contract\EtablissementLateralNavigationInterface;
use App\Entity\Etablissement;
use App\Entity\UtilisateurInformations;
use App\Form\ContactType;
use App\Repository\EtablissementRepository;
use App\Repository\UtilisateurInformationsRepository;
use App\Service\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gère les contacts d'un établissement.
 */
#[Route(requirements: ['id' => '\d+'])]
final class EtablissementContactController extends AbstractController implements EtablissementLateralNavigationInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly EtablissementRepository $etablissementRepository, private readonly UtilisateurInformationsRepository $utilisateurInformationsRepository, private readonly Contact $contactService)
    {
    }

    #[Route(path: '/etablissement/{id}/contacts', name: 'etablissement_contacts')]
    #[IsGranted('view_ets', subject: 'etablissement')]
    public function contacts(Etablissement $etablissement): Response
    {
        $dto = $this->etablissementRepository->findContactsWithInheritance($etablissement->getId());

        return $this->render('etablissement/contacts.html.twig', [
            'etablissement' => $etablissement,
            'contacts' => $dto->contacts,
            'contacts_parents' => $dto->contactsParents,
        ]);
    }

    #[Route(path: '/etablissement/{id}/contacts/ajouter', name: 'etablissement_contacts_add', defaults: ['_etablissement_lateral_nav' => false])]
    #[IsGranted('edit_ets', subject: 'etablissement')]
    public function addContact(Request $request, Etablissement $etablissement): Response
    {
        $contact = new UtilisateurInformations();

        $form = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('etablissement_contacts_add', ['id' => $etablissement->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($contact);
            $etablissement->addContact($contact);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été ajouté.');

            return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
        }

        return $this->render('contact/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/etablissement/{id}/contacts/ajouter/existant', name: 'etablissement_contacts_add_existing', methods: ['POST'], defaults: ['_etablissement_lateral_nav' => false])]
    #[IsGranted('edit_ets', subject: 'etablissement')]
    public function addExistingContact(
        Request $request,
        Etablissement $etablissement
    ): RedirectResponse {
        if (!is_string($request->request->get('token')) || !$this->isCsrfTokenValid('etablissement_contacts_add_existing_'.$etablissement->getId(), $request->request->get('token'))) {
            throw $this->createAccessDeniedException("Le jeton CSRF n'est pas valide.");
        }

        $contactIdsString = $request->request->get('contacts');

        if (!is_string($contactIdsString) || '' === $contactIdsString) {
            return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
        }

        if (!preg_match('/^\d+(?:,\d+)*$/', $contactIdsString)) {
            throw new \InvalidArgumentException("La liste des identifiants des contacts n'a pas le bon format.");
        }

        $contactIds = explode(',', $contactIdsString);

        foreach ($contactIds as $contactId) {
            $contact = $this->utilisateurInformationsRepository->find($contactId);

            if (null === $contact) {
                throw new NotFoundHttpException();
            }

            $etablissement->addContact($contact);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Les contacts ont bien été ajoutés.');

        return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
    }

    #[Route(path: 'etablissement/{id}/contacts/{contact}/supprimer', name: 'etablissement_contacts_delete', methods: ['GET', 'DELETE'], options: ['expose' => true], defaults: ['_etablissement_lateral_nav' => false])]
    #[IsGranted('edit_ets', subject: 'etablissement')]
    public function deleteContact(
        Request $request,
        #[MapEntity(id: 'id')] Etablissement $etablissement,
        #[MapEntity(id: 'contact')] UtilisateurInformations $contact
    ): Response {
        $form = $this->createFormBuilder()
            ->setMethod(Request::METHOD_DELETE)
            ->setAction($this->generateUrl('etablissement_contacts_delete', [
                'id' => $etablissement->getId(),
                'contact' => $contact->getId(),
            ]))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$etablissement->getContacts()->contains($contact)) {
                $this->addFlash('warning', "Le contact n'appartient pas à l'établissement.");

                return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
            }

            $isStillUsed = $this->contactService->isUsed($contact, Contact::ETABLISSEMENT);

            $etablissement->removeContact($contact);

            if (!$isStillUsed) {
                $this->entityManager->remove($contact);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été supprimé.');

            return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/_form_delete_contact.html.twig', [
            'form' => $form,
            'etablissement' => $etablissement,
            'contact' => $contact,
        ]);
    }
}

<?php

namespace App\Controller\Dossier;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Dossier;
use App\Entity\UtilisateurInformations;
use App\Form\ContactType;
use App\Repository\DossierRepository;
use App\Repository\UtilisateurInformationsRepository;
use App\Security\Voter\DossierVoter;
use App\Service\Contact;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Gère les contacts d'un dossier.
 */
#[Route(requirements: ['id' => '\d+'])]
class DossierContactController extends AbstractController implements DossierLateralNavigationInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly DossierRepository $dossierRepository, private readonly UtilisateurInformationsRepository $utilisateurInformationsRepository, private readonly Contact $contactService)
    {
    }

    #[Route(path: '/dossier/{id}/contacts', name: 'dossier_contacts', methods: ['GET'])]
    #[IsGranted(DossierVoter::LECTURE, subject: 'dossier')]
    public function contacts(Dossier $dossier): Response
    {
        $idDossier = $dossier->getId();
        $contacts = new ArrayCollection();

        if (null !== $idDossier) {
            $contacts = $this->dossierRepository->findContactsOrderedByNom($idDossier)->getContacts();
        }

        return $this->render('dossier/contacts/contacts.html.twig', [
            'dossier' => $dossier,
            'contacts' => $contacts,
        ]);
    }

    #[Route(path: '/dossier/{id}/contacts/ajouter', name: 'dossier_contacts_add', methods: ['GET', 'POST'], defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted(DossierVoter::MODIFICATION, subject: 'dossier')]
    public function addContact(Request $request, Dossier $dossier): Response
    {
        $contact = new UtilisateurInformations();

        $form = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('dossier_contacts_add', ['id' => $dossier->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($contact);
            $dossier->addContact($contact);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été ajouté.');

            return $this->redirectToRoute('dossier_contacts', ['id' => $dossier->getId()]);
        }

        return $this->render('contact/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/dossier/{id}/contacts/ajouter/existant', name: 'dossier_contacts_add_existing', methods: ['POST'], defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted(DossierVoter::MODIFICATION, subject: 'dossier')]
    public function addExistingContact(Request $request, Dossier $dossier): Response
    {
        if (!is_string($request->request->get('token')) || !$this->isCsrfTokenValid('dossier_contacts_add_existing_'.$dossier->getId(), $request->request->get('token'))) {
            throw $this->createAccessDeniedException("Le jeton CSRF n'est pas valide.");
        }

        $contactIdsString = $request->request->get('contacts');

        if (!is_string($contactIdsString) || '' === $contactIdsString) {
            return $this->redirectToRoute('dossier_contacts', ['id' => $dossier->getId()]);
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

            $dossier->addContact($contact);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Les contacts ont bien été ajoutés.');

        return $this->redirectToRoute('dossier_contacts', ['id' => $dossier->getId()]);
    }

    #[Route(path: '/dossier/{id}/contacts/{contact}/supprimer', name: 'dossier_contacts_delete', methods: ['GET', 'DELETE'], options: ['expose' => true], defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted(DossierVoter::MODIFICATION, subject: 'dossier')]
    #[ParamConverter('dossier', options: ['id' => 'id'])]
    #[ParamConverter('contact', options: ['id' => 'contact'])]
    public function deleteContact(
        Request $request,
        Dossier $dossier,
        UtilisateurInformations $contact
    ): Response {
        if (!$dossier->getContacts()->contains($contact)) {
            $this->addFlash('warning', "Le contact n'appartient pas à ce dossier.");

            return $this->redirectToRoute('dossier_contacts', ['id' => $dossier->getId()]);
        }

        $form = $this->createFormBuilder()
            ->setMethod(Request::METHOD_DELETE)
            ->setAction($this->generateUrl('dossier_contacts_delete', [
                'id' => $dossier->getId(),
                'contact' => $contact->getId(),
            ]))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isStillUsed = $this->contactService->isUsed($contact, Contact::DOSSIER);

            $dossier->removeContact($contact);

            if (!$isStillUsed) {
                $this->entityManager->remove($contact);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été supprimé.');

            return $this->redirectToRoute('dossier_contacts', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/contacts/_form_delete_contact.html.twig', [
            'form' => $form,
            'dossier' => $dossier,
            'contact' => $contact,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Controller\Contract\EtablissementLateralNavigationInterface;
use App\Entity\Etablissement;
use App\Entity\UtilisateurInformations;
use App\Form\ContactType;
use App\Repository\EtablissementRepository;
use App\Repository\UtilisateurInformationsRepository;
use App\Service\Contact;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Gère les contacts d'un établissement.
 *
 * @Route(requirements={"id"="\d+"})
 */
class EtablissementContactController extends AbstractController implements EtablissementLateralNavigationInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var UtilisateurInformationsRepository */
    private $utilisateurInformationsRepository;

    /** @var Contact */
    private $contactService;

    public function __construct(
        EntityManagerInterface $entityManager,
        EtablissementRepository $etablissementRepository,
        UtilisateurInformationsRepository $utilisateurInformationsRepository,
        Contact $contactService
    ) {
        $this->entityManager = $entityManager;
        $this->etablissementRepository = $etablissementRepository;
        $this->utilisateurInformationsRepository = $utilisateurInformationsRepository;
        $this->contactService = $contactService;
    }

    /**
     * @Route("/etablissement/{id}/contacts", name="etablissement_contacts")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function contacts(Etablissement $etablissement): Response
    {
        $etablissementWithContacts = $this->etablissementRepository->findContactsOrderedByNom($etablissement->getId());

        $contacts = null !== $etablissementWithContacts
            ? $etablissementWithContacts->getSortedContacts()
            : null;

        $contactsParents = new ArrayCollection();

        foreach ($etablissement->getParents() as $parent) {
            $parentWithContacts = $this->etablissementRepository->findContactsOrderedByNom($parent->getId());

            if (null === $parentWithContacts) {
                continue;
            }

            foreach ($parentWithContacts->getSortedContacts() as $contactParent) {
                $contactsParents->add($contactParent);
            }
        }

        return $this->render('etablissement/contacts.html.twig', [
            'etablissement' => $etablissement,
            'contacts' => $contacts,
            'contacts_parents' => $contactsParents,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/contacts/ajouter", name="etablissement_contacts_add", defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
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
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/etablissement/{id}/contacts/ajouter/existant", name="etablissement_contacts_add_existing", defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function addExistingContact(
        Request $request,
        Etablissement $etablissement
    ): Response {
        $contactIdsString = $request->request->get('contacts');

        if (!\is_string($contactIdsString) || !preg_match('/^\d+(?:,\d+)*$/', $contactIdsString)) {
            throw new \InvalidArgumentException("La liste des identifiants des contacts n'a pas le bon format.");
        }

        if ('' === $contactIdsString) {
            return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
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

    /**
     * @Route("etablissement/{id}/contacts/{contact}/supprimer", name="etablissement_contacts_delete", methods={"GET", "DELETE"}, options={"expose"=true}, defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     *
     * @ParamConverter("etablissement", options={"id" = "id"})
     * @ParamConverter("contact", options={"id" = "contact"})
     */
    public function deleteContact(
        Request $request,
        Etablissement $etablissement,
        UtilisateurInformations $contact
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

            $etablissement->removeContact($contact);

            if (!$this->contactService->isUsed($contact, Contact::ETABLISSEMENT)) {
                $this->entityManager->remove($contact);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été supprimé.');

            return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/_form_delete_contact.html.twig', [
            'form' => $form->createView(),
            'etablissement' => $etablissement,
            'contact' => $contact,
        ]);
    }
}

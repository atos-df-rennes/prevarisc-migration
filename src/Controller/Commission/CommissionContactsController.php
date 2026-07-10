<?php

namespace App\Controller\Commission;

use App\Entity\Commission;
use App\Entity\UtilisateurInformations;
use App\Form\AddExistingContactsType;
use App\Form\ContactType;
use App\Service\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Gère l'onglet "Contacts" d'une commission.
 *
 * @IsGranted("gestion_commissions")
 */
class CommissionContactsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    private Contact $contactService;

    public function __construct(
        EntityManagerInterface $entityManager,
        Contact $contactService
    ) {
        $this->entityManager = $entityManager;
        $this->contactService = $contactService;
    }

    /**
     * Onglet Contacts.
     *
     * @Route("/admin/commissions/{id}/contacts", name="admin_commissions_contacts", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function contacts(Commission $commission): Response
    {
        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'contacts',
            'contacts' => $commission->getSortedContacts(),
        ]);
    }

    /**
     * @Route("/admin/commissions/{id}/contacts/ajouter", name="admin_commissions_contacts_add", requirements={"id"="\d+"})
     */
    public function addNewContact(Request $request, Commission $commission): Response
    {
        $contact = new UtilisateurInformations();

        $form = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('admin_commissions_contacts_add', ['id' => $commission->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($contact);
            $commission->addContact($contact);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été ajouté.');

            return $this->redirectToRoute('admin_commissions_contacts', ['id' => $commission->getId()]);
        }

        return $this->render('contact/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/commissions/{id}/contacts/ajouter/existant", name="admin_commissions_contacts_add_existing", requirements={"id"="\d+"})
     */
    public function addExistingContact(Request $request, Commission $commission): Response
    {
        $form = $this->createForm(AddExistingContactsType::class, null, [
            'action' => $this->generateUrl('admin_commissions_contacts_add_existing', ['id' => $commission->getId()]),
            'is_post' => $request->isMethod('POST'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<int, UtilisateurInformations> $contacts */
            $contacts = $form->get('contacts')->getData();
            foreach ($contacts as $contact) {
                $commission->addContact($contact);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Les contacts ont bien été ajoutés.');

            return $this->redirectToRoute('admin_commissions_contacts', ['id' => $commission->getId()]);
        }

        return $this->render('components/_form_contact_existant.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/commissions/{id}/contacts/{contact}/supprimer", name="admin_commissions_contacts_delete", requirements={"id"="\d+", "contact"="\d+"}, methods={"GET", "DELETE"}, options={"expose"=true})
     *
     * @ParamConverter("commission", options={"id" = "id"})
     * @ParamConverter("contact", options={"id" = "contact"})
     */
    public function deleteContact(
        Request $request,
        Commission $commission,
        UtilisateurInformations $contact
    ): Response {
        $form = $this->createFormBuilder()
            ->setMethod(Request::METHOD_DELETE)
            ->setAction($this->generateUrl('admin_commissions_contacts_delete', [
                'id' => $commission->getId(),
                'contact' => $contact->getId(),
            ]))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$commission->getContacts()->contains($contact)) {
                $this->addFlash('warning', "Le contact n'appartient pas à la commission.");

                return $this->redirectToRoute('admin_commissions_contacts', ['id' => $commission->getId()]);
            }

            $isStillUsed = $this->contactService->isUsed($contact, Contact::COMMISSION);

            $commission->removeContact($contact);

            if (!$isStillUsed) {
                $this->entityManager->remove($contact);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été supprimé.');

            return $this->redirectToRoute('admin_commissions_contacts', ['id' => $commission->getId()]);
        }

        return $this->render('components/_form_delete_contact.html.twig', [
            'form' => $form->createView(),
            'contact' => $contact,
            'entity_article' => 'de la commission',
            'entity_libelle' => $commission->getLibelle(),
        ]);
    }
}

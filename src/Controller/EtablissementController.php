<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Entity\UtilisateurInformations;
use App\Form\ContactType;
use App\Form\TextesApplicablesEtablissementType;
use App\Repository\EtablissementRepository;
use App\Repository\UtilisateurInformationsRepository;
use App\Service\Contact;
use App\Service\Etablissement as EtablissementService;
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
 * @IsGranted("view_ets", subject="etablissement")
 */
class EtablissementController extends AbstractController
{
    /**
     * @Route("/etablissement/{id}/historique", name="etablissement_historique")
     */
    public function historique(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        $historique = $etablissementService->getHistorique($etablissement->getId());

        return $this->render('etablissement/historique.html.twig', [
            'etablissement' => $etablissement,
            'historique' => $historique,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/textes-applicables", name="etablissement_textes_applicables")
     */
    public function textesApplicables(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        $etablissementData = $etablissementService->getTextesApplicables($etablissement->getId());

        return $this->render('etablissement/textes_applicables.html.twig', [
            'etablissement' => $etablissement,
            'textes_applicables' => $etablissementData,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/textes-applicables/modifier", name="etablissement_textes_applicables_edit")
     */
    public function editTextesApplicables(Request $request, Etablissement $etablissement, EtablissementRepository $etablissementRepository, EntityManagerInterface $entityManager): Response
    {
        $textesApplicablesEtablissementData = $etablissementRepository->getTextesApplicablesByType($etablissement->getId());
        $form = $this->createForm(TextesApplicablesEtablissementType::class, $textesApplicablesEtablissementData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Les textes applicables ont bien été mis à jour.');

            return $this->redirectToRoute('etablissement_textes_applicables', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/edit_textes_applicables.html.twig', [
            'form' => $form->createView(),
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/avis-derogations", name="etablissement_avis_derogations")
     */
    public function avisDerogations(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        $historiqueAvisDerogations = $etablissementService->getAvisDerogations($etablissement->getId());
        $derogationsOriginales = $etablissement->getDescriptifDerogation();

        return $this->render('etablissement/avis_derogations.html.twig', [
            'etablissement' => $etablissement,
            'historiqueAvisDerogations' => $historiqueAvisDerogations,
            'derogationsOriginales' => $derogationsOriginales,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/pieces-jointes", name="etablissement_pieces_jointes")
     */
    public function piecesJointes(Etablissement $etablissement, EtablissementService $etablissementService): Response
    {
        $image_pjs = $etablissementService->getPJImages($etablissement->getId());
        $doc_pjs = $etablissementService->getAutresPJs($etablissement->getId());

        return $this->render('etablissement/pieces_jointes.html.twig', [
            'image_pjs' => $image_pjs,
            'doc_pjs' => $doc_pjs,
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/contacts", name="etablissement_contacts")
     */
    public function contacts(Etablissement $etablissement, EtablissementRepository $etablissementRepository): Response
    {
        $contacts = $etablissementRepository->findContactsOrderedByNom($etablissement->getId());

        if (null !== $contacts) {
            $contacts = $contacts->getContacts();
        }

        $contactsParents = new ArrayCollection();

        $etablissementsParents = $etablissement->getParents();

        foreach ($etablissementsParents as $parent) {
            $contactsParent = $etablissementRepository->findContactsOrderedByNom($parent->getId());

            if (null === $contactsParent) {
                continue;
            }

            $contactsParent = $contactsParent->getContacts();

            foreach ($contactsParent as $contactParent) {
                $contactsParents->add($contactParent);
            }
        }

        return $this->render('etablissement/contacts.html.twig', [
            'etablissement' => $etablissement,
            'contacts' => $contacts,
            'contacts_parents' => $contactsParents
        ]);
    }

    /**
     * @Route("/etablissement/{id}/contacts/ajouter", name="etablissement_contacts_add")
     */
    public function addContact(Request $request, Etablissement $etablissement, EntityManagerInterface $entityManager): Response
    {
        $contact = new UtilisateurInformations();

        $form = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('etablissement_contacts_add', ['id' => $etablissement->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contact);
            $etablissement->addContact($contact);
            $entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été ajouté.');

            return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
        }

        return $this->render('contact/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/etablissement/{id}/contacts/ajouter/existant", name="etablissement_contacts_add_existing")
     */
    public function addExistingContact(
        Request $request,
        Etablissement $etablissement,
        EntityManagerInterface $entityManager,
        UtilisateurInformationsRepository $utilisateurInformationsRepository
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
            $contact = $utilisateurInformationsRepository->find($contactId);

            if (null === $contact) {
                throw new NotFoundHttpException();
            }

            $etablissement->addContact($contact);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Les contacts ont bien été ajoutés.');

        return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
    }

    /**
     * @Route("etablissement/{id}/contacts/{contact}/supprimer", name="etablissement_contacts_delete", methods={"DELETE"})
     *
     * @ParamConverter("etablissement", options={"id" = "id"})
     * @ParamConverter("contact", options={"id" = "contact"})
     */
    public function deleteContact(
        Etablissement $etablissement,
        UtilisateurInformations $contact,
        EntityManagerInterface $entityManager,
        Contact $contactService
    ): Response {
        // Suppression de l'association.
        $etablissement->removeContact($contact);

        // Suppression définitive du contact
        if (!$contactService->isUsed($contact, Contact::ETABLISSEMENT)) {
            $entityManager->remove($contact);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le contact a bien été supprimé.');

        return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
    }
}

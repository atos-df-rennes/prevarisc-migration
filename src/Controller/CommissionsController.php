<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\UtilisateurInformations;
use App\Form\AddExistingContactsType;
use App\Form\CommissionsCollectionType;
use App\Form\ContactType;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
use App\Service\Commission\CommissionManagerService;
use App\Service\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("gestion_commissions")
 */
class CommissionsController extends AbstractController
{
    /** @var CommissionRepository */
    private $commissionRepository;

    /** @var CommissionTypeRepository */
    private $commissionTypeRepository;

    /** @var CommissionManagerService */
    private $commissionManagerService;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Contact */
    private $contactService;

    public function __construct(
        CommissionRepository $commissionRepository,
        CommissionTypeRepository $commissionTypeRepository,
        CommissionManagerService $commissionManagerService,
        EntityManagerInterface $entityManager,
        Contact $contactService
    ) {
        $this->commissionRepository = $commissionRepository;
        $this->commissionTypeRepository = $commissionTypeRepository;
        $this->commissionManagerService = $commissionManagerService;
        $this->entityManager = $entityManager;
        $this->contactService = $contactService;
    }

    /**
     * @Route("/admin/commissions", name="admin_commissions_index")
     */
    public function index(): Response
    {
        $commissionTypes = $this->commissionTypeRepository->findWithCommissions();

        return $this->render('commissions/index.html.twig', [
            'commission_types' => $commissionTypes,
        ]);
    }

    /**
     * @Route("/admin/commissions/gerer", name="admin_commissions_manage")
     */
    public function manage(Request $request): Response
    {
        $commissions = $this->commissionRepository->findAll();

        $form = $this->createForm(CommissionsCollectionType::class, [
            'commissions' => $commissions,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            /** @var Commission[] $submittedCommissions */
            $submittedCommissions = $data['commissions'] ?? [];

            try {
                $this->commissionManagerService->saveCommissions($commissions, $submittedCommissions);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la sauvegarde : '.$e->getMessage());

                return $this->redirectToRoute('admin_commissions_manage');
            }

            $this->addFlash('success', 'Les commissions ont été sauvegardées avec succès.');

            return $this->redirectToRoute('admin_commissions_index');
        }

        return $this->render('commissions/manage.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Redirige vers l'onglet compétences (point d'entrée générique par commission).
     *
     * @Route("/admin/commissions/{id}", name="admin_commissions_show", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function show(Commission $commission): Response
    {
        return $this->redirectToRoute('admin_commissions_competences', ['id' => $commission->getId()]);
    }

    /**
     * Onglet Membres (placeholder démo).
     *
     * @Route("/admin/commissions/{id}/membres", name="admin_commissions_membres", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function membres(Commission $commission): Response
    {
        return $this->render('commissions/edit.html.twig', [
            'commission' => $commission,
            'active_tab' => 'membres',
        ]);
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
            'contacts' => $commission->getContacts(),
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
            $commission->removeContact($contact);

            if (!$this->contactService->isUsed($contact, Contact::COMMISSION)) {
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

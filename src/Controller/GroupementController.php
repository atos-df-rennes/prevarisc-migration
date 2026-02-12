<?php

namespace App\Controller;

use App\Entity\Groupement;
use App\Entity\UtilisateurInformations;
use App\Form\AddExistingContactsType;
use App\Form\ContactType;
use App\Form\GroupementType as GroupementFormType;
use App\Repository\CommuneRepository;
use App\Repository\GroupementRepository;
use App\Repository\GroupementTypeRepository;
use App\Service\Contact;
use App\Service\GroupementCommunes;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(requirements={"id"="\d+"})
 *
 * @IsGranted("admin")
 */
class GroupementController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CommuneRepository */
    private $communeRepository;

    /** @var GroupementRepository */
    private $groupementRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommuneRepository $communeRepository,
        GroupementRepository $groupementRepository
    ) {
        $this->entityManager = $entityManager;
        $this->communeRepository = $communeRepository;
        $this->groupementRepository = $groupementRepository;
    }

    /**
     * @Route("/admin/groupement/", name="admin_groupement")
     */
    public function index(Request $request, GroupementTypeRepository $groupementTypeRepository): Response
    {
        $groupementTypes = $groupementTypeRepository->findAll();
        $communes = $this->communeRepository->findAll();

        // Récupérer l'ID du groupement à présélectionner (peut être string ou int)
        $selectedGroupementId = $request->query->get('id');
        if (is_numeric($selectedGroupementId)) {
            $selectedGroupementId = (int) $selectedGroupementId;
        }

        return $this->render('groupement/index.html.twig', [
            'title' => 'Groupements de communes',
            'admin_groupement_types' => $groupementTypes,
            'communes' => $communes,
            'selected_groupement_id' => $selectedGroupementId,
        ]);
    }

    /**
     * @Route("/admin/groupement/creation", name="admin_groupement_creation")
     */
    public function create(Request $request): Response
    {
        $groupement = new Groupement();

        $form = $this->createForm(GroupementFormType::class, $groupement, [
            'action' => $this->generateUrl('admin_groupement_creation'),
            'scopes' => ['create']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($groupement);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le groupement '.$groupement->getLibelle().' a été créé.');

            return $this->redirectToRoute('admin_groupement');
        }

        return $this->render('groupement/_form_ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/groupement/{id}/form/{scope}", name="admin_groupement_form_scope", requirements={"scope"="general|affectations|coordonnees"})
     */
    public function formByScope(Groupement $groupement, string $scope, Request $request, GroupementCommunes $groupementCommunes): Response
    {
        $form = $this->createForm(GroupementFormType::class, $groupement, [
            'scopes' => [$scope]
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            if ($form->has('reaffectationAutomatique')) {
                $reaffectationAutomatique = $form->get('reaffectationAutomatique')->getData();
                if ('1' === $reaffectationAutomatique) {
                    $groupementCommunes->reaffectationPreventioniste($groupement);
                }
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Le groupement a été mis à jour.'
                ]);
            }

            $this->addFlash('success', 'Le groupement a été mis à jour.');

            return $this->redirectToRoute('admin_groupement');
        }

        $templateData = [
            'form' => $form->createView(),
            'groupement' => $groupement,
        ];

        if ('general' === $scope) {
            $templateData['villes'] = $this->communeRepository->findAll();
        }

        return $this->render(sprintf('groupement/_form_%s.html.twig', $scope), $templateData);
    }

    /**
     * @Route("/admin/groupement/{id}/delete", name="admin_groupement_delete", methods={"GET", "POST"})
     */
    public function delete(Request $request, Groupement $groupement): Response
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_groupement_delete', ['id' => $groupement->getId()]))
            ->setMethod(Request::METHOD_POST)
            ->add('submit', SubmitType::class, [
                'label' => 'Confirmer la suppression',
                'attr' => ['class' => 'btn btn-danger']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->remove($groupement);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le groupement a été supprimé.');
            } catch (\Exception $exception) {
                $this->addFlash('error', $exception->getMessage());
            }

            return $this->redirectToRoute('admin_groupement');
        }

        return $this->render('groupement/_form_delete.html.twig', [
            'form' => $form->createView(),
            'groupement' => $groupement,
        ]);
    }

    /**
     * @Route("/admin/groupement/{id}/contacts", name="admin_groupement_contacts")
     */
    public function contacts(Groupement $groupement): Response
    {
        $contacts = $this->groupementRepository->find($groupement->getId());

        if (null !== $contacts) {
            $contacts = $contacts->getContacts();
        }

        return $this->render('groupement/_contacts.html.twig', [
            'groupement' => $groupement,
            'contacts' => $contacts,
        ]);
    }

    /**
     * @Route("/admin/groupement/{id}/contacts/ajouter", name="admin_groupement_contacts_add")
     */
    public function addNewContact(Request $request, Groupement $groupement): Response
    {
        $contact = new UtilisateurInformations();

        $form = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('admin_groupement_contacts_add', ['id' => $groupement->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($contact);
            $groupement->addContact($contact);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été ajouté.');

            return $this->redirectToRoute('admin_groupement', ['id' => $groupement->getId()]);
        }

        return $this->render('contact/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/groupement/{id}/contacts/ajouter/existant", name="admin_groupement_contacts_add_existing")
     */
    public function addExistingContact(
        Request $request,
        Groupement $groupement
    ): Response {
        $form = $this->createForm(AddExistingContactsType::class, null, [
            'action' => $this->generateUrl('admin_groupement_contacts_add_existing', ['id' => $groupement->getId()]),
            'is_post' => $request->isMethod('POST'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<int, UtilisateurInformations> $contacts */
            $contacts = $form->get('contacts')->getData();
            foreach ($contacts as $contact) {
                $groupement->addContact($contact);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Les contacts ont bien été ajoutés.');

            return $this->redirectToRoute('admin_groupement', ['id' => $groupement->getId()]);
        }

        return $this->render('groupement/_form_existing_contacts.html.twig', [
            'form' => $form->createView(),
            'groupement' => $groupement,
        ]);
    }

    /**
     * @Route("/admin/groupement/{id}/contacts/{contact}/supprimer", name="admin_groupement_contacts_delete", methods={"GET", "DELETE"})
     *
     * @ParamConverter("groupement", options={"id" = "id"})
     * @ParamConverter("contact", options={"id" = "contact"})
     */
    public function deleteContact(
        Request $request,
        Groupement $groupement,
        UtilisateurInformations $contact,
        Contact $contactService
    ): Response {
        $form = $this->createFormBuilder()
            ->setMethod(Request::METHOD_DELETE)
            ->setAction($this->generateUrl('admin_groupement_contacts_delete', [
                'id' => $groupement->getId(),
                'contact' => $contact->getId()
            ]))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupement->removeContact($contact);

            if (!$contactService->isUsed($contact, Contact::GROUPEMENT)) {
                $this->entityManager->remove($contact);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Le contact a bien été supprimé.');

            return $this->redirectToRoute('admin_groupement', ['id' => $groupement->getId()]);
        }

        return $this->render('groupement/_form_delete_contact.html.twig', [
            'form' => $form->createView(),
            'groupement' => $groupement,
            'contact' => $contact,
        ]);
    }
}

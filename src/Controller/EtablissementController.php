<?php

namespace App\Controller;

use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\UtilisateurInformations;
use App\Form\ContactType;
use App\Form\TextesApplicablesEtablissementType;
use App\Repository\CoucheCartoRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\EtablissementRepository;
use App\Repository\GroupementRepository;
use App\Repository\PeriodiciteRepository;
use App\Repository\UtilisateurInformationsRepository;
use App\Service\Contact;
use App\Service\DataStore\DataStore;
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
use Symfony\Component\Serializer\SerializerInterface;

class EtablissementController extends AbstractController
{
    /** @var EtablissementPieceJointeRepository */
    private $etablissementPieceJointeRepository;

    /** @var EtablissementAdresseRepository */
    private $etablissementAdresseRepository;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var EtablissementService */
    private $etablissementService;

    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        EtablissementPieceJointeRepository $etablissementPieceJointeRepository,
        EtablissementAdresseRepository $etablissementAdresseRepository,
        DossierRepository $dossierRepository,
        EtablissementService $etablissementService,
        EtablissementRepository $etablissementRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->etablissementPieceJointeRepository = $etablissementPieceJointeRepository;
        $this->etablissementAdresseRepository = $etablissementAdresseRepository;
        $this->dossierRepository = $dossierRepository;
        $this->etablissementService = $etablissementService;
        $this->etablissementRepository = $etablissementRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/etablissement/{id}/informations", name="etablissement_informations")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function informations(
        Etablissement $etablissement,
        CoucheCartoRepository $coucheCartoRepository,
        PeriodiciteRepository $periodiciteRepository,
        GroupementRepository $groupementRepository,
        DataStore $dataStore,
        SerializerInterface $serializer
    ): Response {
        // Récupération des couches cartographiques
        $couchesCartographiques = $coucheCartoRepository->findAll();
        $couchesCartographiquesJson = $serializer->serialize($couchesCartographiques, 'json');

        // Récupération de la périodicité par défaut
        $defaultPeriodicite = $periodiciteRepository->gn4ForEtablissement($etablissement);

        // Récupération de la périodicité affichée
        $periodicity = $this->etablissementService->getDisplayedPeriodicity($etablissement);

        // Récupération des adresses de l'établissement
        $adresses = $this->etablissementAdresseRepository->get($etablissement->getId());

        // Récupération des groupements de communes liés à la commune de l'établissement
        $groupementsDeCommunes = [];
        if (0 !== count($adresses)) {
            $firstAddress = current($adresses);
            if ($firstAddress instanceof EtablissementAdresse && $firstAddress->getCommune() instanceof Commune) {
                $groupementsDeCommunes = $groupementRepository->findByNumInsee(
                    $firstAddress->getCommune()->getId()
                );
            }
        }

        // Récupération des pièces jointes de type plan et diaporama
        $diapoPlans = $this->etablissementPieceJointeRepository->getPlans($etablissement);
        $diapo = $this->etablissementPieceJointeRepository->getDiaporama($etablissement);

        // Récupération des dates de visite
        $datesVisites = $this->etablissementService->getDatesVisites($etablissement);

        return $this->render('etablissement/informations.html.twig', [
            'etablissement' => $etablissement,
            'couches_cartographiques' => $couchesCartographiquesJson,
            'adresses' => $adresses,
            'default_periodicite' => $defaultPeriodicite,
            'periodicity' => $periodicity,
            'groupements_de_communes' => $groupementsDeCommunes,
            'store' => $dataStore,
            'diapo_plans' => $diapoPlans,
            'diapo' => $diapo,
            'last_visite' => $datesVisites['lastVisite'],
            'next_visite' => $datesVisites['nextVisite'],
            'pc_initial' => $this->dossierRepository->getPCInitial($etablissement),
            'presence_dus' => $this->etablissementService->hasContactDus($etablissement),
            'donnees_pratiques' => $this->etablissementService->calculateDonneesPratiques($etablissement)
            // TODO: gestion role :  'is_allowed_delete_etablissement' => $isAllowedDeleteEtablissement,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/historique", name="etablissement_historique")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function historique(Etablissement $etablissement): Response
    {
        $historique = $this->etablissementService->getHistorique($etablissement->getId());

        return $this->render('etablissement/historique.html.twig', [
            'etablissement' => $etablissement,
            'historique' => $historique,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/textes-applicables", name="etablissement_textes_applicables")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function textesApplicables(Etablissement $etablissement): Response
    {
        $etablissementData = $this->etablissementService->getTextesApplicables($etablissement->getId());

        return $this->render('etablissement/textes_applicables.html.twig', [
            'etablissement' => $etablissement,
            'textes_applicables' => $etablissementData,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/textes-applicables/modifier", name="etablissement_textes_applicables_edit")
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function editTextesApplicables(Request $request, Etablissement $etablissement): Response
    {
        $textesApplicablesEtablissementData = $this->etablissementRepository->getTextesApplicablesByType($etablissement->getId());
        $form = $this->createForm(TextesApplicablesEtablissementType::class, $textesApplicablesEtablissementData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

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
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function avisDerogations(Etablissement $etablissement): Response
    {
        $historiqueAvisDerogations = $this->etablissementService->getAvisDerogations($etablissement->getId());
        $derogationsOriginales = $etablissement->getDescriptifDerogation();

        return $this->render('etablissement/avis_derogations.html.twig', [
            'etablissement' => $etablissement,
            'historiqueAvisDerogations' => $historiqueAvisDerogations,
            'derogationsOriginales' => $derogationsOriginales,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/pieces-jointes", name="etablissement_pieces_jointes")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function piecesJointes(Etablissement $etablissement): Response
    {
        $image_pjs = $this->etablissementService->getPJImages($etablissement->getId());
        $doc_pjs = $this->etablissementService->getAutresPJs($etablissement->getId());

        return $this->render('etablissement/pieces_jointes.html.twig', [
            'image_pjs' => $image_pjs,
            'doc_pjs' => $doc_pjs,
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * @Route("/etablissement/{id}/contacts", name="etablissement_contacts")
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function contacts(Etablissement $etablissement): Response
    {
        $contacts = $this->etablissementRepository->findContactsOrderedByNom($etablissement->getId());

        if (null !== $contacts) {
            $contacts = $contacts->getContacts();
        }

        $contactsParents = new ArrayCollection();

        $etablissementsParents = $etablissement->getParents();

        foreach ($etablissementsParents as $parent) {
            $contactsParent = $this->etablissementRepository->findContactsOrderedByNom($parent->getId());

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
     * @Route("/etablissement/{id}/contacts/ajouter/existant", name="etablissement_contacts_add_existing")
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function addExistingContact(
        Request $request,
        Etablissement $etablissement,
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

        $this->entityManager->flush();

        $this->addFlash('success', 'Les contacts ont bien été ajoutés.');

        return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
    }

    /**
     * @Route("etablissement/{id}/contacts/{contact}/supprimer", name="etablissement_contacts_delete", methods={"DELETE"})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     *
     * @ParamConverter("etablissement", options={"id" = "id"})
     * @ParamConverter("contact", options={"id" = "contact"})
     */
    public function deleteContact(
        Etablissement $etablissement,
        UtilisateurInformations $contact,
        Contact $contactService
    ): Response {
        // Suppression de l'association.
        $etablissement->removeContact($contact);

        // Suppression définitive du contact
        if (!$contactService->isUsed($contact, Contact::ETABLISSEMENT)) {
            $this->entityManager->remove($contact);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Le contact a bien été supprimé.');

        return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
    }
}

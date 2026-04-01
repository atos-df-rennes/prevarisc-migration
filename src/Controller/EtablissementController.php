<?php

namespace App\Controller;

use App\Constant\DossierType;
use App\Constant\NomInterneCapsule;
use App\Controller\Contract\EtablissementLateralNavigationInterface;
use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\UtilisateurInformations;
use App\Form\ContactType;
use App\Form\DescriptifOriginalType;
use App\Form\TextesApplicablesEtablissementType;
use App\Repository\CoucheCartoRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\EtablissementRepository;
use App\Repository\GroupementRepository;
use App\Repository\PeriodiciteRepository;
use App\Repository\UtilisateurInformationsRepository;
use App\Service\Alerte\AlerteService;
use App\Service\Contact;
use App\Service\DataStore\DataStore;
use App\Service\Descriptif as DescriptifService;
use App\Service\Dossier;
use App\Service\Etablissement as EtablissementService;
use App\Service\Mail\MailerConfiguration;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class EtablissementController extends AbstractController implements EtablissementLateralNavigationInterface
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

    /**
     * @var CoucheCartoRepository
     */
    private $coucheCartoRepository;

    /**
     * @var PeriodiciteRepository
     */
    private $periodiciteRepository;

    /**
     * @var GroupementRepository
     */
    private $groupementRepository;

    /**
     * @var DataStore
     */
    private $dataStore;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var UtilisateurInformationsRepository
     */
    private $utilisateurInformationsRepository;

    /**
     * @var Contact
     */
    private $contactService;

    /** @var Dossier */
    private $dossierService;

    /**
     * @var MailerConfiguration
     */
    private $mailerConfig;

    /**
     * @var AlerteService
     */
    private $alerteService;

    /** @var int */
    private $nbDossiersAAfficher;

    /** @var DescriptifService */
    private $descriptifService;

    /** @var bool */
    private $descriptifPersonnalise;

    public function __construct(
        EtablissementPieceJointeRepository $etablissementPieceJointeRepository,
        EtablissementAdresseRepository $etablissementAdresseRepository,
        DossierRepository $dossierRepository,
        EtablissementService $etablissementService,
        EtablissementRepository $etablissementRepository,
        EntityManagerInterface $entityManager,
        Dossier $dossierService,
        CoucheCartoRepository $coucheCartoRepository,
        PeriodiciteRepository $periodiciteRepository,
        GroupementRepository $groupementRepository,
        DataStore $dataStore,
        SerializerInterface $serializer,
        UtilisateurInformationsRepository $utilisateurInformationsRepository,
        Contact $contactService,
        MailerConfiguration $mailerConfig,
        AlerteService $alerteService,
        int $nbDossiersAAfficher,
        DescriptifService $descriptifService,
        bool $descriptifPersonnalise
    ) {
        $this->etablissementPieceJointeRepository = $etablissementPieceJointeRepository;
        $this->etablissementAdresseRepository = $etablissementAdresseRepository;
        $this->dossierRepository = $dossierRepository;
        $this->etablissementService = $etablissementService;
        $this->etablissementRepository = $etablissementRepository;
        $this->entityManager = $entityManager;
        $this->dossierService = $dossierService;
        $this->coucheCartoRepository = $coucheCartoRepository;
        $this->periodiciteRepository = $periodiciteRepository;
        $this->groupementRepository = $groupementRepository;
        $this->dataStore = $dataStore;
        $this->serializer = $serializer;
        $this->utilisateurInformationsRepository = $utilisateurInformationsRepository;
        $this->contactService = $contactService;
        $this->mailerConfig = $mailerConfig;
        $this->alerteService = $alerteService;
        $this->nbDossiersAAfficher = $nbDossiersAAfficher;
        $this->descriptifService = $descriptifService;
        $this->descriptifPersonnalise = $descriptifPersonnalise;
    }

    /**
     * @Route("/etablissement/{id}/informations", name="etablissement_informations", options={"expose"=true})
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function informations(
        Etablissement $etablissement
    ): Response {
        // Récupération des couches cartographiques
        $couchesCartographiques = $this->coucheCartoRepository->findAll();
        $couchesCartographiquesJson = $this->serializer->serialize($couchesCartographiques, 'json');

        // Récupération de la périodicité par défaut
        $defaultPeriodicite = $this->periodiciteRepository->gn4ForEtablissement($etablissement);

        // Récupération de la périodicité affichée
        $periodicity = $this->etablissementService->getDisplayedPeriodicity($etablissement);

        // Récupération des adresses de l'établissement
        $adresses = $this->etablissementAdresseRepository->get($etablissement->getId());

        // Récupération des groupements de communes liés à la commune de l'établissement
        $groupementsDeCommunes = [];
        if (0 !== count($adresses)) {
            $firstAddress = current($adresses);
            if ($firstAddress instanceof EtablissementAdresse && $firstAddress->getCommune() instanceof Commune) {
                $groupementsDeCommunes = $this->groupementRepository->findByNumInsee(
                    $firstAddress->getCommune()->getId()
                );
            }
        }

        // Récupération des pièces jointes de type plan et diaporama
        $diapoPlans = $this->etablissementPieceJointeRepository->getPlans($etablissement);
        $diapo = $this->etablissementPieceJointeRepository->getDiaporama($etablissement);

        // Récupération des dates de visite
        $datesVisites = $this->etablissementService->getDatesVisites($etablissement);

        // Récupération des fils triés par libellé de leur dernière information
        $filsOrdonnes = $this->etablissementRepository->findFilsOrderedByLibelle($etablissement);

        return $this->render('etablissement/informations.html.twig', [
            'etablissement' => $etablissement,
            'couches_cartographiques' => $couchesCartographiquesJson,
            'adresses' => $adresses,
            'default_periodicite' => $defaultPeriodicite,
            'periodicity' => $periodicity,
            'groupements_de_communes' => $groupementsDeCommunes,
            'store' => $this->dataStore,
            'diapo_plans' => $diapoPlans,
            'diapo' => $diapo,
            'last_visite' => $datesVisites['lastVisite'],
            'next_visite' => $datesVisites['nextVisite'],
            'pc_initial' => $this->dossierRepository->getPCInitial($etablissement),
            'presence_dus' => $this->etablissementService->hasContactDus($etablissement),
            'donnees_pratiques' => $this->etablissementService->calculateDonneesPratiques($etablissement),
            'fils_ordonnes' => $filsOrdonnes,
        ]);
    }

    /**
     * Point d'entrée Symfony après modification d'un établissement depuis le legacy.
     *
     * Reçoit le type d'alerte déterminé par le legacy (checkAlerte), génère le flash
     * Symfony avec le lien "Alerter" si les conditions sont réunies, puis redirige
     * vers la page d'informations de l'établissement.
     *
     * Équivalent legacy : fin de EtablissementController::editAction() (flash + redirect)
     *
     * @Route("/symfony/etablissement/{id}/apres-modification", name="etablissement_apres_modification", methods={"GET"}, defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("view_ets", subject="etablissement")
     *
     * @param int           $id            Identifiant de l'établissement
     * @param Request       $request       Requête HTTP (query param type_alerte)
     * @param Etablissement $etablissement Établissement modifié
     */
    public function apresModification(
        int $id,
        Request $request,
        Etablissement $etablissement
    ): Response {
        $typeAlerte = $request->query->getInt('type_alerte', 0);
        $lienAlerte = '';

        if ($typeAlerte > 0 && $this->mailerConfig->isEnabled()) {
            $privilege = AlerteService::PRIVILEGES_CHANGEMENT[$typeAlerte] ?? null;

            if (null !== $privilege && $this->isGranted($privilege)) {
                $lienAlerte = $this->alerteService->getLink($typeAlerte, $id);
            }
        }

        $this->addFlash('success', 'L\'établissement a bien été mis à jour.'.$lienAlerte);

        return $this->redirectToRoute('etablissement_informations', ['id' => $id]);
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
        $this->etablissementRepository->getTextesApplicablesByType($etablissement->getId());
        $form = $this->createForm(TextesApplicablesEtablissementType::class, $etablissement);

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
        $historiqueAvisDerogations = $this->etablissementService->getAvisDerogations($etablissement);
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
            'contacts_parents' => $contactsParents
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
                'contact' => $contact->getId()
            ]))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$etablissement->getContacts()->contains($contact)) {
                $this->addFlash('warning', "Le contact n'appartient pas à l'établissement.");

                return $this->redirectToRoute('etablissement_contacts', ['id' => $etablissement->getId()]);
            }

            // Suppression de l'association.
            $etablissement->removeContact($contact);

            // Suppression définitive du contact
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

    /**
     * @Route("/etablissement/{id}/dossiers", name="etablissement_dossiers", methods={"GET"})
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function getDossiers(
        Etablissement $etablissement
    ): Response {
        $id = $etablissement->getId();

        // Récupération des dossiers par type (limités à nbDossiersAAfficher en SQL)
        $etudes = $this->dossierRepository->getDossiersEtablissement($id, [DossierType::ETUDE], false, $this->nbDossiersAAfficher);
        $visites = $this->dossierRepository->getDossiersEtablissement($id, DossierType::TYPES_VISITE, false, $this->nbDossiersAAfficher);
        $autres = $this->dossierRepository->getDossiersEtablissement($id, DossierType::TYPES_PRINCIPAUX, true, $this->nbDossiersAAfficher);

        // Comptage total par type
        $nbEtudes = $this->dossierRepository->countDossiersEtablissement($id, [DossierType::ETUDE]);
        $nbVisites = $this->dossierRepository->countDossiersEtablissement($id, DossierType::TYPES_VISITE);
        $nbAutres = $this->dossierRepository->countDossiersEtablissement($id, DossierType::TYPES_PRINCIPAUX, true);

        return $this->render('etablissement/dossiers.html.twig', [
            'etablissement' => $etablissement,
            'etudes' => $this->dossierService->extraireDonneesUtiles($etudes),
            'visites' => $this->dossierService->extraireDonneesUtiles($visites),
            'autres' => $this->dossierService->extraireDonneesUtiles($autres),
            'nbEtudes' => $nbEtudes,
            'nbVisites' => $nbVisites,
            'nbAutres' => $nbAutres,
            'nbElemMax' => $this->nbDossiersAAfficher,
        ]);
    }

    /**
     * @Route("/etablissement/get-dossiers-after-n", name="etablissement_get_dossiers_after_n", methods={"POST"}, defaults={"_etablissement_lateral_nav": false})
     */
    public function getDossiersAfterN(Request $request): Response
    {
        $id = $request->request->getInt('id');
        if (0 === $id) {
            return new Response('');
        }

        $typeDossier = $request->request->get('typeDossier');

        // Récupération des dossiers selon le type, en démarrant après les N premiers (offset SQL)
        switch ($typeDossier) {
            case 'etudes':
                $dossiers = $this->dossierRepository->getDossiersEtablissement($id, [DossierType::ETUDE], false, null, $this->nbDossiersAAfficher);
                break;
            case 'visites':
                $dossiers = $this->dossierRepository->getDossiersEtablissement($id, DossierType::TYPES_VISITE, false, null, $this->nbDossiersAAfficher);
                break;
            case 'autres':
                $dossiers = $this->dossierRepository->getDossiersEtablissement($id, DossierType::TYPES_PRINCIPAUX, true, null, $this->nbDossiersAAfficher);
                break;
            default:
                throw new \InvalidArgumentException('Type de dossier invalide.');
        }

        if ([] === $dossiers) {
            return new Response('');
        }

        // Génération du HTML
        $html = '';
        foreach ($this->dossierService->extraireDonneesUtiles($dossiers) as $dossier) {
            $html .= $this->renderView('etablissement/dossiers/_item.html.twig', ['dossier' => $dossier]);
        }

        return new Response($html);
    }

    /**
     * Affiche l'onglet Descriptif de l'établissement.
     * Si PREVARISC_DESCRIPTIF_PERSONNALISE est activé, affiche les formulaires personnalisés.
     * Sinon, affiche le descriptif original (champs fixes).
     *
     * @Route("/etablissement/{id}/descriptif", name="etablissement_descriptif", methods={"GET"})
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function descriptif(Etablissement $etablissement): Response
    {
        if (!$this->descriptifPersonnalise) {
            $data = $this->etablissementService->getDescriptifOriginal($etablissement);

            return $this->render('etablissement/descriptif_original.html.twig', [
                'etablissement' => $etablissement,
                'descriptif' => $data['descriptif'],
                'historique' => $data['historique'],
                'derogation' => $data['derogation'],
                'techniquesGroupes' => $data['techniquesGroupes'],
            ]);
        }

        $data = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT);

        return $this->render('etablissement/_informations/descriptif_personnalise.html.twig', [
            'etablissement' => $etablissement,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
        ]);
    }

    /**
     * Retourne le contenu du descriptif original pour la modale (sans layout).
     *
     * @Route("/etablissement/{id}/descriptif-original", name="etablissement_descriptif_original", methods={"GET"}, defaults={"_etablissement_lateral_nav": false})
     *
     * @IsGranted("view_ets", subject="etablissement")
     */
    public function descriptifOriginal(Etablissement $etablissement, Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $data = $this->etablissementService->getDescriptifOriginal($etablissement);

        return $this->render('etablissement/_descriptif_original_content.html.twig', [
            'descriptif' => $data['descriptif'],
            'historique' => $data['historique'],
            'derogation' => $data['derogation'],
            'techniquesGroupes' => $data['techniquesGroupes'],
        ]);
    }

    /**
     * Affiche et traite le formulaire d'édition du descriptif original.
     *
     * @Route("/etablissement/{id}/descriptif/modifier", name="etablissement_descriptif_modifier", methods={"GET", "POST"})
     *
     * @IsGranted("edit_ets", subject="etablissement")
     */
    public function editDescriptifOriginal(Etablissement $etablissement, Request $request): Response
    {
        if ($this->descriptifPersonnalise) {
            return $this->redirectToRoute('etablissement_descriptif', ['id' => $etablissement->getId()]);
        }

        $form = $this->createForm(DescriptifOriginalType::class, $etablissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->etablissementService->saveDescriptifOriginal();
                $this->addFlash('success', 'Les descriptifs ont bien été mis à jour.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Les descriptifs n\'ont pas été mis à jour. Veuillez rééssayez. ('.$e->getMessage().')');
            }

            return $this->redirectToRoute('etablissement_descriptif', ['id' => $etablissement->getId()]);
        }

        return $this->render('etablissement/edit_descriptif_original.html.twig', [
            'etablissement' => $etablissement,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche l'onglet Effectifs et Dégagements de l'établissement.
     *
     * @Route("/etablissement/{id}/effectifs-degagements", name="etablissement_effectifs_degagements", methods={"GET"})
     *
     * @Security("is_granted('effectifs_degagements_ets') and is_granted('view_ets', etablissement)")
     */
    public function effectifsDegagements(Etablissement $etablissement): Response
    {
        $data = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT);

        return $this->render('etablissement/_informations/effectifs_degagements.html.twig', [
            'etablissement' => $etablissement,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
        ]);
    }
}

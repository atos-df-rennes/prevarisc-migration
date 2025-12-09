<?php

namespace App\Controller;

use App\Entity\Groupement;
use App\Entity\GroupementType as GroupementTypeEntity;
use App\Entity\UtilisateurInformations;
use App\Form\GroupementType as GroupementFormType;
use App\Repository\CommuneRepository;
use App\Repository\GroupementRepository;
use App\Repository\GroupementTypeRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    public function __construct(
        EntityManagerInterface $entityManager,
        CommuneRepository $communeRepository
    ) {
        $this->entityManager = $entityManager;
        $this->communeRepository = $communeRepository;
    }

    /**
     * @Route("/admin/groupement/", name="admin_groupement")
     */
    public function index(GroupementTypeRepository $groupementTypeRepository): Response
    {
        $groupementTypes = $groupementTypeRepository->findAll();
        $communes = $this->communeRepository->findAll();

        return $this->render('groupement/index.html.twig', [
            'title' => 'Groupements de communes',
            'admin_groupement_types' => $groupementTypes,
            'communes' => $communes,
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

        return $this->render('groupement/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/groupement/display/{id}", name="admin_groupement_display")
     */
    public function display(Groupement $groupement): Response
    {
        return $this->render('groupement/display.html.twig', [
            'groupement' => $groupement,
        ]);
    }

    /**
     * @Route("/admin/groupement/form/{id}/{scope}", name="admin_groupement_form_scope", requirements={"scope"="general|affectations|coordonnees"})
     */
    public function formByScope(Groupement $groupement, string $scope, Request $request, GroupementRepository $groupementRepository): Response
    {
        $form = $this->createForm(GroupementFormType::class, $groupement, [
            'scopes' => [$scope]
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reaffectationAutomatique = $form->get('reaffectationAutomatique')->getData();

            if ('1' == $reaffectationAutomatique) {
                // TODO: Implémenter le comportement de réaffectation automatique
            }

            $this->entityManager->flush();

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

        return $this->render(sprintf('groupement/form_%s.html.twig', $scope), $templateData);
    }

    /**
     * @Route("/admin/groupement/view", name="admin_groupement_view")
     */
    public function view(Request $request, GroupementRepository $groupementRepository): Response
    {
        $id = $request->query->get('id');
        $groupement = $groupementRepository->find($id);

        if (!$groupement) {
            throw $this->createNotFoundException('Groupement non trouvé');
        }

        $preventionnistes = $groupement->getPreventionnistes();

        return $this->render('groupement/view.html.twig', [
            'row' => $groupement,
            'prev' => $preventionnistes,
        ]);
    }

    /**
     * @Route("/admin/groupement/add", name="admin_groupement_add", methods={"POST"})
     */
    public function add(Request $request, CommuneRepository $communeRepository, UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        try {
            $idGpt = $request->request->get('id_gpt', 0);

            if (0 == $idGpt) {
                $groupement = new Groupement();
                $informations = new UtilisateurInformations();
                $this->populateUtilisateurInformations($informations, $request);
                $this->entityManager->persist($informations);
                $groupement->setInformations($informations);
            } else {
                $groupement = $this->entityManager->getRepository(Groupement::class)->find($idGpt);
                if (!$groupement) {
                    throw new \Exception('Groupement non trouvé');
                }

                // Supprimer les associations existantes
                foreach ($groupement->getCommunes() as $commune) {
                    $groupement->removeCommune($commune);
                }

                foreach ($groupement->getPreventionnistes() as $prev) {
                    $groupement->removePreventionniste($prev);
                }

                $informations = $groupement->getInformations();
                if (null === $informations) {
                    $informations = new UtilisateurInformations();
                    $this->entityManager->persist($informations);
                    $groupement->setInformations($informations);
                }

                $this->populateUtilisateurInformations($informations, $request);
            }

            $nomGroupement = $request->request->get('nom_groupement');
            if (!is_string($nomGroupement)) {
                throw new \Exception('Nom du groupement invalide');
            }

            $groupement->setLibelle($nomGroupement);

            $typeId = $request->request->get('type_groupement');
            $type = $this->entityManager->getRepository(GroupementTypeEntity::class)->find($typeId);
            $groupement->setType($type);

            $this->entityManager->persist($groupement);
            $this->entityManager->flush();

            // Associer les communes
            $villes = $request->request->get('villes');
            if (!empty($villes) && is_array($villes)) {
                foreach ($villes as $numInsee) {
                    $commune = $communeRepository->find($numInsee);
                    if ($commune) {
                        $groupement->addCommune($commune);
                    }
                }
            }

            // Associer les préventionnistes
            $prevs = $request->request->get('prev');
            if (!empty($prevs) && is_array($prevs)) {
                foreach ($prevs as $userId) {
                    $utilisateur = $utilisateurRepository->find($userId);
                    if ($utilisateur) {
                        $groupement->addPreventionniste($utilisateur);
                    }
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Le groupement '.$groupement->getLibelle().' a été ajouté.');

            return new JsonResponse([
                'id' => $groupement->getId(),
                'libelle' => $groupement->getLibelle(),
                'type' => $groupement->getType() ? $groupement->getType()->getId() : null,
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());

            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/admin/groupement/delete", name="admin_groupement_delete")
     */
    public function delete(Request $request, GroupementRepository $groupementRepository): Response
    {
        try {
            $id = $request->query->get('id');
            $groupement = $groupementRepository->find($id);

            if (!$groupement) {
                throw new \Exception('Groupement non trouvé');
            }

            // Les associations sont gérées par Doctrine
            $this->entityManager->remove($groupement);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le groupement a été supprimé.');
        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_groupement_index');
    }

    /**
     * @Route("/admin/groupement/add-type", name="admin_groupement_add_type", methods={"POST"})
     */
    public function addType(Request $request): Response
    {
        try {
            $libelle = $request->request->get('LIBELLE_GROUPEMENTTYPE');

            if (!is_string($libelle)) {
                throw new \Exception('Libellé invalide');
            }

            $groupementType = new GroupementTypeEntity();
            $groupementType->setLibelle($libelle);

            $this->entityManager->persist($groupementType);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le traitement est ok.');

            return new JsonResponse(['id' => $groupementType->getId()]);
        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());

            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function populateUtilisateurInformations(UtilisateurInformations $informations, Request $request): void
    {
        if ($request->request->get('ID_UTILISATEURCIVILITE') && 'null' !== $request->request->get('ID_UTILISATEURCIVILITE')) {
            // $informations->setCivilite($request->request->get('ID_UTILISATEURCIVILITE'));
        }

        $nom = $request->request->get('NOM_UTILISATEURINFORMATIONS', '');
        $prenom = $request->request->get('PRENOM_UTILISATEURINFORMATIONS', '');
        $telFixe = $request->request->get('TELFIXE_UTILISATEURINFORMATIONS', '');
        $telPortable = $request->request->get('TELPORTABLE_UTILISATEURINFORMATIONS', '');
        $telFax = $request->request->get('TELFAX_UTILISATEURINFORMATIONS', '');
        $mail = $request->request->get('MAIL_UTILISATEURINFORMATIONS', '');
        $numeroAdresse = $request->request->get('NUMEROADRESSE_UTILISATEURINFORMATIONS', '');
        $rueAdresse = $request->request->get('RUEADRESSE_UTILISATEURINFORMATIONS', '');
        $cpAdresse = $request->request->get('CPADRESSE_UTILISATEURINFORMATIONS', '');
        $villeAdresse = $request->request->get('VILLEADRESSE_UTILISATEURINFORMATIONS', '');

        $informations->setNom(is_string($nom) ? $nom : '');
        $informations->setPrenom(is_string($prenom) ? $prenom : '');
        $informations->setTelephoneFixe(is_string($telFixe) ? $telFixe : '');
        $informations->setTelephonePortable(is_string($telPortable) ? $telPortable : '');
        $informations->setTelephoneFax(is_string($telFax) ? $telFax : '');
        $informations->setMail(is_string($mail) ? $mail : '');
        $informations->setNumeroAdresse(is_string($numeroAdresse) ? $numeroAdresse : '');
        $informations->setRueAdresse(is_string($rueAdresse) ? $rueAdresse : '');
        $informations->setCodePostalAdresse(is_string($cpAdresse) ? $cpAdresse : '');
        $informations->setVilleAdresse(is_string($villeAdresse) ? $villeAdresse : '');
    }
}

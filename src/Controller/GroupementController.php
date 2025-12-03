<?php

namespace App\Controller;

use App\Entity\Groupement;
use App\Entity\GroupementType;
use App\Entity\UtilisateurInformations;
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
 * @IsGranted("admin")
 */
class GroupementController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/groupement/", name="groupement_index")
     */
    public function index(GroupementTypeRepository $groupementTypeRepository, CommuneRepository $communeRepository): Response
    {
        $groupementTypes = $groupementTypeRepository->findAll();
        $communes = $communeRepository->findAll();

        return $this->render('groupement/index.html.twig', [
            'title' => 'Groupements de communes',
            'groupement_types' => $groupementTypes,
            'communes' => $communes,
        ]);
    }

    /**
     * @Route("/groupement/display", name="groupement_display")
     */
    public function display(Request $request, GroupementRepository $groupementRepository, CommuneRepository $communeRepository, GroupementTypeRepository $groupementTypeRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        $communes = $communeRepository->findAll();
        $types = $groupementTypeRepository->findAll();

        $groupement = null;
        $preventionnistes = [];
        $villesDuGroupement = [];
        $userInfo = null;

        $id = $request->query->get('id');
        if ($id && 0 != $id) {
            $groupement = $groupementRepository->find($id);
            if ($groupement) {
                $preventionnistes = $groupement->getPreventionnistes();
                $villesDuGroupement = $groupement->getCommunes();
                $userInfo = $groupement->getInformations();
            }
        }

        return $this->render('groupement/display.html.twig', [
            'villes' => $communes,
            'types' => $types,
            'groupement' => $groupement,
            'libelle' => $groupement ? $groupement->getLibelle() : '',
            'type' => $groupement && $groupement->getType() ? $groupement->getType()->getId() : null,
            'preventionnistes' => $preventionnistes,
            'ville_du_groupement' => $villesDuGroupement,
            'user_info' => $userInfo,
        ]);
    }

    /**
     * @Route("/groupement/view", name="groupement_view")
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
     * @Route("/groupement/add", name="groupement_add", methods={"POST"})
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
            $type = $this->entityManager->getRepository(GroupementType::class)->find($typeId);
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
     * @Route("/groupement/delete", name="groupement_delete")
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

        return $this->redirectToRoute('groupement_index');
    }

    /**
     * @Route("/groupement/add-type", name="groupement_add_type", methods={"POST"})
     */
    public function addType(Request $request): Response
    {
        try {
            $libelle = $request->request->get('LIBELLE_GROUPEMENTTYPE');

            if (!is_string($libelle)) {
                throw new \Exception('Libellé invalide');
            }

            $groupementType = new GroupementType();
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

    /**
     * @Route("/groupement/preventionniste", name="groupement_preventionniste", methods={"POST"})
     */
    public function preventionniste(Request $request): Response
    {
        $request->request->get('groupement');

        // TODO: Implémenter la logique de réaffectation des préventionnistes
        // Nécessite la migration du service Service_GroupementCommunes

        return new JsonResponse(['success' => true]);
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

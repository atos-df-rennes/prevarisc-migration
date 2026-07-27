<?php

namespace App\Controller\Dossier;

use App\Constant\TypePrescription;
use App\Entity\Dossier;
use App\Entity\PrescriptionType;
use App\Repository\PrescriptionArticleRepository;
use App\Repository\PrescriptionCategorieRepository;
use App\Repository\PrescriptionTexteRepository;
use App\Repository\PrescriptionTypeRepository;
use App\Service\DossierPrescriptionService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierPrescriptionTypeController extends AbstractController
{
    public function __construct(private DossierPrescriptionService $service, private PrescriptionCategorieRepository $categorieRepo, private PrescriptionTypeRepository $prescriptionTypeRepo, private PrescriptionTexteRepository $texteRepo, private PrescriptionArticleRepository $articleRepo, private LoggerInterface $logger)
    {
    }

    /**
     * Retourne le contenu de la modale "Ajouter une prescription type".
     */
    #[Route(path: '/dossier/{id}/prescriptions/modal-type', name: 'dossier_prescriptions_modal_type', requirements: ['id' => '\d+'], methods: ['GET'], options: ['expose' => true])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function ajouterTypeModal(Dossier $dossier): Response
    {
        return $this->render('dossier/prescriptions/_modal_ajout_type.html.twig', [
            'dossier' => $dossier,
            'categories' => $this->categorieRepo->findBy([], ['numero' => 'ASC']),
            'prescriptionsTypesInitiales' => $this->prescriptionTypeRepo->findByClassement(0, 0, 0),
        ]);
    }

    /**
     * Recherche des prescriptions types par mots-clés (AJAX).
     * Retourne un fragment HTML avec la liste des résultats.
     */
    #[Route(path: '/dossier/{id}/prescriptions/recherche-mots-cles', name: 'dossier_prescriptions_recherche_mots_cles', requirements: ['id' => '\d+'], methods: ['POST'], options: ['expose' => true])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function rechercheMotsCles(Request $request, Dossier $dossier): Response
    {
        $rawMotsCles = $request->request->get('motsCles', '');
        $motsCles = [] !== ($parts = array_filter(explode(' ', is_string($rawMotsCles) ? trim($rawMotsCles) : '')))
            ? array_values($parts)
            : [];

        $prescriptionsTypes = [] !== $motsCles
            ? $this->prescriptionTypeRepo->findByMotsCles($motsCles)
            : [];

        return $this->render('dossier/prescriptions/_prescription_type_list.html.twig', [
            'dossier' => $dossier,
            'prescriptionsTypes' => $prescriptionsTypes,
            'motsCles' => $motsCles,
        ]);
    }

    /**
     * Navigation hiérarchique par classement (catégorie → texte → article) (AJAX).
     * Retourne un fragment HTML adapté au niveau de navigation courant.
     */
    #[Route(path: '/dossier/{id}/prescriptions/recherche-classement', name: 'dossier_prescriptions_recherche_classement', requirements: ['id' => '\d+'], methods: ['POST'], options: ['expose' => true])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function rechercheClassement(Request $request, Dossier $dossier): Response
    {
        $categorieId = $request->request->getInt('categorieId', 0);
        $texteId = $request->request->getInt('texteId', 0);
        $articleId = $request->request->getInt('articleId', 0);

        if (0 === $categorieId) {
            return $this->render('dossier/prescriptions/_emplacement_navigation.html.twig', [
                'dossier' => $dossier,
                'niveau' => 'categories',
                'categories' => $this->categorieRepo->findBy([], ['numero' => 'ASC']),
                'prescriptionsTypes' => $this->prescriptionTypeRepo->findByClassement(0, 0, 0),
            ]);
        }

        $categorie = $this->categorieRepo->find($categorieId);

        if (0 === $texteId) {
            return $this->render('dossier/prescriptions/_emplacement_navigation.html.twig', [
                'dossier' => $dossier,
                'niveau' => 'textes',
                'categorie' => $categorie,
                'textes' => null !== $categorie ? $this->texteRepo->findByCategorie($categorie) : [],
                'prescriptionsTypes' => $this->prescriptionTypeRepo->findByClassement($categorieId, 0, 0),
            ]);
        }

        $texte = $this->texteRepo->find($texteId);

        if (0 === $articleId) {
            return $this->render('dossier/prescriptions/_emplacement_navigation.html.twig', [
                'dossier' => $dossier,
                'niveau' => 'articles',
                'categorie' => $categorie,
                'texte' => $texte,
                'articles' => null !== $texte ? $this->articleRepo->findByTexte($texte) : [],
                'prescriptionsTypes' => $this->prescriptionTypeRepo->findByClassement($categorieId, $texteId, 0),
            ]);
        }

        $article = $this->articleRepo->find($articleId);

        return $this->render('dossier/prescriptions/_emplacement_navigation.html.twig', [
            'dossier' => $dossier,
            'niveau' => 'prescriptions',
            'categorie' => $categorie,
            'texte' => $texte,
            'article' => $article,
            'prescriptionsTypes' => $this->prescriptionTypeRepo->findByClassement($categorieId, $texteId, $articleId),
        ]);
    }

    /**
     * Ajoute une prescription type au dossier (AJAX).
     * Retourne le fragment HTML de la prescription ajoutée.
     */
    #[Route(path: '/dossier/{id}/prescriptions/{idPrescType}/ajouter-type', name: 'dossier_prescriptions_ajouter_type', requirements: ['id' => '\d+', 'idPrescType' => '\d+'], methods: ['POST'], options: ['expose' => true])]
    #[ParamConverter('prescriptionType', options: ['id' => 'idPrescType'])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function ajouterDepuisType(Request $request, Dossier $dossier, PrescriptionType $prescriptionType): Response
    {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('add_prescription_type_'.$dossier->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        $typePrescription = $request->request->getInt('typePrescriptionDossier', TypePrescription::REGLEMENTAIRE);

        try {
            $prescription = $this->service->ajouterDepuisType($dossier, $prescriptionType, $typePrescription);
        } catch (\Exception $exception) {
            $this->logger->error("Erreur lors de l'ajout de la prescription type au dossier.", [
                'message' => $exception->getMessage(),
                'dossier' => $dossier->getId(),
            ]);

            return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->render('dossier/prescriptions/_prescription_item.html.twig', [
            'prescription' => $prescription,
            'dossier' => $dossier,
        ]);
    }
}

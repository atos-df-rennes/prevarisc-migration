<?php

namespace App\Controller;

use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Entity\Dossier;
use App\Form\Dossier\DossierAjouterEtablissementType;
use App\Form\Dossier\DossierLiaisonsType;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Service\Dossier\DossierLiaisonsService;
use App\Service\Dossier\DossierSauvegardeService;
use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierLiaisonsController extends AbstractController implements DossierLateralNavigationInterface
{
    /** @var DossierLiaisonsService */
    private $dossierLiaisonsService;

    /** @var DossierSauvegardeService */
    private $dossierSauvegardeService;

    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var DossierRepository */
    private $dossierRepository;

    public function __construct(
        DossierLiaisonsService $dossierLiaisonsService,
        DossierSauvegardeService $dossierSauvegardeService,
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository
    ) {
        $this->dossierLiaisonsService = $dossierLiaisonsService;
        $this->dossierSauvegardeService = $dossierSauvegardeService;
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
    }

    /**
     * Affichage des établissements et dossiers liés.
     *
     * @Route("/dossier/{id}/liees", name="dossier_liees", requirements={"id"="\d+"}, methods={"GET"})
     *
     * @IsGranted("view_doss", subject="dossier")
     */
    public function liees(Dossier $dossier): Response
    {
        $formAjouterEtab = $this->createForm(DossierAjouterEtablissementType::class, null, [
            'action' => $this->generateUrl('dossier_liees_etab_ajouter', ['id' => $dossier->getId()]),
        ]);

        return $this->render('dossier/liaisons/index.html.twig', [
            'dossier' => $dossier,
            'liaisons' => $this->dossierLiaisonsService->getLiaisons($dossier),
            'etablissements' => $dossier->getEtablissements()->toArray(),
            'formAjouterEtab' => $formAjouterEtab->createView(),
        ]);
    }

    /**
     * Sélection des dossiers à lier (GET : formulaire, POST : enregistrement).
     *
     * @Route("/dossier/{id}/liees/dossiers", name="dossier_liees_doss", requirements={"id"="\d+"}, methods={"GET", "POST"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function lieesDoss(Request $request, Dossier $dossier): Response
    {
        if ($dossier->hasVerrou()) {
            $this->addFlash('warning', 'Le dossier est verrouillé.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        $disponibles = $this->dossierLiaisonsService->getDossiersDisponibles($dossier);
        $form = $this->createForm(DossierLiaisonsType::class, null, ['available_dossiers' => $disponibles]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Collection $dossiersToAdd */
            $dossiersToAdd = $form->get('dossiersToAdd')->getData();
            $this->dossierLiaisonsService->addLiaisons($dossier, $dossiersToAdd->toArray());

            $this->addFlash('success', 'Les liaisons ont bien été enregistrées.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        $grouped = $this->dossierLiaisonsService->getDossiersDisponiblesGroupesParEtablissement($dossier);

        return $this->render('dossier/liaisons/dossiers.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
            'grouped' => $grouped,
        ]);
    }

    /**
     * Ajout d'un établissement lié au dossier.
     *
     * @Route("/dossier/{id}/liees/etablissements/ajouter", name="dossier_liees_etab_ajouter",
     *     requirements={"id"="\d+"},
     *     methods={"POST"},
     *     defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function ajouterEtablissementLie(Request $request, Dossier $dossier): Response
    {
        if ($dossier->hasVerrou()) {
            $this->addFlash('warning', 'Le dossier est verrouillé.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        $form = $this->createForm(DossierAjouterEtablissementType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', 'Requête invalide.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        $idEtablissement = (int) $form->get('idEtablissement')->getData();
        $etablissement = $idEtablissement > 0 ? $this->etablissementRepository->find($idEtablissement) : null;

        if (null === $etablissement) {
            $this->addFlash('danger', 'Établissement introuvable.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        $etablissementsIds = $this->dossierLiaisonsService->addEtablissement($dossier, $etablissement);
        $this->addFlash('success', 'L\'établissement a bien été lié au dossier.');

        return $this->redirect($this->dossierSauvegardeService->construireUrlRedirection(
            $dossier,
            $etablissementsIds,
            $this->generateUrl('dossier_liees', ['id' => $dossier->getId()])
        ));
    }

    /**
     * Suppression du lien entre un dossier et un établissement.
     *
     * @Route("/dossier/{id}/liees/etablissements/{idEtab}/supprimer", name="dossier_liees_etab_supprimer",
     *     requirements={"id"="\d+", "idEtab"="\d+"},
     *     methods={"GET", "DELETE"},
     *     options={"expose"=true},
     *     defaults={"_dossier_lateral_nav": false})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function supprimerEtablissementLie(Request $request, Dossier $dossier, int $idEtab): Response
    {
        $etablissement = $this->etablissementRepository->find($idEtab);

        if (null === $etablissement || !$dossier->getEtablissements()->contains($etablissement)) {
            throw $this->createNotFoundException('Établissement introuvable ou non lié à ce dossier.');
        }

        $form = $this->createFormBuilder()
            ->setMethod(Request::METHOD_DELETE)
            ->setAction($this->generateUrl('dossier_liees_etab_supprimer', [
                'id' => $dossier->getId(),
                'idEtab' => $etablissement->getId(),
            ]))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etablissementsIds = $this->dossierLiaisonsService->removeEtablissement($dossier, $etablissement);
            $this->addFlash('success', 'Le lien avec l\'établissement a bien été supprimé.');

            return $this->redirect($this->dossierSauvegardeService->construireUrlRedirection(
                $dossier,
                $etablissementsIds,
                $this->generateUrl('dossier_liees', ['id' => $dossier->getId()])
            ));
        }

        return $this->render('dossier/liaisons/etab_supprimer.html.twig', [
            'dossier' => $dossier,
            'etablissement' => $etablissement,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'une liaison entre deux dossiers.
     *
     * @Route("/dossier/{id}/liees/{idLie}/supprimer", name="dossier_liees_supprimer",
     *     requirements={"id"="\d+", "idLie"="\d+"},
     *     methods={"GET", "DELETE"},
     *     options={"expose"=true},
     *     defaults={"_dossier_lateral_nav": false}
     * )
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function supprimerLiaison(Request $request, Dossier $dossier, int $idLie): Response
    {
        $dossierLie = $this->dossierRepository->find($idLie);

        if (null === $dossierLie) {
            throw $this->createNotFoundException('Dossier lié introuvable.');
        }

        $isLinked = $dossier->getDossiersLies()->contains($dossierLie)
            || $dossierLie->getDossiersLies()->contains($dossier);

        if (!$isLinked) {
            $this->addFlash('warning', 'Ce dossier n\'est pas lié au dossier courant.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        $form = $this->createFormBuilder()
            ->setMethod(Request::METHOD_DELETE)
            ->setAction($this->generateUrl('dossier_liees_supprimer', [
                'id' => $dossier->getId(),
                'idLie' => $dossierLie->getId(),
            ]))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dossierLiaisonsService->removeLiaison($dossier, $dossierLie);
            $this->addFlash('success', 'Le lien avec le dossier a bien été supprimé.');

            return $this->redirectToRoute('dossier_liees', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/liaisons/supprimer.html.twig', [
            'dossier' => $dossier,
            'dossierLie' => $dossierLie,
            'form' => $form->createView(),
        ]);
    }
}

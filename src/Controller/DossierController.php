<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use App\Form\DossierType;
use App\Repository\AvisRepository;
use App\Repository\CommissionRepository;
use App\Repository\CommissionTypeRepository;
use App\Repository\DossierTypeRepository;
use App\Repository\EtablissementRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierController extends AbstractController
{
    /**
     * Affichage et édition des informations générales d'un dossier.
     *
     * @Route("/dossier/{id}", name="dossier_index", requirements={"id"="\d+"}, methods={"GET", "POST"})
     */
    public function index(
        Request $request,
        Dossier $dossier,
        EntityManagerInterface $entityManager,
        DossierTypeRepository $dossierTypeRepository,
        AvisRepository $avisRepository,
        CommissionTypeRepository $commissionTypeRepository,
        CommissionRepository $commissionRepository,
        EtablissementRepository $etablissementRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        $form = $this->createForm(DossierType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le dossier a été mis à jour avec succès.');

            return $this->redirectToRoute('dossier_index', ['id' => $dossier->getId()]);
        }

        // Récupération des données nécessaires pour l'affichage
        $dossierTypes = $dossierTypeRepository->findAll();
        $avis = $avisRepository->findAll();

        // Récupération des commissions par type
        $commissionsParType = [];
        foreach ($commissionTypeRepository->findAll() as $type) {
            $commissionsParType[$type->getId()] = [
                'libelle' => $type->getLibelle(),
                'commissions' => $commissionRepository->findBy(['type' => $type]),
            ];
        }

        // Récupération des établissements liés au dossier
        $etablissements = $dossier->getEtablissements();
        $enteteEtab = null;
        if ($etablissements->count() > 0) {
            $premierEtablissement = $etablissements->first();
            if ($premierEtablissement) {
                $enteteEtab = $etablissementRepository->find($premierEtablissement->getId());
            }
        }

        // Récupération des informations de l'utilisateur connecté
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Vérification des permissions
        $isAllowedChangeAvis = $this->isGranted('edit_avis_com', 'avis_commission');
        $isAllowedDeleteDossier = $this->isGranted('delete_dossier', 'suppression');

        // Informations sur le verrouillage
        $userInfoVerrou = null;
        if ($dossier->getVerrou() && $dossier->getUtilisateurVerrou()) {
            $userInfoVerrou = $utilisateurRepository->find($dossier->getUtilisateurVerrou());
        }

        return $this->render('dossier/index.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
            'dossierTypes' => $dossierTypes,
            'avis' => $avis,
            'commissionsParType' => $commissionsParType,
            'etablissements' => $etablissements,
            'enteteEtab' => $enteteEtab,
            'user' => $user,
            'isAllowedChangeAvis' => $isAllowedChangeAvis,
            'isAllowedDeleteDossier' => $isAllowedDeleteDossier,
            'userInfoVerrou' => $userInfoVerrou,
            'action' => 'edit',
        ]);
    }

    /**
     * Création d'un nouveau dossier.
     *
     * @Route("/dossier/nouveau", name="dossier_nouveau", methods={"GET", "POST"})
     */
    public function nouveau(
        Request $request,
        EntityManagerInterface $entityManager,
        DossierTypeRepository $dossierTypeRepository,
        AvisRepository $avisRepository,
        CommissionTypeRepository $commissionTypeRepository,
        CommissionRepository $commissionRepository,
        EtablissementRepository $etablissementRepository
    ): Response {
        $dossier = new Dossier();
        $dossier->setDateInsert(new \DateTime());

        /** @var Utilisateur $user */
        $user = $this->getUser();
        $dossier->setCreateur($user);

        // Récupération de l'établissement si fourni
        $idEtablissement = $request->query->get('id_etablissement');
        if ($idEtablissement) {
            $etablissement = $etablissementRepository->find($idEtablissement);
            if ($etablissement) {
                $dossier->addEtablissement($etablissement);
            }
        }

        $form = $this->createForm(DossierType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($dossier);
            $entityManager->flush();

            $this->addFlash('success', 'Le dossier a été créé avec succès.');

            return $this->redirectToRoute('dossier_index', ['id' => $dossier->getId()]);
        }

        // Récupération des données nécessaires pour l'affichage
        $dossierTypes = $dossierTypeRepository->findAll();
        $avis = $avisRepository->findAll();

        // Récupération des commissions par type
        $commissionsParType = [];
        foreach ($commissionTypeRepository->findAll() as $type) {
            $commissionsParType[$type->getId()] = [
                'libelle' => $type->getLibelle(),
                'commissions' => $commissionRepository->findBy(['type' => $type]),
            ];
        }

        $enteteEtab = null;
        if ($idEtablissement) {
            $enteteEtab = $etablissementRepository->find($idEtablissement);
        }

        return $this->render('dossier/index.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
            'dossierTypes' => $dossierTypes,
            'avis' => $avis,
            'commissionsParType' => $commissionsParType,
            'etablissements' => $dossier->getEtablissements(),
            'enteteEtab' => $enteteEtab,
            'user' => $user,
            'isAllowedChangeAvis' => false,
            'isAllowedDeleteDossier' => false,
            'userInfoVerrou' => null,
            'action' => 'new',
            'idEtablissement' => $idEtablissement,
        ]);
    }

    // @todo: Ajouter la vérification du privilège verrouillage de dossier
    /**
     * @Route("/dossier/{id}/verrouiller", name="dossier_verrouiller")
     */
    public function verrouiller(Request $request, Dossier $dossier, EntityManagerInterface $entityManager): Response
    {
        $referer = $request->headers->get('referer');

        if (null === $referer) {
            throw new \InvalidArgumentException("La route d'origine n'a pas pu être déterminée.");
        }

        $dossier->setVerrou(true);
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $dossier->setUtilisateurVerrou($user->getId());

        $entityManager->flush();

        return $this->redirect($referer);
    }

    /**
     * @Route("/dossier/{id}/deverrouiller", name="dossier_deverrouiller")
     */
    public function deverrouiller(Request $request, Dossier $dossier, EntityManagerInterface $entityManager): Response
    {
        $referer = $request->headers->get('referer');

        if (null === $referer) {
            throw new \InvalidArgumentException("La route d'origine n'a pas pu être déterminée.");
        }

        $dossier->setVerrou(false);
        $dossier->setUtilisateurVerrou(null);

        $entityManager->flush();

        return $this->redirect($referer);
    }

    /**
     * Suppression d'un dossier.
     *
     * @Route("/dossier/{id}/supprimer", name="dossier_supprimer", methods={"POST"})
     *
     * @IsGranted("delete_dossier", subject="suppression")
     */
    public function supprimer(
        Request $request,
        Dossier $dossier,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('delete'.$dossier->getId(), $token)) {
            /** @var Utilisateur $user */
            $user = $this->getUser();
            $dossier->setDateSuppression(new \DateTime());
            $dossier->setDeletedBy($user);

            $entityManager->flush();

            $this->addFlash('success', 'Le dossier a été supprimé avec succès.');
        }

        return $this->redirectToRoute('etablissement_index');
    }
}

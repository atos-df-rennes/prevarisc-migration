<?php

namespace App\Controller\Dossier;

use App\Constant\NomInterneCapsule;
use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Dto\Descriptif\DescriptifFormData;
use App\Entity\Dossier;
use App\Repository\DossierRepository;
use App\Service\Descriptif as DescriptifService;
use App\Service\Descriptif\DescriptifFormBuilder;
use App\Service\Descriptif\DescriptifFormHydrator;
use App\Service\Descriptif\DescriptifFormPersister;
use App\Service\DescriptifCopier;
use App\Service\DescriptifInspector;
use App\Service\Dossier\EffectifsDegagementsRepriseValidator;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Gère les actions liées aux effectifs et dégagements d'un dossier.
 */
#[Route(requirements: ['id' => '\d+'])]
class DossierEffectifsController extends AbstractController implements DossierLateralNavigationInterface
{
    public function __construct(private DescriptifService $descriptifService, private DescriptifInspector $descriptifInspector, private DescriptifCopier $descriptifCopier, private DossierRepository $dossierRepository, private EffectifsDegagementsRepriseValidator $repriseValidator, private LoggerInterface $logger, private DescriptifFormHydrator $descriptifFormHydrator, private DescriptifFormBuilder $descriptifFormBuilder, private DescriptifFormPersister $descriptifFormPersister, private FormFactoryInterface $formFactory)
    {
    }

    /**
     * Affiche l'onglet Effectifs et Dégagements du dossier.
     */
    #[Route(path: '/dossier/{id}/effectifs-degagements', name: 'dossier_effectifs_degagements', methods: ['GET'])]
    #[Security("is_granted('effectifs_degagements_doss') and is_granted('view_doss', dossier)")]
    public function effectifsDegagements(Dossier $dossier): Response
    {
        $data = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);

        return $this->render('dossier/_effectifs_degagements.html.twig', [
            'dossier' => $dossier,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
            'hasData' => $this->descriptifInspector->rubriquesHaveData($data['rubriques']),
        ]);
    }

    #[Route(path: '/dossier/{id}/copier-effectifs-degagements-etablissement', name: 'dossier_copier_effectifs_degagements_etablissement', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['_dossier_lateral_nav' => false])]
    #[Security("is_granted('effectifs_degagements_doss') and is_granted('edit_doss_ignore_verrou', dossier)")]
    public function copierEffectifsDegagementsEtablissement(
        Dossier $dossier,
        Request $request
    ): Response {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('copier_effectifs_degagements_'.$dossier->getId(), $csrfToken)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        $etablissements = $dossier->getEtablissements()->toArray();
        $nbEtablissements = count($etablissements);

        if (1 !== $nbEtablissements) {
            return $this->json([
                'error' => sprintf(
                    'Impossible de copier : %d établissement(s) lié(s) au dossier (1 attendu). Les effectifs et dégagements n\'ont pas été copiés.',
                    $nbEtablissements
                ),
            ], 400);
        }

        $etablissement = $etablissements[0];

        if (!$this->isGranted('edit_ets', $etablissement)) {
            return $this->json(['error' => 'Vous n\'avez pas le droit de modifier cet établissement.'], 403);
        }

        try {
            $sourceDossier = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);
            $targetEtablissement = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT);

            $this->descriptifCopier->copyFromDossierToEtablissement(
                $sourceDossier['rubriques'],
                $targetEtablissement['rubriques'],
                $etablissement
            );
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la copie des effectifs et dégagements dossier→établissement.', [
                'dossier_id' => $dossier->getId(),
                'etablissement_id' => $etablissement->getId(),
                'error' => $exception->getMessage(),
            ]);

            return $this->json(['error' => 'Erreur technique lors de la copie. Veuillez réessayer.'], 500);
        }

        return $this->json(['success' => true]);
    }

    #[Route(path: '/dossier/{id}/recuperer-effectifs-degagements-etablissement', name: 'dossier_recuperer_effectifs_degagements_etablissement', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['_dossier_lateral_nav' => false])]
    #[Security("is_granted('effectifs_degagements_doss') and is_granted('edit_doss_ignore_verrou', dossier)")]
    public function recupererEffectifsDegagementsEtablissement(
        Dossier $dossier,
        Request $request
    ): Response {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('recuperer_effectifs_degagements_'.$dossier->getId(), $csrfToken)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        $etablissements = $dossier->getEtablissements()->toArray();
        $nbEtablissements = count($etablissements);

        if (1 !== $nbEtablissements) {
            return $this->json([
                'error' => sprintf(
                    'Impossible de récupérer : %d établissement(s) lié(s) au dossier (1 attendu). Les effectifs et dégagements n\'ont pas été récupérés.',
                    $nbEtablissements
                ),
            ], 400);
        }

        $etablissement = $etablissements[0];

        if (!$this->isGranted('view_ets', $etablissement)) {
            return $this->json(['error' => 'Vous n\'avez pas le droit de consulter cet établissement.'], 403);
        }

        try {
            $sourceEtablissement = $this->descriptifService->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT);
            $targetDossier = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);

            $this->descriptifCopier->copyFromEtablissementToDossier(
                $sourceEtablissement['rubriques'],
                $targetDossier['rubriques'],
                $dossier
            );
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la récupération des effectifs et dégagements établissement→dossier.', [
                'dossier_id' => $dossier->getId(),
                'etablissement_id' => $etablissement->getId(),
                'error' => $exception->getMessage(),
            ]);

            return $this->json(['error' => 'Erreur technique lors de la récupération. Veuillez réessayer.'], 500);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Retourne le contenu de la modale "Reprendre les effectifs et dégagements d'un dossier précédent".
     */
    #[Route(path: '/dossier/{id}/effectifs-degagements/modal-reprise', name: 'dossier_effectifs_degagements_modal_reprise', requirements: ['id' => '\d+'], methods: ['GET'], defaults: ['_dossier_lateral_nav' => false])]
    #[Security("is_granted('effectifs_degagements_doss') and is_granted('edit_doss_ignore_verrou', dossier)")]
    public function effectifsDegagementsModalReprise(Dossier $dossier): Response
    {
        $etablissements = $dossier->getEtablissements();

        if (1 !== $etablissements->count()) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $etablissement = $etablissements->first();
        if (false === $etablissement) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $groupes = $this->dossierRepository->findByEtablissementGrouped($etablissement, $dossier);

        return $this->render('dossier/_modal_reprise_effectifs_degagements.html.twig', [
            'dossier' => $dossier,
            'etudes' => $groupes['etudes'],
        ]);
    }

    /**
     * Reprend les effectifs et dégagements d'un dossier source vers le dossier courant.
     */
    #[Route(path: '/dossier/{id}/effectifs-degagements/reprendre-depuis-etude', name: 'dossier_effectifs_degagements_reprendre_depuis_etude', requirements: ['id' => '\d+'], methods: ['POST'], defaults: ['_dossier_lateral_nav' => false])]
    #[Security("is_granted('effectifs_degagements_doss') and is_granted('edit_doss_ignore_verrou', dossier)")]
    public function reprendreEffectifsDegagementsDepuisEtude(
        Dossier $dossier,
        Request $request
    ): Response {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('reprendre_effectifs_degagements_'.$dossier->getId(), $csrfToken)) {
            return $this->json(['error' => 'Token CSRF invalide.'], 403);
        }

        $idDossierSource = $request->request->getInt('dossierSelect', 0);
        if (0 === $idDossierSource) {
            return $this->json(['error' => 'Aucun dossier source sélectionné.'], 400);
        }

        $dossierSource = $this->dossierRepository->find($idDossierSource);
        if (null === $dossierSource || null !== $dossierSource->getDateSuppression()) {
            return $this->json(['error' => 'Dossier source introuvable.'], 404);
        }

        if (!$this->isGranted('view_doss', $dossierSource)) {
            return $this->json(['error' => "Vous n'avez pas le droit de consulter le dossier source."], 403);
        }

        $erreurValidation = $this->repriseValidator->validerSource($dossier, $dossierSource);
        if (null !== $erreurValidation) {
            return $this->json(['error' => $erreurValidation], 400);
        }

        try {
            $sourceData = $this->descriptifService->getForDossier($dossierSource, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);
            $targetData = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);

            $this->descriptifCopier->copyFromDossierToDossier(
                $sourceData['rubriques'],
                $targetData['rubriques'],
                $dossier
            );
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la reprise des effectifs et dégagements depuis un autre dossier.', [
                'dossier_cible_id' => $dossier->getId(),
                'dossier_source_id' => $idDossierSource,
                'error' => $exception->getMessage(),
            ]);

            return $this->json(['error' => 'Erreur technique lors de la reprise. Veuillez réessayer.'], 500);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Affiche et traite le formulaire d'édition des effectifs et dégagements du dossier.
     */
    #[Route(path: '/dossier/{id}/effectifs-degagements/modifier', name: 'dossier_effectifs_degagements_edit', methods: ['GET', 'POST'])]
    #[Security("is_granted('effectifs_degagements_doss') and is_granted('edit_doss_ignore_verrou', dossier)")]
    public function editEffectifsDegagements(Dossier $dossier, Request $request): Response
    {
        $data = $this->descriptifService->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER);

        $formData = $this->descriptifFormHydrator->hydrateFromDossier($dossier, $data['rubriques']);
        $form = $this->descriptifFormBuilder->buildForm($this->formFactory, $formData, $data['rubriques']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var DescriptifFormData $submittedData */
                $submittedData = $form->getData();
                $this->descriptifFormPersister->persistForDossier($dossier, $submittedData, $data['rubriques']);
                $this->addFlash('success', 'Les effectifs et dégagements ont bien été mis à jour.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la sauvegarde des effectifs et dégagements du dossier', [
                    'dossier_id' => $dossier->getId(),
                    'exception' => $e->getMessage(),
                ]);
                $this->addFlash('error', 'Les effectifs et dégagements n\'ont pas été mis à jour. Veuillez réessayer.');
            }

            return $this->redirectToRoute('dossier_effectifs_degagements', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/edit_effectifs_degagements.html.twig', [
            'dossier' => $dossier,
            'capsule' => $data['capsule'],
            'rubriques' => $data['rubriques'],
            'form' => $form->createView(),
        ]);
    }
}

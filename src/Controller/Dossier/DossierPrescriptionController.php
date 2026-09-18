<?php

namespace App\Controller\Dossier;

use App\Constant\TypePrescription;
use App\Controller\Contract\DossierLateralNavigationInterface;
use App\Controller\Contract\DossierPrescriptionsAccessInterface;
use App\Entity\Dossier;
use App\Entity\PrescriptionDossier;
use App\Form\PrescriptionDossierType;
use App\Service\DossierPrescriptionService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DossierPrescriptionController extends AbstractController implements DossierLateralNavigationInterface, DossierPrescriptionsAccessInterface
{
    public function __construct(private readonly DossierPrescriptionService $service)
    {
    }

    #[Route(path: '/dossier/{id}/prescriptions', name: 'dossier_prescriptions', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('view_doss', subject: 'dossier')]
    public function index(Dossier $dossier): Response
    {
        return $this->render('dossier/prescriptions/index.html.twig', [
            'dossier' => $dossier,
            'prescriptionsRegl' => $this->service->getPrescriptionsParType($dossier, TypePrescription::REGLEMENTAIRE),
            'prescriptionsExploitation' => $this->service->getPrescriptionsParType($dossier, TypePrescription::EXPLOITATION),
            'prescriptionsAmelioration' => $this->service->getPrescriptionsParType($dossier, TypePrescription::AMELIORATION),
        ]);
    }

    #[Route(path: '/dossier/{id}/prescriptions/reordonner', name: 'dossier_prescriptions_reordonner', requirements: ['id' => '\d+'], methods: ['POST'], options: ['expose' => true], defaults: ['_dossier_lateral_nav' => false])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function reordonner(Request $request, Dossier $dossier): Response
    {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('reorder_prescriptions', $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        $rawIdsRegl = $request->request->get('idsRegl');
        $rawIdsAutres = $request->request->get('idsAutres');

        $idsRegl = array_filter(array_map(intval(...), explode(',', is_string($rawIdsRegl) ? $rawIdsRegl : '')));
        $idsAutres = array_filter(array_map(intval(...), explode(',', is_string($rawIdsAutres) ? $rawIdsAutres : '')));

        $this->service->reordonner($dossier, array_values($idsRegl), array_values($idsAutres));

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/dossier/{id}/prescriptions/ajouter', name: 'dossier_prescriptions_ajouter', requirements: ['id' => '\d+'])]
    #[Route(path: '/dossier/{id}/prescriptions/{idPrescription}/modifier', name: 'dossier_prescriptions_modifier', requirements: ['id' => '\d+', 'idPrescription' => '\d+'])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function form(Request $request, Dossier $dossier, #[MapEntity(id: 'idPrescription')] ?PrescriptionDossier $prescription = null): Response
    {
        $isNew = null === $prescription;
        $ancienType = null;

        if ($isNew) {
            $prescription = new PrescriptionDossier();
            $prescription->setType(TypePrescription::REGLEMENTAIRE);
        } else {
            $ancienType = $prescription->getType();
            $this->service->preparerPourEdition($prescription);
        }

        $form = $this->createForm(PrescriptionDossierType::class, $prescription, [
            'action' => $this->generateUrl(
                $isNew ? 'dossier_prescriptions_ajouter' : 'dossier_prescriptions_modifier',
                $isNew
                    ? ['id' => $dossier->getId()]
                    : ['id' => $dossier->getId(), 'idPrescription' => $prescription->getId()]
            ),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->service->sauvegarder($dossier, $prescription, $ancienType);

            $this->addFlash(
                'success',
                $isNew ? 'La prescription a bien été ajoutée.' : 'La prescription a bien été modifiée.'
            );

            return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier/prescriptions/form.html.twig', [
            'dossier' => $dossier,
            'prescription' => $prescription,
            'form' => $form,
            'is_new' => $isNew,
        ]);
    }

    #[Route(path: '/dossier/{id}/prescriptions/{idPrescription}/supprimer', name: 'dossier_prescriptions_supprimer', requirements: ['id' => '\d+', 'idPrescription' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_doss', subject: 'dossier')]
    public function supprimer(Request $request, Dossier $dossier, #[MapEntity(id: 'idPrescription')] PrescriptionDossier $prescription): Response
    {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('delete_prescription_'.$prescription->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        $this->service->supprimer($prescription);

        $this->addFlash('success', 'La prescription a bien été supprimée.');

        return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
    }
}

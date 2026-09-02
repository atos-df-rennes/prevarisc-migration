<?php

namespace App\Controller\Dossier;

use App\Controller\Contract\DossierPrescriptionsAccessInterface;
use App\Entity\Dossier;
use App\Repository\DossierRepository;
use App\Service\DossierPrescriptionService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierPrescriptionRepriseController extends AbstractController implements DossierPrescriptionsAccessInterface
{
    /** @var DossierPrescriptionService */
    private $service;

    /** @var DossierRepository */
    private $dossierRepo;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        DossierPrescriptionService $service,
        DossierRepository $dossierRepo,
        LoggerInterface $logger
    ) {
        $this->service = $service;
        $this->dossierRepo = $dossierRepo;
        $this->logger = $logger;
    }

    /**
     * Retourne le contenu de la modale "Reprendre les prescriptions d'autres dossiers".
     *
     * Disponible uniquement si le dossier est lié à un seul établissement.
     *
     * @Route("/dossier/{id}/prescriptions/modal-reprise", name="dossier_prescriptions_modal_reprise", requirements={"id"="\d+"}, methods={"GET"}, options={"expose"=true})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function repriseModal(Dossier $dossier): Response
    {
        $etablissements = $dossier->getEtablissements();

        if (1 !== $etablissements->count()) {
            return new Response(
                'Pour pouvoir utiliser cette fonctionnalité, le dossier ne doit concerner qu\'un seul établissement.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $etablissement = $etablissements->first();
        if (false === $etablissement) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $groupes = $this->dossierRepo->findByEtablissementGrouped($etablissement, $dossier);

        return $this->render('dossier/prescriptions/_modal_reprise.html.twig', [
            'dossier' => $dossier,
            'etudes' => $groupes['etudes'],
            'visites' => $groupes['visites'],
            'autres' => $groupes['autres'],
        ]);
    }

    /**
     * Reprend les prescriptions des dossiers sélectionnés vers le dossier courant.
     *
     * @Route("/dossier/{id}/prescriptions/reprendre", name="dossier_prescriptions_reprendre", requirements={"id"="\d+"}, methods={"POST"})
     *
     * @IsGranted("edit_doss", subject="dossier")
     */
    public function reprendre(Request $request, Dossier $dossier): Response
    {
        /** @var string $csrfToken */
        $csrfToken = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('reprendre_prescriptions_'.$dossier->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Token de sécurité invalide. Veuillez réessayer.');
        }

        $dossierIds = $request->request->get('dossierIds', []);
        $dossierIds = is_array($dossierIds) ? array_map(function ($v): int { return (int) (is_scalar($v) ? $v : 0); }, $dossierIds) : [];
        $dossierIds = array_filter($dossierIds);

        if ([] === $dossierIds) {
            $this->addFlash('warning', 'Aucun dossier sélectionné.');

            return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
        }

        $sources = array_filter(
            array_map(function (int $id) { return $this->dossierRepo->find($id); }, $dossierIds)
        );

        try {
            $prescriptionsReprises = $this->service->reprendrePrescriptions($dossier, array_values($sources));
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la reprise des prescriptions.', [
                'message' => $exception->getMessage(),
                'dossier' => $dossier->getId(),
            ]);

            $this->addFlash('danger', 'Une erreur est survenue lors de la reprise des prescriptions. Veuillez réessayer.');

            return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
        }

        $this->addFlash('success', \sprintf('%d prescription(s) reprises depuis %d dossier(s).',
            $prescriptionsReprises->nombrePrescriptions(),
            $prescriptionsReprises->nombreDossiers()
        ));

        return $this->redirectToRoute('dossier_prescriptions', ['id' => $dossier->getId()]);
    }
}

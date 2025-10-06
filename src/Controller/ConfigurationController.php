<?php

namespace App\Controller;

use App\Entity\CommissionType;
use App\Entity\Configurations;
use App\Form\ConfigurationType;
use App\Repository\ConfigurationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationController extends AbstractController
{
    /**
     * @Route("/admin/configuration", name="admin_configuration")
     */
    public function index(Request $request, EntityManagerInterface $entityManager, ConfigurationsRepository $configurationsRepository): Response
    {
        $configuration = $configurationsRepository->getSingleConfiguration();
        if (!$configuration) {
            $configuration = new Configurations();
        }

        $commissionTypeId = $this->getParameter('app.commission_communale_type');

        if ($commissionTypeId) {
            $commissionType = $entityManager->getRepository(CommissionType::class)->find($commissionTypeId);
            if (!$commissionType) {
                throw new \Exception('CommissionType introuvable pour l\'ID ' . $commissionTypeId);
            }
        }

        $configuration->setPrevariscDebugEnabled($this->getParameter('app.debug'));
        $configuration->setPrevariscDashboardCourrierSansReponseDays($this->getParameter('app.dashboard_courrier_sans_reponse_days'));
        $configuration->setPrevariscDashboardDossiersSansAvisDays($this->getParameter('app.dashboard_dossiers_sans_avis_days'));
        $configuration->setPrevariscDashboardNextCommissionsDays($this->getParameter('app.dashboard_next_commissions_days'));
        $configuration->setPrevariscDeactivatePlatau($this->getParameter('app.deactivate_platau'));
        $configuration->setPrevariscDateCommissionRelancePeriodicite($this->getParameter('app.relance_periodicite_commission'));
        $configuration->setPrevariscDescriptifPersonnalise($this->getParameter('app.descriptif_personnalise'));
        $configuration->setPrevariscDossiersMaxAAfficher($this->getParameter('app.nb_dossiers_a_afficher'));
        $configuration->setPrevariscEnforceSecurity($this->getParameter('app.enforce_security'));
        $configuration->setPrevariscRisquesNaturelsExemples($this->getParameter('app.risques_naturels_exemples'));
        $configuration->setPrevariscRisquesTechnologiquesExemples($this->getParameter('app.risques_technologiques_exemples'));
        $configuration->setPrevariscPluginIgnkey($this->getParameter('app.ign_key'));
        $configuration->setPrevariscPlatauNoHealthcheck($this->getParameter('app.platau_no_healthcheck'));
        $configuration->setPrevariscLocalSommeilTypes($this->getParameter('app.local_sommeil_types'));
        $configuration->setPrevariscUnitePeriodiciteAnnees($this->getParameter('app.periodicite_annees'));

        $form = $this->createForm(ConfigurationType::class, $configuration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configuration);
            $entityManager->flush();

            $this->addFlash('success', 'Configuration mise à jour avec succès.');
            return $this->redirectToRoute('admin_configuration');
        }

        return $this->render('configuration/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

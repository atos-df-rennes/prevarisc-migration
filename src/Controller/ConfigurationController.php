<?php

namespace App\Controller;

use App\Entity\Configuration;
use App\Repository\ConfigurationRepository;
use App\Service\ConfigurationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationController extends AbstractController
{
    /**
     * @Route("/admin/configuration", name="admin_configuration")
     */
    public function index(
        ConfigurationRepository $repository,
        ConfigurationService $configService
    ): Response {
        $configurations = $repository->findAll();

        $configsWithInfo = array_map(function ($config) use ($configService) {
            return [
                'config' => $config,
                'info'   => $configService->getConfigInfo($config->getConfigKey()),
            ];
        }, $configurations);

        // Tri par catégorie puis par label
        usort($configsWithInfo, function ($a, $b) {
            $categoryA = $a['info']['category'] ?? 'Autres';
            $categoryB = $b['info']['category'] ?? 'Autres';

            if ($categoryA === $categoryB) {
                return strcmp($a['info']['label'], $b['info']['label']);
            }

            return strcmp($categoryA, $categoryB);
        });

        return $this->render('admin/configuration/index.html.twig', [
            'configsWithInfo' => $configsWithInfo,
        ]);
    }

    /**
     * @Route("/admin/configuration/toggle-boolean/{id}", name="admin_configuration_toggle_boolean", methods={"POST"})
     */
    public function toggleBoolean(
        Configuration $configuration,
        ConfigurationService $service
    ): Response {
        $newValue = ($configuration->getValue() === '1') ? '0' : '1';

        $success = $service->updateConfigurationValue(
            $configuration->getConfigKey(),
            $newValue
        );

        return $this->json([
            'success'  => $success,
            'newValue' => $newValue,
        ], $success ? 200 : 500);
    }

    /**
     * @Route("/admin/configuration/update-text/{id}", name="admin_configuration_update_text", methods={"POST"})
     */
    public function updateText(
        Request $request,
        Configuration $configuration,
        ConfigurationService $service
    ): Response {
        $newValue = $request->request->get('value', '');

        $success = $service->updateConfigurationValue(
            $configuration->getConfigKey(),
            $newValue
        );

        $this->addFlash(
            $success ? 'success' : 'error',
            $success
                ? 'Configuration mise à jour avec succès.'
                : 'Erreur lors de la mise à jour.'
        );

        return $this->redirectToRoute('admin_configuration');
    }
}

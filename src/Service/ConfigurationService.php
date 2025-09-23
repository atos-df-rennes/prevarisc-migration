<?php

namespace App\Service;

use App\Entity\Configuration;
use App\Repository\ConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class ConfigurationService
{
    private $configurationRepository;
    private $entityManager;
    private $logger;

    public function __construct(
        ConfigurationRepository $configurationRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->configurationRepository = $configurationRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function initializeDefaultConfigurations(): void
    {
        foreach (array_keys($this->getConfigMetadata()) as $key) {
            $this->createConfigIfNotExists($key, $this->getConfigMetadata()[$key]['default'] ?? '');
        }
    }

    public function getConfigurationValue(string $key, $defaultValue = null): ?string
    {
        $config = $this->configurationRepository->findOneBy(['configKey' => $key]);
        return $config ? $config->getValue() : $defaultValue;
    }

    public function updateConfigurationValue(string $key, $value): bool
    {
        try {
            $config = $this->configurationRepository->findOneBy(['configKey' => $key]);

            if ($config) {
                $config->setValue($this->convertValueToString($value));
                $this->entityManager->flush();
                return true;
            }
        } catch (\Throwable $e) {
            $this->logger->error("Erreur lors de la mise à jour du paramètre {$key}", [
                'exception' => $e
            ]);
        }

        return false;
    }

    public function getConfigInfo(string $key): array
    {
        $metadata = $this->getConfigMetadata();

        return $metadata[$key] ?? [
            'label' => $key,
            'type' => 'text',
            'category' => 'Autres'
        ];
    }

    private function createConfigIfNotExists(string $key, string $defaultValue): void
    {
        try {
            $existingConfig = $this->configurationRepository->findOneBy(['configKey' => $key]);

            if (!$existingConfig) {
                $config = new Configuration();
                $config->setConfigKey($key);
                $config->setValue($defaultValue);
                $this->entityManager->persist($config);
                $this->entityManager->flush();
            }
        } catch (\Throwable $e) {
            $this->logger->error("Erreur lors de l'initialisation du paramètre {$key}", [
                'exception' => $e
            ]);
        }
    }

    private function convertValueToString($value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }

    private function getConfigMetadata(): array
    {
        return [
                // Debug et Sécurité
                'PREVARISC_DEBUG_ENABLED' => [
                    'label' => 'Mode Debug',
                    'type' => 'boolean',
                    'category' => 'Debug et Sécurité',
                    'description' => 'Active ou désactive le mode debug de l\'application'
                ],
                'PREVARISC_ENFORCE_SECURITY' => [
                    'label' => 'Sécurité renforcée',
                    'type' => 'boolean',
                    'category' => 'Debug et Sécurité',
                    'description' => 'Active ou désactive les contrôles de sécurité renforcés'
                ],

                // Plugins
                'PREVARISC_PLUGIN_IGNKEY' => [
                    'label' => 'Clés de plugins ignorées',
                    'type' => 'text',
                    'category' => 'Plugins',
                    'description' => 'Liste des clés de plugins à ignorer (séparées par des virgules)',
                    'placeholder' => 'essentiels,adresse,administratif,cartes,cartovecto'
                ],

                // Types et Classifications
                'PREVARISC_LOCAL_SOMMEIL_TYPES' => [
                    'label' => 'Types de locaux à sommeil',
                    'type' => 'text',
                    'category' => 'Types et Classifications',
                    'description' => 'Types de locaux à sommeil (séparés par des points-virgules)',
                    'placeholder' => '1;5'
                ],
                'PREVARISC_COMMISSION_COMMUNALE_TYPE' => [
                    'label' => 'Type de commission communale',
                    'type' => 'text',
                    'category' => 'Types et Classifications',
                    'description' => 'Type de commission communale par défaut',
                    'placeholder' => 'Entrez le type de commission'
                ],

                // Risques
                'PREVARISC_RISQUES_NATURELS_EXEMPLES' => [
                    'label' => 'Exemples de risques naturels',
                    'type' => 'textarea',
                    'category' => 'Risques',
                    'description' => 'Liste des exemples de risques naturels (séparés par des virgules)',
                    'placeholder' => 'Avalanche, Cyclonique, Feux de Forêt, Inondation...'
                ],
                'PREVARISC_RISQUES_TECHNOLOGIQUES_EXEMPLES' => [
                    'label' => 'Exemples de risques technologiques',
                    'type' => 'textarea',
                    'category' => 'Risques',
                    'description' => 'Liste des exemples de risques technologiques (séparés par des virgules)',
                    'placeholder' => 'Industriel, Nucléaire, Rupture de barrage...'
                ],

                // Dashboard et Affichage
                'PREVARISC_DASHBOARD_NEXT_COMMISSIONS_DAYS' => [
                    'label' => 'Jours pour prochaines commissions',
                    'type' => 'number',
                    'category' => 'Dashboard',
                    'description' => 'Nombre de jours pour afficher les prochaines commissions sur le dashboard',
                    'min' => 1,
                    'max' => 365
                ],
                'PREVARISC_DASHBOARD_DOSSIERS_SANS_AVIS_DAYS' => [
                    'label' => 'Jours pour dossiers sans avis',
                    'type' => 'number',
                    'category' => 'Dashboard',
                    'description' => 'Nombre de jours pour afficher les dossiers sans avis sur le dashboard',
                    'min' => 1,
                    'max' => 365
                ],
                'PREVARISC_DASHBOARD_COURRIER_SANS_REPONSE_DAYS' => [
                    'label' => 'Jours pour courrier sans réponse',
                    'type' => 'number',
                    'category' => 'Dashboard',
                    'description' => 'Nombre de jours pour afficher les courriers sans réponse sur le dashboard',
                    'min' => 1,
                    'max' => 365
                ],
                'PREVARISC_DOSSIERS_MAX_A_AFFICHER' => [
                    'label' => 'Maximum de dossiers à afficher',
                    'type' => 'number',
                    'category' => 'Dashboard',
                    'description' => 'Nombre maximum de dossiers à afficher dans les listes',
                    'min' => 1,
                    'max' => 100
                ],

                // Commissions et Périodicité
                'PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE' => [
                    'label' => 'Périodicité de relance des commissions',
                    'type' => 'number',
                    'category' => 'Commissions',
                    'description' => 'Périodicité en jours pour les relances de commission (0 = désactivé)',
                    'min' => 0,
                    'max' => 365
                ],
                'PREVARISC_UNITE_PERIODICITE_ANNEES' => [
                    'label' => 'Unité de périodicité en années',
                    'type' => 'boolean',
                    'category' => 'Commissions',
                    'description' => 'Utiliser les années comme unité de périodicité'
                ],

                // Fonctionnalités
                'PREVARISC_DESCRIPTIF_PERSONNALISE' => [
                    'label' => 'Descriptif personnalisé',
                    'type' => 'boolean',
                    'category' => 'Fonctionnalités',
                    'description' => 'Active ou désactive les descriptifs personnalisés'
                ],
                'PREVARISC_DEACTIVATE_PLATAU' => [
                    'label' => 'Désactiver Platau',
                    'type' => 'boolean',
                    'category' => 'Fonctionnalités',
                    'description' => 'Active ou désactive l\'intégration Platau'
                ]
            ];
    }
}

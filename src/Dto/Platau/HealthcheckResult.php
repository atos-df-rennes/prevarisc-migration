<?php

namespace App\Dto\Platau;

use App\Constant\Platau\HealthcheckErrorSource;

/**
 * Résultat du healthcheck Plat'AU, prêt à être sérialisé en JSON.
 *
 * Deux origines d'erreur possibles (voir HealthcheckErrorSource) :
 *  - PREVARISC : Prevarisc ne peut pas contacter Plat'AU (config, réseau, token)
 *  - PLATAU    : Plat'AU a répondu mais signale un problème (état dégradé, HTTP non-2xx)
 */
final readonly class HealthcheckResult implements \JsonSerializable
{
    /**
     * @param bool                 $success         True si le healthcheck a réussi
     * @param int|null             $errorSource     HealthcheckErrorSource::PREVARISC ou PLATAU
     * @param string|null          $message         Message HTML prêt à l'emploi pour le toast (échappé XSS)
     * @param VersionInfo|null     $versionInfo     Informations de version si disponibles
     * @param MaintenanceInfo|null $maintenanceInfo Informations de maintenance si disponibles
     */
    private function __construct(
        private bool $success,
        private ?int $errorSource,
        private ?string $message,
        private ?VersionInfo $versionInfo,
        private ?MaintenanceInfo $maintenanceInfo
    ) {
    }

    /**
     * Healthcheck réussi : Plat'AU est disponible.
     */
    public static function success(VersionInfo $versionInfo, ?MaintenanceInfo $maintenanceInfo): self
    {
        $message = "La connexion à Plat'AU a fonctionnée.";

        $versionParts = array_filter([
            null !== $versionInfo->getVersion()
                ? htmlspecialchars($versionInfo->getVersion(), ENT_QUOTES, 'UTF-8')
                : null,
            null !== $versionInfo->getVersionModel()
                ? 'Modèle : '.htmlspecialchars($versionInfo->getVersionModel(), ENT_QUOTES, 'UTF-8')
                : null,
            null !== $versionInfo->getVersionNomenclature()
                ? 'Nomenclature : '.htmlspecialchars($versionInfo->getVersionNomenclature(), ENT_QUOTES, 'UTF-8')
                : null,
        ]);

        if (!empty($versionParts)) {
            $message .= '<br><small>'.implode(' / ', $versionParts).'</small>';
        }

        return new self(true, null, $message, $versionInfo, $maintenanceInfo);
    }

    /**
     * Erreur interne Prevarisc : configuration, réseau, token PISTE indisponible.
     */
    public static function prevariscError(string $reason): self
    {
        return new self(
            false,
            HealthcheckErrorSource::PREVARISC,
            htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'),
            null,
            null
        );
    }

    /**
     * Erreur côté Plat'AU : état dégradé ou code HTTP non-2xx.
     * La version et les informations de maintenance sont incluses si disponibles.
     */
    public static function platauError(string $reason, ?VersionInfo $versionInfo, ?MaintenanceInfo $maintenanceInfo): self
    {
        return new self(
            false,
            HealthcheckErrorSource::PLATAU,
            htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'),
            $versionInfo,
            $maintenanceInfo
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrorSource(): ?int
    {
        return $this->errorSource;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getVersionInfo(): ?VersionInfo
    {
        return $this->versionInfo;
    }

    public function getMaintenanceInfo(): ?MaintenanceInfo
    {
        return $this->maintenanceInfo;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'errorSource' => $this->errorSource,
            'message' => $this->message,
            'version' => $this->versionInfo,
            'maintenance' => $this->maintenanceInfo,
        ];
    }
}

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
final class HealthcheckResult implements \JsonSerializable
{
    /** @var bool */
    private $success;

    /** @var int|null HealthcheckErrorSource::PREVARISC ou PLATAU */
    private $errorSource;

    /** @var string|null Message HTML prêt à l'emploi pour le toast (échappé XSS) */
    private $message;

    /** @var VersionInfo|null */
    private $version;

    /** @var MaintenanceInfo|null */
    private $maintenance;

    private function __construct(
        bool $success,
        ?int $errorSource,
        ?string $message,
        ?VersionInfo $version,
        ?MaintenanceInfo $maintenance
    ) {
        $this->success = $success;
        $this->errorSource = $errorSource;
        $this->message = $message;
        $this->version = $version;
        $this->maintenance = $maintenance;
    }

    /**
     * Healthcheck réussi : Plat'AU est disponible.
     */
    public static function success(VersionInfo $version, ?MaintenanceInfo $maintenance): self
    {
        $message = "La connexion à Plat'AU a fonctionnée.";

        $versionParts = array_filter([
            null !== $version->getVersion()
                ? htmlspecialchars($version->getVersion(), ENT_QUOTES, 'UTF-8')
                : null,
            null !== $version->getVersionModel()
                ? 'Modèle : '.htmlspecialchars($version->getVersionModel(), ENT_QUOTES, 'UTF-8')
                : null,
            null !== $version->getVersionNomenclature()
                ? 'Nomenclature : '.htmlspecialchars($version->getVersionNomenclature(), ENT_QUOTES, 'UTF-8')
                : null,
        ]);

        if (!empty($versionParts)) {
            $message .= '<br><small>'.implode(' / ', $versionParts).'</small>';
        }

        return new self(true, null, $message, $version, $maintenance);
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
    public static function platauError(string $reason, ?VersionInfo $version, ?MaintenanceInfo $maintenance): self
    {
        return new self(
            false,
            HealthcheckErrorSource::PLATAU,
            htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'),
            $version,
            $maintenance
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

    public function getVersion(): ?VersionInfo
    {
        return $this->version;
    }

    public function getMaintenance(): ?MaintenanceInfo
    {
        return $this->maintenance;
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
            'version' => $this->version,
            'maintenance' => $this->maintenance,
        ];
    }
}

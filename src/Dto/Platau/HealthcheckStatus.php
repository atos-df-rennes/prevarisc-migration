<?php

namespace App\Dto\Platau;

/**
 * Résultat du healthcheck Plat'AU conservé en cache, avec l'heure de vérification et celle d'expiration du cache.
 */
final readonly class HealthcheckStatus
{
    public const string STATUS_OK = 'ok';

    public const string STATUS_WARNING = 'warning';

    public const string STATUS_ERROR = 'error';

    private const array STATUS_LABELS = [
        self::STATUS_OK => "Plat'AU est disponible",
        self::STATUS_WARNING => "Plat'AU est disponible (maintenance annoncée)",
        self::STATUS_ERROR => "Plat'AU est indisponible",
    ];

    public function __construct(
        private HealthcheckResult $result,
        private \DateTimeImmutable $checkedAt,
        private \DateTimeImmutable $expiresAt
    ) {
    }

    public function getResult(): HealthcheckResult
    {
        return $this->result;
    }

    public function getCheckedAt(): \DateTimeImmutable
    {
        return $this->checkedAt;
    }

    /**
     * Expiration du résultat en cache : il ne s'agit pas de l'heure de la prochaine vérification,
     * qui n'aura lieu qu'au prochain affichage de page après cette échéance.
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Vert : disponible ; orange : disponible avec maintenance ; rouge : erreur Plat'AU ou Prevarisc.
     */
    public function getStatus(): string
    {
        if (!$this->result->isSuccess()) {
            return self::STATUS_ERROR;
        }

        return $this->result->getMaintenanceInfo() instanceof MaintenanceInfo ? self::STATUS_WARNING : self::STATUS_OK;
    }

    public function getLabel(): string
    {
        return self::STATUS_LABELS[$this->getStatus()];
    }
}

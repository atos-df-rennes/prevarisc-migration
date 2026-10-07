<?php

namespace App\Twig;

use App\Dto\Platau\HealthcheckStatus;
use App\Service\Platau\PlatauHealthcheckStatusProvider;
use Twig\Attribute\AsTwigFunction;

class PlatauHealthcheckExtension
{
    public function __construct(
        private readonly bool $deactivatePlatau,
        private readonly bool $platauNoHealthcheck,
        private readonly PlatauHealthcheckStatusProvider $statusProvider
    ) {
    }

    /**
     * Indique si l'indicateur de disponibilité Plat'AU doit être affiché dans l'en-tête.
     */
    #[AsTwigFunction(name: 'platau_healthcheck_enabled')]
    public function isEnabled(): bool
    {
        return !$this->deactivatePlatau && !$this->platauNoHealthcheck;
    }

    /**
     * État de Plat'AU déjà en cache, sans déclencher de vérification (null si le cache a expiré).
     */
    #[AsTwigFunction(name: 'platau_healthcheck_cached_status')]
    public function getCachedStatus(): ?HealthcheckStatus
    {
        return $this->isEnabled() ? $this->statusProvider->getCachedStatus() : null;
    }
}

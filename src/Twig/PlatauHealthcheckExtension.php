<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFunction;

class PlatauHealthcheckExtension
{
    public function __construct(private readonly bool $deactivatePlatau, private readonly bool $platauNoHealthcheck)
    {
    }

    /**
     * Indique si l'indicateur de disponibilité Plat'AU doit être affiché dans l'en-tête.
     */
    #[AsTwigFunction(name: 'platau_healthcheck_enabled')]
    public function isEnabled(): bool
    {
        return !$this->deactivatePlatau && !$this->platauNoHealthcheck;
    }
}

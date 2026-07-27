<?php

namespace App\Twig;

use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PlatauExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('platau_pec_label', [$this, 'getPecLabel']),
            new TwigFilter('platau_pec_class', [$this, 'getPecClass']),
            new TwigFilter('platau_avis_label', [$this, 'getAvisLabel']),
            new TwigFilter('platau_avis_class', [$this, 'getAvisClass']),
        ];
    }

    public function getPecLabel(?string $statut): string
    {
        return PlatauStatutPecEnum::getLabel($statut);
    }

    public function getPecClass(?string $statut): string
    {
        return match ($statut) {
            PlatauStatutPecEnum::EN_ATTENTE => 'text-info',
            PlatauStatutPecEnum::PRISE_EN_COMPTE => 'text-success',
            PlatauStatutPecEnum::A_RENVOYER => 'text-warning',
            PlatauStatutPecEnum::EN_ERREUR => 'text-danger',
            default => 'text-muted',
        };
    }

    public function getAvisLabel(?string $statut): string
    {
        return PlatauStatutAvisEnum::getLabel($statut);
    }

    public function getAvisClass(?string $statut): string
    {
        return match ($statut) {
            PlatauStatutAvisEnum::EN_COURS => 'text-info',
            PlatauStatutAvisEnum::TRAITE => 'text-success',
            PlatauStatutAvisEnum::A_RENVOYER => 'text-warning',
            PlatauStatutAvisEnum::EN_ERREUR => 'text-danger',
            default => 'text-muted',
        };
    }
}

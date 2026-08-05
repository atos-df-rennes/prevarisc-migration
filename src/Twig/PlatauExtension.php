<?php

namespace App\Twig;

use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;
use Twig\Attribute\AsTwigFilter;

class PlatauExtension
{
    #[AsTwigFilter(name: 'platau_pec_label')]
    public function getPecLabel(?string $statut): string
    {
        return PlatauStatutPecEnum::getLabel($statut);
    }

    #[AsTwigFilter(name: 'platau_pec_class')]
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

    #[AsTwigFilter(name: 'platau_avis_label')]
    public function getAvisLabel(?string $statut): string
    {
        return PlatauStatutAvisEnum::getLabel($statut);
    }

    #[AsTwigFilter(name: 'platau_avis_class')]
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

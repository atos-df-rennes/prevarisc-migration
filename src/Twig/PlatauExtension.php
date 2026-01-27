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
        switch ($statut) {
            case PlatauStatutPecEnum::EN_ATTENTE:
                return 'text-info';
            case PlatauStatutPecEnum::PRISE_EN_COMPTE:
                return 'text-success';
            case PlatauStatutPecEnum::A_RENVOYER:
                return 'text-warning';
            case PlatauStatutPecEnum::EN_ERREUR:
                return 'text-danger';
            default:
                return 'text-muted';
        }
    }

    public function getAvisLabel(?string $statut): string
    {
        return PlatauStatutAvisEnum::getLabel($statut);
    }

    public function getAvisClass(?string $statut): string
    {
        switch ($statut) {
            case PlatauStatutAvisEnum::EN_COURS:
                return 'text-info';
            case PlatauStatutAvisEnum::TRAITE:
                return 'text-success';
            case PlatauStatutAvisEnum::A_RENVOYER:
                return 'text-warning';
            case PlatauStatutAvisEnum::EN_ERREUR:
                return 'text-danger';
            default:
                return 'text-muted';
        }
    }
}

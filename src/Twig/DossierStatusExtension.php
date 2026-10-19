<?php

namespace App\Twig;

use App\Constant\Avis;
use App\Constant\DossierNature;
use App\Constant\DossierType;
use App\Dto\Repository\AbstractDossierResultDto;
use App\Service\DateDiffFormatterService;
use Twig\Attribute\AsTwigFilter;

final readonly class DossierStatusExtension
{
    public function __construct(private DateDiffFormatterService $dateDiffFormatter)
    {
    }

    /**
     * Retourne le badge HTML d'avis pour un dossier dans les résultats de recherche.
     */
    #[AsTwigFilter(name: 'dossier_avis_badge', isSafe: ['html'])]
    public function renderAvisBadge(AbstractDossierResultDto $item): string
    {
        // INCOMPLET prioritaire
        if (1 === $this->mixedToInt($item->INCOMPLET_DOSSIER)) {
            return '<span class="avis" style="background-color: #f89406;">Dossier incomplet</span>';
        }

        if (1 === $this->mixedToInt($item->DIFFEREAVIS_DOSSIER)) {
            return '<span class="avis" style="background-color: #3a87ad;">Avis différé</span>';
        }

        // Dossier courrier (TYPE_DOSSIER == 5)
        if (DossierType::COURRIER === $this->mixedToInt($item->TYPE_DOSSIER)) {
            if (!empty($item->DATEREP_DOSSIER)) {
                return '<span class="avis F">Répondu</span>';
            }

            $dateDiff = '';
            if (!empty($item->DATESDIS_DOSSIER)) {
                $diff = $this->dateDiffFormatter->format($this->mixedToString($item->DATESDIS_DOSSIER));
                if ('' !== $diff) {
                    $dateDiff = ' ('.htmlspecialchars($diff, ENT_QUOTES, 'UTF-8').')';
                }
            }

            return '<span class="avis">Réponse à faire'.$dateDiff.'</span>';
        }

        $idAvis = $this->mixedToInt($item->ID_AVIS);
        $exploitation = $this->isExploitationNature($item);

        if (Avis::FAVORABLE === $idAvis) {
            return '<span class="avis F">Favorable'.($exploitation ? ' à l\'exploitation' : '').'</span>';
        }

        if (Avis::DEFAVORABLE === $idAvis) {
            return '<span class="avis D">Défavorable'.($exploitation ? ' à l\'exploitation' : '').'</span>';
        }

        return '<span class="avis">Avis indisponible</span>';
    }

    /**
     * Détermine si la nature du dossier correspond à une nature d'exploitation.
     */
    private function isExploitationNature(AbstractDossierResultDto $item): bool
    {
        return null !== $item->ID_DOSSIERNATURE
            && in_array($this->mixedToInt($item->ID_DOSSIERNATURE), DossierNature::NATURES_DONNANT_AVIS, true);
    }

    /**
     * Convertit une valeur mixte en entier de façon sûre pour PHPStan niveau 10.
     *
     * @param mixed $value
     */
    private function mixedToInt($value): int
    {
        return \is_scalar($value) ? (int) $value : 0;
    }

    /**
     * Convertit une valeur mixte en chaîne de façon sûre pour PHPStan niveau 10.
     *
     * @param mixed $value
     */
    private function mixedToString($value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }
}

<?php

namespace App\Twig;

use App\Constant\Avis;
use App\Constant\DossierType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DossierStatusExtension extends AbstractExtension
{
    /** @var SearchExtension */
    private $searchExtension;

    public function __construct(SearchExtension $searchExtension)
    {
        $this->searchExtension = $searchExtension;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('dossier_avis_badge', [$this, 'renderAvisBadge'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Retourne le badge HTML d'avis pour un dossier dans les résultats de recherche.
     *
     * @param array<string, mixed> $item
     */
    public function renderAvisBadge(array $item): string
    {
        // INCOMPLET prioritaire
        if (isset($item['INCOMPLET_DOSSIER']) && 1 === $this->mixedToInt($item['INCOMPLET_DOSSIER'])) {
            return '<span class="avis" style="background-color: #f89406;">Dossier incomplet</span>';
        }

        if (isset($item['DIFFEREAVIS_DOSSIER']) && 1 === $this->mixedToInt($item['DIFFEREAVIS_DOSSIER'])) {
            return '<span class="avis" style="background-color: #3a87ad;">Avis différé</span>';
        }

        // Dossier courrier (TYPE_DOSSIER == 5)
        if (isset($item['TYPE_DOSSIER']) && DossierType::COURRIER === $this->mixedToInt($item['TYPE_DOSSIER'])) {
            if (!empty($item['DATEREP_DOSSIER'])) {
                return '<span class="avis F">Répondu</span>';
            }

            $dateDiff = '';
            if (!empty($item['DATESDIS_DOSSIER'])) {
                $diff = $this->searchExtension->formatDateDiff($this->mixedToString($item['DATESDIS_DOSSIER']));
                if ('' !== $diff) {
                    $dateDiff = ' ('.htmlspecialchars($diff, ENT_QUOTES, 'UTF-8').')';
                }
            }

            return '<span class="avis">Réponse à faire'.$dateDiff.'</span>';
        }

        $idAvis = isset($item['ID_AVIS']) ? $this->mixedToInt($item['ID_AVIS']) : 0;
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
     * Liste des IDs de natures "exploitation" (union des deux templates, normalisée).
     *
     * @param array<string, mixed> $item
     */
    private function isExploitationNature(array $item): bool
    {
        $exploitationNatureIds = [7, 16, 17, 19, 21, 23, 24, 26, 28, 29, 47, 48];

        return isset($item['ID_DOSSIERNATURE'])
            && in_array($this->mixedToInt($item['ID_DOSSIERNATURE']), $exploitationNatureIds, true);
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

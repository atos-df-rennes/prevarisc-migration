<?php

namespace App\Twig;

use App\Entity\Dossier;
use App\Service\DossierFields;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DossierExtension extends AbstractExtension
{
    /** @var DossierFields */
    private $dossierFieldsService;

    public function __construct(DossierFields $dossierFieldsService)
    {
        $this->dossierFieldsService = $dossierFieldsService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('should_display_field', [$this, 'shouldDisplayField']),
        ];
    }

    public function shouldDisplayField(Dossier $dossier, string $fieldName): bool
    {
        return $this->dossierFieldsService->shouldDisplayField($dossier, $fieldName);
    }
}

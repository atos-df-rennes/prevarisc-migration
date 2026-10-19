<?php

namespace App\Twig;

use App\Entity\Dossier;
use App\Service\DossierFields;
use Twig\Attribute\AsTwigFilter;

final class DossierExtension
{
    public function __construct(private readonly DossierFields $dossierFieldsService)
    {
    }

    #[AsTwigFilter(name: 'should_display_field')]
    public function shouldDisplayField(Dossier $dossier, string $fieldName): bool
    {
        return $this->dossierFieldsService->shouldDisplayField($dossier, $fieldName);
    }
}

<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFunction;

class IconeTypeEtablissementExtension
{
    public function __construct(private readonly string $srcDir)
    {
    }

    #[AsTwigFunction(name: 'icone_type_etablissement_exists')]
    public function iconeTypeEtablissementExists(string $typeEtablissement): bool
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->srcDir,
            'public',
            'images',
            'types',
            'b',
            'icone-type-'.$typeEtablissement.'.png'
        ]);

        return file_exists($filepath);
    }
}

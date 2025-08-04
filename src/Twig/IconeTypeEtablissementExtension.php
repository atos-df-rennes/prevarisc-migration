<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IconeTypeEtablissementExtension extends AbstractExtension {
    /** @var string */
    private $srcDir;

    public function __construct(string $srcDir) {
        $this->srcDir = $srcDir;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('icone_type_etablissement_exists', [$this, 'iconeTypeEtablissementExists']),
        ];
    }

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

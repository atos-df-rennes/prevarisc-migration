<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Attribute\AsTwigFunction;

class AssetExistExtension
{
    public function __construct(private readonly Packages $packages, private readonly string $srcDir)
    {
    }

    #[AsTwigFunction(name: 'asset_exist')]
    public function assetExist(string $assetPath): bool
    {
        $filepath = $this->packages->getUrl($assetPath);

        return file_exists($this->srcDir.'/public'.$filepath);
    }
}

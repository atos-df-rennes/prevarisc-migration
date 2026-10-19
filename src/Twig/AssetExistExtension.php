<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFunction;

final readonly class AssetExistExtension
{
    public function __construct(private string $srcDir)
    {
    }

    #[AsTwigFunction(name: 'asset_exist')]
    public function assetExist(string $assetPath): bool
    {
        return is_file($this->srcDir.'/public/'.ltrim($assetPath, '/'));
    }
}

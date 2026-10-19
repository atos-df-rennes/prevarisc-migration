<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFunction;

final class AssetExistExtension
{
    public function __construct(private readonly string $srcDir)
    {
    }

    #[AsTwigFunction(name: 'asset_exist')]
    public function assetExist(string $assetPath): bool
    {
        return is_file($this->srcDir.'/public/'.ltrim($assetPath, '/'));
    }
}

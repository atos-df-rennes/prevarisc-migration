<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExistExtension extends AbstractExtension
{
    public function __construct(private readonly Packages $packages, private readonly string $srcDir)
    {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_exist', $this->assetExist(...)),
        ];
    }

    public function assetExist(string $assetPath): bool
    {
        $filepath = $this->packages->getUrl($assetPath);

        return file_exists($this->srcDir.'/public'.$filepath);
    }
}

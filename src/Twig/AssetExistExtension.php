<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExistExtension extends AbstractExtension
{
    /** @var Packages */
    private $packages;

    /** @var string */
    private $srcDir;

    public function __construct(Packages $packages, string $srcDir)
    {
        $this->packages = $packages;
        $this->srcDir = $srcDir;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_exist', [$this, 'assetExist']),
        ];
    }

    public function assetExist(string $assetPath): bool
    {
        $filepath = $this->packages->getUrl($assetPath);

        return file_exists($this->srcDir.'/public'.$filepath);
    }
}

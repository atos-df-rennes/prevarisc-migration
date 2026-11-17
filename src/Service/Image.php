<?php

namespace App\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;

final readonly class Image
{
    private Imagine $imagine;

    public function __construct()
    {
        $this->imagine = new Imagine();
    }

    public function resize(string $filename, int $width, ?int $height = null): ImageInterface
    {
        $image = $this->imagine->open($filename);

        $height ??= $this->calculateHeightFromWidth($image, $width);

        return $image->resize(new Box($width, $height));
    }

    private function calculateHeightFromWidth(ImageInterface $image, int $desiredWidth): int
    {
        $initialWidth = $image->getSize()->getWidth();
        $initialHeight = $image->getSize()->getHeight();

        return (int) (($initialHeight / $initialWidth) * $desiredWidth);
    }
}

<?php

namespace App\Services;

use Symfony\Component\Finder\Finder;

class Doc
{
    private $srcDir;

    public function __construct(string $srcDir)
    {
        $this->srcDir = $srcDir;
    }

    public function get(string $filename): string
    {
        $path = implode(DIRECTORY_SEPARATOR, [
            $this->srcDir,
            'docs',
            $filename.'.md',
        ]);

        return file_get_contents($path);
    }
}
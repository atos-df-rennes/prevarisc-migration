<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

final readonly class Doc
{
    public function __construct(private string $srcDir, private LoggerInterface $logger)
    {
    }

    public function get(string $filename): string
    {
        $path = implode(DIRECTORY_SEPARATOR, [
            $this->srcDir,
            'docs',
            $filename.'.md',
        ]);

        $contents = file_get_contents($path);

        if (false === $contents) {
            $this->logger->warning("Le contenu du fichier {filename} n'a pas pu être lu.", ['filename' => $filename]);

            return '';
        }

        return $contents;
    }
}

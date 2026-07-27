<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

final class Doc
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
            $this->logger->warning(sprintf("Le contenu du fichier %s n'a pas pu être lu.", $filename));

            return '';
        }

        return $contents;
    }
}

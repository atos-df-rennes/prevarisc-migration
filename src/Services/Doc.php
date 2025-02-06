<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

class Doc
{
    /** @var string */
    private $srcDir;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $srcDir, LoggerInterface $logger)
    {
        $this->srcDir = $srcDir;
        $this->logger = $logger;
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

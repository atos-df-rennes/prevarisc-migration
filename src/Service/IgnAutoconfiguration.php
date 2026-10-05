<?php

namespace App\Service;

use App\Utils\PathNormalizer;
use Psr\Log\LoggerInterface;

final class IgnAutoconfiguration
{
    /** @var string */
    private $autoconfPath;

    /** @var string */
    private $srcDir;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $autoconfPath, string $srcDir, LoggerInterface $logger)
    {
        $this->autoconfPath = $autoconfPath;
        $this->srcDir = $srcDir;
        $this->logger = $logger;
    }

    public function getPath(): string
    {
        if ('' === $this->autoconfPath) {
            return '';
        }

        $filePath = PathNormalizer::join($this->srcDir, 'public', $this->autoconfPath);

        if (!is_file($filePath)) {
            $this->logger->warning('Fichier d\'autoconfiguration IGN introuvable.', [
                'path' => $filePath,
            ]);

            return '';
        }

        return $this->autoconfPath;
    }
}

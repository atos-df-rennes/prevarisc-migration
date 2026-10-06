<?php

namespace App\Service;

use App\Utils\PathNormalizer;
use Psr\Log\LoggerInterface;

final readonly class IgnAutoconfiguration
{
    public function __construct(private string $autoconfPath, private string $srcDir, private LoggerInterface $logger)
    {
    }

    public function getPath(): string
    {
        if ('' === $this->autoconfPath) {
            return '';
        }

        $filePath = PathNormalizer::join($this->srcDir, 'public', $this->autoconfPath);

        if (!is_file($filePath)) {
            $this->logger->warning("Fichier d'autoconfiguration IGN introuvable.", [
                'path' => $filePath,
            ]);

            return '';
        }

        // Chemin relatif à la racine du site, même sans slash initial
        return '/'.ltrim($this->autoconfPath, '/');
    }
}

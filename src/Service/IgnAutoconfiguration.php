<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class IgnAutoconfiguration
{
    /** @var string */
    private $autoconfPath;

    /** @var string */
    private $srcDir;

    /** @var RequestStack */
    private $requestStack;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $autoconfPath, string $srcDir, RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->autoconfPath = $autoconfPath;
        $this->srcDir = $srcDir;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    public function getPath(): string
    {
        if ('' === $this->autoconfPath) {
            return '';
        }

        $request = $this->requestStack->getMasterRequest();
        $documentRoot = $request ? $request->server->get('DOCUMENT_ROOT') : null;
        $publicDir = is_string($documentRoot) && '' !== $documentRoot ? $documentRoot : $this->srcDir.'/public';
        $webPath = '/'.ltrim(str_replace('\\', '/', $this->autoconfPath), '/');
        $filePath = rtrim($publicDir, '/\\').$webPath;

        if (!is_file($filePath)) {
            $this->logger->warning('Fichier d\'autoconfiguration IGN introuvable.', [
                'path' => $filePath,
            ]);

            return '';
        }

        return $webPath;
    }
}

<?php

namespace App\Controller;

use App\Service\ProxyConfigurationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Relai HTTP côté serveur pour contourner les restrictions réseau (CORS, intranet SDIS).
 * Port direct de ProxyController Zend.
 */
class ProxyController extends AbstractController
{
    public function __construct(private readonly ProxyConfigurationService $proxyConfiguration, private readonly LoggerInterface $logger, private readonly bool $debugEnabled)
    {
    }

    /**
     * Reçoit une URL externe en paramètre et la relaie via cURL côté serveur.
     * Retransmet le header Content-Type et le status HTTP de la réponse externe.
     */
    #[Route(path: '/proxy/index', name: 'proxy_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // On forme la chaîne de paramètres en excluant les paramètres de routage
        $excluded = ['url', 'controller', 'action', 'module'];
        /** @var array<string, mixed> $queryAll */
        $queryAll = $request->query->all();
        $filteredParams = array_filter(
            $queryAll,
            static fn (string $key): bool => !in_array($key, $excluded, true),
            ARRAY_FILTER_USE_KEY
        );

        $queryString = !empty($filteredParams) ? '?'.http_build_query($filteredParams) : '';

        // URL cible
        $targetUrl = $request->query->get('url', '').$queryString;

        if ('' === $targetUrl) {
            return new Response('URL parameter required', Response::HTTP_BAD_REQUEST);
        }

        $ch = curl_init();
        if (false === $ch) {
            $this->logger->error('ProxyController: curl_init() failed');

            return new Response('cURL initialization failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debugEnabled);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $referer = $request->server->get('HTTP_REFERER', '');
        if (is_string($referer) && '' !== $referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        $this->proxyConfiguration->applyCurlOptions($ch);

        $data = curl_exec($ch);

        if (false === $data) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->logger->error('ProxyController: curl_exec() failed', ['error' => $error, 'url' => $targetUrl]);

            return new Response($error, Response::HTTP_BAD_GATEWAY);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $dataStr = is_string($data) ? $data : '';
        $headerBlock = substr($dataStr, 0, $headerSize);
        $headers = explode("\r\n", $headerBlock);
        $body = substr($dataStr, $headerSize);

        curl_close($ch);

        $contentType = null;
        foreach ($headers as $header) {
            if ($header && 0 !== preg_match('/^Content-Type/i', $header)) {
                $parts = explode(':', $header, 2);
                if (isset($parts[1])) {
                    $contentType = trim($parts[1]);
                }
            }
        }

        $statusCode = $httpCode > 0 ? $httpCode : Response::HTTP_OK;
        $response = new Response($body, $statusCode);
        if (null !== $contentType) {
            $response->headers->set('Content-Type', $contentType);
        }

        return $response;
    }
}

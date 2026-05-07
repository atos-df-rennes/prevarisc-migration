<?php

namespace App\Controller;

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
    /** @var bool */
    private $proxyEnabled;

    /** @var int */
    private $proxyProtocol;

    /** @var int */
    private $proxyPort;

    /** @var string */
    private $proxyHost;

    /** @var string */
    private $proxyUsername;

    /** @var string */
    private $proxyPassword;

    public function __construct(
        bool $proxyEnabled,
        int $proxyProtocol,
        int $proxyPort,
        string $proxyHost,
        string $proxyUsername,
        string $proxyPassword
    ) {
        $this->proxyEnabled = $proxyEnabled;
        $this->proxyProtocol = $proxyProtocol;
        $this->proxyPort = $proxyPort;
        $this->proxyHost = $proxyHost;
        $this->proxyUsername = $proxyUsername;
        $this->proxyPassword = $proxyPassword;
    }

    /**
     * Reçoit une URL externe en paramètre et la relaie via cURL côté serveur.
     * Retransmet le header Content-Type de la réponse externe.
     *
     * @Route("/proxy/index", name="proxy_index", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        // On forme la chaîne de paramètres
        $params = '';
        /** @var array<string, mixed> $queryAll */
        $queryAll = $request->query->all();
        foreach ($queryAll as $key => $value) {
            if (!in_array($key, ['url', 'controller', 'action', 'module'], true)) {
                $params .= $key.'='.str_replace(' ', '+', is_scalar($value) ? (string) $value : '').'&';
            }
        }

        if ('' !== $params && '0' !== $params) {
            $params = '?'.$params;
        }

        // Website url to open
        $rawUrl = $request->query->get('url', '');
        $daurl = (is_string($rawUrl) ? $rawUrl : '').$params;

        if ('' === $daurl) {
            return new Response('URL parameter required', Response::HTTP_BAD_REQUEST);
        }

        $ch = curl_init();
        if (false === $ch) {
            return new Response('cURL initialization failed');
        }

        curl_setopt($ch, CURLOPT_URL, $daurl);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $referer = $request->server->get('HTTP_REFERER', '');
        if (is_string($referer) && '' !== $referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        // are we under a proxy?
        if ($this->proxyEnabled) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxyProtocol);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxyPort);
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost);
            if ($this->proxyUsername) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyUsername.':'.$this->proxyPassword);
            }
        }

        $data = curl_exec($ch);

        $body = '';
        $contentType = null;

        if (false === $data) {
            $body = curl_error($ch);
        } else {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $dataStr = is_string($data) ? $data : '';
            $headerBlock = substr($dataStr, 0, $headerSize);
            $headers = explode("\r\n", $headerBlock);
            $body = substr($dataStr, $headerSize);

            curl_close($ch);

            foreach ($headers as $header) {
                if (
                    $header
                    && 0 !== preg_match('/^Content-Type/i', $header)
                ) {
                    $parts = explode(':', $header, 2);
                    if (isset($parts[1])) {
                        $contentType = trim($parts[1]);
                    }
                }
            }
        }

        $response = new Response($body);
        if (null !== $contentType) {
            $response->headers->set('Content-Type', $contentType);
        }

        return $response;
    }
}

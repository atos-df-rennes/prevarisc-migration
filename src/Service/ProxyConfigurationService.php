<?php

namespace App\Service;

/**
 * Configuration du proxy sortant cURL pour Prevarisc.
 * Centralise les paramètres PREVARISC_PROXY_* et leur application sur un handle cURL.
 */
class ProxyConfigurationService
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

    /**
     * @param int $proxyProtocol Constante cURL : 0 = CURLPROXY_HTTP, 5 = CURLPROXY_SOCKS5
     */
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

    public function isEnabled(): bool
    {
        return $this->proxyEnabled;
    }

    /**
     * Applique la configuration proxy sur un handle cURL si le proxy est activé.
     *
     * @param resource $ch Handle cURL
     */
    public function applyCurlOptions($ch): void
    {
        if (!$this->proxyEnabled) {
            return;
        }

        curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxyProtocol);
        curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxyPort);
        curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost);

        if ($this->proxyUsername) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyUsername.':'.$this->proxyPassword);
        }
    }

    /**
     * Retourne les options cURL proxy sous forme de tableau (pour curl_setopt_array).
     *
     * @return array<int, mixed>
     */
    public function getCurlOptions(): array
    {
        if (!$this->proxyEnabled) {
            return [];
        }

        $options = [
            CURLOPT_PROXYTYPE => $this->proxyProtocol,
            CURLOPT_PROXYPORT => $this->proxyPort,
            CURLOPT_PROXY => $this->proxyHost,
        ];

        if ($this->proxyUsername) {
            $options[CURLOPT_PROXYUSERPWD] = $this->proxyUsername.':'.$this->proxyPassword;
        }

        return $options;
    }
}

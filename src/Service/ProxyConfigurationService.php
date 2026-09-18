<?php

namespace App\Service;

/**
 * Configuration du proxy sortant cURL pour Prevarisc.
 * Centralise les paramètres PREVARISC_PROXY_* et leur application sur un handle cURL.
 */
class ProxyConfigurationService
{
    // @fixme: La variable a pour valeur "HTTP", ce qui n'est pas correct mais fonctionne avec le legacy car (int) "HTTP" = 0
    // Modifier l'env var processor pour utiliser int après modification de la configuration chez tous les SDIS
    // + constructeur et suppression du cast (int)
    private readonly int $proxyProtocol;

    /**
     * @param string $proxyProtocol Constante cURL : 0 = CURLPROXY_HTTP, 5 = CURLPROXY_SOCKS5 -> Legacy = "HTTP", à corriger
     */
    public function __construct(
        private readonly bool $proxyEnabled,
        string $proxyProtocol,
        private readonly int $proxyPort,
        private readonly string $proxyHost,
        private readonly string $proxyUsername,
        private readonly string $proxyPassword
    ) {
        $this->proxyProtocol = (int) $proxyProtocol;
    }

    public function isEnabled(): bool
    {
        return $this->proxyEnabled;
    }

    /**
     * Applique la configuration proxy sur un handle cURL si le proxy est activé.
     */
    public function applyCurlOptions(\CurlHandle $ch): void
    {
        if (!$this->proxyEnabled) {
            return;
        }

        curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxyProtocol);
        curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxyPort);
        curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost);

        if ('' !== $this->proxyUsername && '0' !== $this->proxyUsername) {
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

        if ('' !== $this->proxyUsername && '0' !== $this->proxyUsername) {
            $options[CURLOPT_PROXYUSERPWD] = $this->proxyUsername.':'.$this->proxyPassword;
        }

        return $options;
    }
}

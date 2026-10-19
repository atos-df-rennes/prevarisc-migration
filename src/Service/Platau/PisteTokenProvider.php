<?php

namespace App\Service\Platau;

use App\Exception\PisteTokenException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PisteTokenProvider
{
    private const string CACHE_KEY = 'platau_piste_token';

    private const int EXPIRATION_MARGIN_SECONDS = 300;

    private const string UNAVAILABLE_MESSAGE = "La connexion à Plat'AU depuis Prevarisc a échouée. Impossible d'obtenir un identifiant de connexion à PISTE. Veuillez consulter les logs de l'application.";

    public function __construct(
        private readonly PlatauGatewayConfig $config,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Oublie le token en cache (par exemple après un refus d'authentification de Plat'AU).
     */
    public function invalidate(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * @throws PisteTokenException
     */
    public function getToken(): string
    {
        /** @var string $token */
        $token = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item, bool &$save): string {
            $url = $this->config->getPisteTokenUrl();
            $clientId = $this->config->getPisteClientId();
            $clientSecret = $this->config->getPisteClientSecret();

            if (in_array(null, [$url, $clientId, $clientSecret], true)) {
                throw new PisteTokenException(self::UNAVAILABLE_MESSAGE);
            }

            try {
                $response = $this->httpClient->request('POST', $url, $this->config->getHttpClientOptions() + [
                    'body' => [
                        'scope' => 'openid',
                        'grant_type' => 'client_credentials',
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                    ],
                ]);
                $statusCode = $response->getStatusCode();
                $data = $response->toArray(false);
            } catch (TransportExceptionInterface|DecodingExceptionInterface $exception) {
                $this->logger->error('La récupération du token PISTE a échoué.', ['exception' => $exception]);

                throw new PisteTokenException(self::UNAVAILABLE_MESSAGE, $exception->getCode(), previous: $exception);
            }

            if (array_key_exists('error', $data)) {
                $this->logger->error('Erreur lors de la récupération du token PISTE.', ['error' => $data['error'], 'httpCode' => $statusCode]);

                throw new PisteTokenException(self::UNAVAILABLE_MESSAGE);
            }

            $token = $data['access_token'] ?? null;

            if (!is_string($token)) {
                $this->logger->error("Le token PISTE n'est pas disponible.", ['httpCode' => $statusCode]);

                throw new PisteTokenException("La connexion à Plat'AU depuis Prevarisc a échouée. L'identifiant de connexion à PISTE n'est pas lisible. Veuillez consulter les logs de l'application.");
            }

            $expiresIn = $data['expires_in'] ?? null;
            $ttl = is_numeric($expiresIn) ? max(0, (int) $expiresIn - self::EXPIRATION_MARGIN_SECONDS) : 0;
            $item->expiresAfter($ttl);
            $save = $ttl > 0;

            return $token;
        }, 0);

        return $token;
    }
}

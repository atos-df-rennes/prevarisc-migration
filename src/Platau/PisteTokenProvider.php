<?php

declare(strict_types=1);

namespace App\Platau;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PisteTokenProvider
{
    private string $cacheKey;

    public function __construct(
        #[Autowire(service: 'piste.client')] private HttpClientInterface $pisteClient,
        private PlatauHttp $http,
        #[Autowire(service: 'cache.platau_consultation_tokens')] private CacheInterface $cache,
        #[Autowire(env: 'PISTE_CLIENT_ID')] private string $clientId,
        #[Autowire(env: 'PISTE_CLIENT_SECRET')] #[\SensitiveParameter] private string $clientSecret,
        #[Autowire(env: 'PISTE_ACCESS_TOKEN_URL')] private string $tokenUrl,
        #[Autowire(param: 'kernel.environment')] string $environment,
    ) {
        $this->cacheKey = hash('sha256', implode("\0", [$environment, $tokenUrl, $clientId, $clientSecret]));
    }

    public function invalidate(): void
    {
        $this->cache->delete($this->cacheKey);
    }

    public function token(): string
    {
        if ('' === $this->clientId || '' === $this->clientSecret) {
            throw new PlatauException('Les identifiants client PISTE doivent être configurés.');
        }

        return $this->cache->get($this->cacheKey, function (ItemInterface $item, bool &$save): string {
            try {
                $client = $this->pisteClient->withOptions(['auth_basic' => [$this->clientId, $this->clientSecret]] + $this->http->options());
                $response = $client->request('POST', $this->tokenUrl, [
                    'body' => ['grant_type' => 'client_credentials'],
                    'extra' => ['trace_content' => false],
                ]);
                $data = $this->http->json($response, 'PISTE');
            } catch (TransportExceptionInterface) {
                throw new PlatauException('Impossible de communiquer avec PISTE.');
            }

            $token = $data['access_token'] ?? null;
            if (!is_string($token) || '' === trim($token)) {
                throw new PlatauException('La réponse PISTE ne contient pas de token utilisable.');
            }

            $ttl = is_numeric($data['expires_in'] ?? null) ? (int) $data['expires_in'] - 300 : 0;
            $save = $ttl > 0;
            if ($save) {
                $item->expiresAfter($ttl);
            }

            return $token;
        }, 0);
    }
}

<?php

declare(strict_types=1);

namespace App\Platau;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract readonly class PlatauApiReader
{
    public function __construct(
        #[Autowire(service: 'platau.client')] private HttpClientInterface $platauClient,
        private PisteTokenProvider $tokens,
        private PlatauHttp $http,
        #[Autowire(env: 'PLATAU_ID_ACTEUR_APPELANT')] private string $actorId,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<array-key, mixed>
     */
    protected function read(string $method, string $path, array $options): array
    {
        if ('' === $this->actorId) {
            throw new PlatauException("L'identifiant de l'acteur Plat'AU appelant doit être configuré.");
        }

        $token = $this->tokens->token();
        try {
            $response = $this->request($method, $path, $options, $token, ConsultationRetryStrategy::MAX_RETRIES);
            if (401 === $response->getStatusCode()) {
                $this->tokens->invalidate();
                $used = $response->getInfo('retry_count');
                $remaining = ConsultationRetryStrategy::MAX_RETRIES - (is_int($used) ? $used : 0);
                $response = $this->request($method, $path, $options, $this->tokens->token(), $remaining);
                if (401 === $response->getStatusCode()) {
                    $this->tokens->invalidate();
                }
            }

            return $this->http->json($response, "Plat'AU");
        } catch (TransportExceptionInterface) {
            throw new PlatauException("Impossible de communiquer avec Plat'AU.");
        }
    }

    /** @param array<string, mixed> $options */
    private function request(string $method, string $path, array $options, #[\SensitiveParameter] string $token, int $maxRetries): ResponseInterface
    {
        $client = $this->platauClient->withOptions(['auth_bearer' => $token] + $this->http->options());

        return $client->request($method, $path, [
            'headers' => ['Id-Acteur-Appelant' => $this->actorId],
            'extra' => ['trace_content' => false],
            'max_retries' => $maxRetries,
        ] + $options);
    }
}

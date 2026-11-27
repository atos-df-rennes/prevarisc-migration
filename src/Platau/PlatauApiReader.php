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
        #[Autowire(env: 'PLATAU_ID_ACTEUR_APPELANT')] protected string $actorId,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<array-key, mixed>
     */
    protected function read(string $method, string $path, array $options): array
    {
        return $this->authenticatedRequest($method, $path, $options, ConsultationRetryStrategy::MAX_RETRIES);
    }

    /**
     * @param array<array-key, mixed> $payload
     *
     * @return array<array-key, mixed>
     */
    protected function postOnce(string $path, array $payload): array
    {
        // Une réponse perdue ne signifie pas que l'écriture a échoué. Seul un 401 permet un nouvel envoi.
        return $this->authenticatedRequest('POST', $path, ['json' => $payload], 0, 200);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<array-key, mixed>
     */
    private function authenticatedRequest(string $method, string $path, array $options, int $maxRetries, ?int $expectedStatus = null): array
    {
        if ('' === $this->actorId) {
            throw new PlatauException("L'identifiant de l'acteur Plat'AU appelant doit être configuré.");
        }

        $token = $this->tokens->token();
        try {
            $response = $this->request($method, $path, $options, $token, $maxRetries);
            if (401 === $response->getStatusCode()) {
                $this->tokens->invalidate();
                $used = $response->getInfo('retry_count');
                $remaining = $maxRetries - (is_int($used) ? $used : 0);
                $response = $this->request($method, $path, $options, $this->tokens->token(), $remaining);
                if (401 === $response->getStatusCode()) {
                    $this->tokens->invalidate();
                }
            }

            return $this->http->json($response, "Plat'AU", $expectedStatus);
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

<?php

declare(strict_types=1);

namespace App\Platau;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class HttpConsultationReader implements ConsultationReader
{
    public function __construct(
        #[Autowire(service: 'platau.client')] private HttpClientInterface $platauClient,
        private PisteTokenProvider $tokens,
        private PlatauHttp $http,
        #[Autowire(env: 'PLATAU_ID_ACTEUR_APPELANT')] private string $actorId,
    ) {
    }

    public function details(string $id): array
    {
        if ('' === $this->actorId) {
            throw new PlatauException("L'identifiant de l'acteur Plat'AU appelant doit être configuré.");
        }

        $page = 0;
        do {
            $data = $this->page($id, $page);
            $pages = $data['nombrePages'] ?? null;
            $results = $data['resultats'] ?? null;
            if (!is_int($pages) || $pages < 0 || !is_array($results) || !array_is_list($results)) {
                throw new PlatauException("La pagination renvoyée par Plat'AU est incorrecte.");
            }

            if ([] !== $results) {
                $row = $results[0];
                if (!is_array($row) || !is_array($row['dossier'] ?? null)
                    || !is_array($row['dossier']['consultations'] ?? null) || 1 !== count($row['dossier']['consultations'])) {
                    throw new PlatauException("Réponse Plat'AU incorrecte : une consultation par dossier est attendue.");
                }

                return $row;
            }
        } while (++$page < $pages);

        throw new PlatauException(sprintf('la consultation %s est introuvable selon les critères de recherche', $id));
    }

    /** @return array<array-key, mixed> */
    private function page(string $id, int $page): array
    {
        $token = $this->tokens->token();
        try {
            $response = $this->request($id, $page, $token, ConsultationRetryStrategy::MAX_RETRIES);
            if (401 === $response->getStatusCode()) {
                $this->tokens->invalidate();
                $used = $response->getInfo('retry_count');
                $remaining = ConsultationRetryStrategy::MAX_RETRIES - (is_int($used) ? $used : 0);
                $token = $this->tokens->token();
                $response = $this->request($id, $page, $token, $remaining);
                if (401 === $response->getStatusCode()) {
                    $this->tokens->invalidate();
                }
            }

            return $this->http->json($response, "Plat'AU consultations/recherche", $token);
        } catch (TransportExceptionInterface $transportException) {
            throw $this->http->failure("Connexion Plat'AU consultations/recherche", $transportException->getMessage(), $token);
        }
    }

    private function request(string $id, int $page, #[\SensitiveParameter] string $token, int $maxRetries): ResponseInterface
    {
        $client = $this->platauClient->withOptions(['auth_bearer' => $token] + $this->http->options());

        return $client->request('POST', 'consultations/recherche', [
            'headers' => ['Id-Acteur-Appelant' => $this->actorId],
            'query' => ['colonneTri' => 'DT_LIMITE_DE_REPONSE', 'sensTri' => 'DESC', 'numeroPage' => $page, 'nbElementsParPage' => 10],
            'json' => ['criteresSurConsultations' => ['idConsultation' => $id]],
            'extra' => ['trace_content' => false],
            'max_retries' => $maxRetries,
        ]);
    }
}

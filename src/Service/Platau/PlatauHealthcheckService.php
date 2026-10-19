<?php

namespace App\Service\Platau;

use App\Dto\Platau\HealthcheckResult;
use App\Dto\Platau\MaintenanceInfo;
use App\Dto\Platau\VersionInfo;
use App\Exception\PisteTokenException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PlatauHealthcheckService
{
    private const string HEALTHCHECK_ENDPOINT = 'healthcheck';

    private const string UNAVAILABLE_RESPONSE_MESSAGE = "La connexion à Plat'AU depuis Prevarisc a échouée. La réponse Plat'AU n'a pas pu être récupérée. Veuillez consulter les logs de l'application.";

    public function __construct(
        private readonly PlatauGatewayConfig $config,
        private readonly PisteTokenProvider $tokenProvider,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function executeHealthcheck(): HealthcheckResult
    {
        try {
            $response = $this->requestHealthcheck();

            // Token refusé (révoqué avant son expiration) : on le renouvelle une seule fois
            if (401 === $response->getStatusCode()) {
                $this->tokenProvider->invalidate();
                $response = $this->requestHealthcheck();
            }

            return $this->interpretResponse($response);
        } catch (PisteTokenException $pisteTokenException) {
            return HealthcheckResult::prevariscError($pisteTokenException->getMessage());
        } catch (TransportExceptionInterface $transportException) {
            $this->logger->error("La récupération du healthcheck Plat'AU a échoué.", ['exception' => $transportException]);

            return HealthcheckResult::prevariscError(self::UNAVAILABLE_RESPONSE_MESSAGE);
        }
    }

    /**
     * @throws PisteTokenException
     * @throws TransportExceptionInterface
     */
    private function requestHealthcheck(): ResponseInterface
    {
        $pisteToken = $this->tokenProvider->getToken();
        $url = $this->config->getPlatauUrl();

        if (null === $url) {
            throw new PisteTokenException(self::UNAVAILABLE_RESPONSE_MESSAGE);
        }

        return $this->httpClient->request('GET', $url.self::HEALTHCHECK_ENDPOINT, $this->config->getHttpClientOptions() + [
            'headers' => ['Content-Type' => 'application/json'],
            'auth_bearer' => $pisteToken,
        ]);
    }

    private function interpretResponse(ResponseInterface $response): HealthcheckResult
    {
        $httpCode = $response->getStatusCode();

        if ('' === $response->getContent(false)) {
            $this->logger->error("Le healthcheck n'a rien renvoyé. Vérifiez que l'URL de Plat'AU est correctement renseignée.");

            return HealthcheckResult::prevariscError(self::UNAVAILABLE_RESPONSE_MESSAGE);
        }

        try {
            $data = $response->toArray(false);
        } catch (DecodingExceptionInterface $decodingException) {
            $this->logger->error("Erreur lors de la vérification de la santé de Plat'AU : réponse JSON invalide.", ['exception' => $decodingException]);
            $data = null;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->logger->error("Healthcheck Plat'AU : code HTTP {http_code}.", ['http_code' => $httpCode]);

            return HealthcheckResult::platauError(
                sprintf("Plat'AU est en erreur (HTTP %d). Veuillez réessayer ultérieurement.", $httpCode),
                null !== $data ? VersionInfo::fromApiResponse($data) : null,
                null !== $data ? $this->buildMaintenanceInfo($data) : null
            );
        }

        if (null === $data) {
            return HealthcheckResult::prevariscError(
                "La connexion à Plat'AU depuis Prevarisc a échouée. La réponse Plat'AU n'a pas pu être lue correctement. Veuillez consulter les logs de l'application."
            );
        }

        return $this->interpretHealthcheck($data);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function interpretHealthcheck(array $data): HealthcheckResult
    {
        $version = VersionInfo::fromApiResponse($data);
        $maintenance = $this->buildMaintenanceInfo($data);

        if (true !== ($data['etatGeneral'] ?? null)) {
            $this->logger->error("L'état général de Plat'AU est en erreur.");

            return HealthcheckResult::platauError(
                "Plat'AU est en erreur (état général dégradé). Veuillez réessayer ultérieurement.",
                $version,
                $maintenance
            );
        }

        if (true !== ($data['etatBdd'] ?? null)) {
            $this->logger->error("L'état BDD de Plat'AU est en erreur.");

            return HealthcheckResult::platauError(
                "Plat'AU est en erreur (base de données dégradée). Veuillez réessayer ultérieurement.",
                $version,
                $maintenance
            );
        }

        return HealthcheckResult::success($version, $maintenance);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function buildMaintenanceInfo(array $data): ?MaintenanceInfo
    {
        $info = MaintenanceInfo::fromApiResponse($data);

        return $info->hasAnyMaintenanceInfo() ? $info : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Platau;

use App\Service\ProxyConfigurationService;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class PlatauHttp
{
    public function __construct(private ProxyConfigurationService $proxy)
    {
    }

    /** @return array{proxy?: string, no_proxy?: string} */
    public function options(): array
    {
        $options = $this->proxy->getHttpClientOptions();
        if (isset($options['proxy'])) {
            $options['no_proxy'] = '';
        }

        return $options;
    }

    /** @return array<array-key, mixed> */
    public function json(ResponseInterface $response, string $service, ?int $expectedStatus = null): array
    {
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300 || (null !== $expectedStatus && $status !== $expectedStatus)) {
            $reason = match ($status) {
                401 => 'authentification refusée',
                403 => 'accès refusé',
                default => 'échec de la requête',
            };

            throw new PlatauException(sprintf('%s : %s (HTTP %d).', $service, $reason, $status));
        }

        try {
            $data = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            throw new PlatauException($service.' : JSON invalide ('.$jsonException->getMessage().').', $jsonException->getCode(), $jsonException);
        }

        if (!is_array($data)) {
            throw new PlatauException($service.' : objet JSON attendu.');
        }

        return $data;
    }
}

<?php

declare(strict_types=1);

namespace App\Platau;

use App\Service\ProxyConfigurationService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class PlatauHttp
{
    public function __construct(
        private ProxyConfigurationService $proxy,
        #[Autowire(env: 'PISTE_CLIENT_ID')] private string $clientId,
        #[Autowire(env: 'PISTE_CLIENT_SECRET')] #[\SensitiveParameter] private string $clientSecret,
    ) {
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
    public function json(ResponseInterface $response, string $operation, #[\SensitiveParameter] string $token = ''): array
    {
        $status = $response->getStatusCode();
        $body = $response->getContent(false);
        if ($status < 200 || $status >= 300) {
            throw $this->failure($operation.' : HTTP '.$status, $body, $token);
        }

        try {
            $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            throw new PlatauException($operation.' : JSON invalide ('.$jsonException->getMessage().').', $jsonException->getCode(), $jsonException);
        }

        if (!is_array($data)) {
            throw new PlatauException($operation.' : objet JSON attendu.');
        }

        return $data;
    }

    public function failure(string $operation, #[\SensitiveParameter] string $message, #[\SensitiveParameter] string $token = ''): PlatauException
    {
        $secrets = [$this->clientId, $this->clientSecret, base64_encode($this->clientId.':'.$this->clientSecret), $token];
        $proxy = $this->proxy->getHttpClientOptions()['proxy'] ?? '';
        foreach ([PHP_URL_USER, PHP_URL_PASS] as $component) {
            $value = parse_url($proxy, $component);
            if (is_string($value)) {
                $secrets[] = rawurldecode($value);
            }
        }

        $replacements = [];
        foreach ($secrets as $secret) {
            if ('' !== $secret) {
                $replacements[$secret] = $replacements[rawurlencode($secret)] = '[masqué]';
            }
        }

        // Également masquer les identifiants d'un proxy système et un éventuel jeton
        // jusque-là inconnu renvoyé par PISTE dans une réponse d'erreur.
        $message = preg_replace('~(://)[^\s/@]+@~', '$1[masqué]@', $message) ?? '';
        $message = preg_replace('/("(?:access_token|refresh_token|id_token|client_secret)"\s*:\s*)"(?:\\\\.|[^"\\\\])*"/i', '$1"[masqué]"', $message) ?? '';
        $message = preg_replace('/\b(Basic|Bearer)\s+[a-z0-9._~+\/=-]+/i', '$1 [masqué]', $message) ?? '';

        return new PlatauException($operation.' : '.strtr($message, $replacements));
    }
}

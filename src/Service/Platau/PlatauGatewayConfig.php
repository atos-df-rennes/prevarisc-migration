<?php

namespace App\Service\Platau;

use App\Service\ProxyConfigurationService;
use Psr\Log\LoggerInterface;

/**
 * Configuration de connexion à Plat'AU, lue à la demande dans la passerelle.
 */
class PlatauGatewayConfig
{
    private const int HTTP_TIMEOUT_SECONDS = 5;

    private readonly string $configFilePath;

    private readonly string $serviceFilePath;

    private bool $loaded = false;

    private ?string $pisteClientId = null;

    private ?string $pisteClientSecret = null;

    private ?string $platauUrl = null;

    private ?string $pisteTokenUrl = null;

    public function __construct(
        string $platauConfigFile,
        string $projectDir,
        private readonly LoggerInterface $logger,
        private readonly ProxyConfigurationService $proxyConfiguration
    ) {
        $platauPath = (string) (getenv('PREVARISC_PLATAU_PATH') ?: dirname($projectDir).DIRECTORY_SEPARATOR.'prevarisc-passerelle-platau');
        $this->configFilePath = $platauPath.DIRECTORY_SEPARATOR.$platauConfigFile;
        $this->serviceFilePath = $platauPath.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, ['src', 'Service', 'PlatauAbstract.php']);
    }

    public function getPisteClientId(): ?string
    {
        $this->load();

        return $this->pisteClientId;
    }

    public function getPisteClientSecret(): ?string
    {
        $this->load();

        return $this->pisteClientSecret;
    }

    public function getPlatauUrl(): ?string
    {
        $this->load();

        return $this->platauUrl;
    }

    public function getPisteTokenUrl(): ?string
    {
        $this->load();

        return $this->pisteTokenUrl;
    }

    /**
     * Pas de redirection suivie, comme l'ancien appel cURL.
     *
     * @return array{timeout: int, max_duration: int, max_redirects: int, proxy?: string}
     */
    public function getHttpClientOptions(): array
    {
        return [
            'timeout' => self::HTTP_TIMEOUT_SECONDS,
            'max_duration' => self::HTTP_TIMEOUT_SECONDS,
            'max_redirects' => 0,
        ] + $this->proxyConfiguration->getHttpClientOptions();
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;
        $options = $this->readOptions();

        if (null !== $options) {
            $this->pisteClientId = $options['PISTE_CLIENT_ID'];
            $this->pisteClientSecret = $options['PISTE_CLIENT_SECRET'];
            $this->platauUrl = $this->getOptionalUrl($options, 'PLATAU_URL');
            $this->pisteTokenUrl = $this->getOptionalUrl($options, 'PISTE_ACCESS_TOKEN_URL');
        }

        $this->platauUrl ??= $this->getConstInFile('PLATAU_URL');
        $this->pisteTokenUrl ??= $this->getConstInFile('PISTE_ACCESS_TOKEN_URL');
    }

    /**
     * @return array{PISTE_CLIENT_ID: string, PISTE_CLIENT_SECRET: string, ...}|null
     */
    private function readOptions(): ?array
    {
        if (!file_exists($this->configFilePath)) {
            $this->logger->warning("Le fichier de configuration Plat'AU est introuvable : {path}", ['path' => $this->configFilePath]);

            return null;
        }

        $content = file_get_contents($this->configFilePath);

        if (false === $content) {
            $this->logger->error("Le fichier de configuration Plat'AU n'a pas pu être récupéré correctement.");

            return null;
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded) || !isset($decoded['platau.options']) || !is_array($decoded['platau.options'])) {
            $this->logger->error("Le fichier de configuration Plat'AU est invalide.");

            return null;
        }

        $options = $decoded['platau.options'];

        if (!isset($options['PISTE_CLIENT_ID'], $options['PISTE_CLIENT_SECRET'])) {
            $this->logger->error("Les options Plat'AU sont invalides dans le fichier de configuration.");

            return null;
        }

        if (!is_string($options['PISTE_CLIENT_ID']) || !is_string($options['PISTE_CLIENT_SECRET'])) {
            $this->logger->error('Les identifiants PISTE sont invalides dans le fichier de configuration.');

            return null;
        }

        return $options;
    }

    /**
     * @param array<array-key, mixed> $options
     */
    private function getOptionalUrl(array $options, string $key): ?string
    {
        $value = $options[$key] ?? null;

        return is_string($value) && '' !== $value ? $value : null;
    }

    private function getConstInFile(string $constant): ?string
    {
        if (file_exists($this->serviceFilePath)) {
            $lines = file($this->serviceFilePath);

            if (false !== $lines) {
                foreach ($lines as $line) {
                    if (1 === preg_match('/\bconst\s+(?:string\s+)?'.preg_quote($constant, '/').'\s*=\s*([\'"])(.*?)\1\s*;/', $line, $matches)) {
                        return $matches[2];
                    }
                }
            }
        }

        $this->logger->error("L'URL {constant} n'a pas pu être récupérée correctement dans la passerelle Plat'AU.", ['constant' => $constant]);

        return null;
    }
}

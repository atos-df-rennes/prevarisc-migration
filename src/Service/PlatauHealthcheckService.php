<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Vérification de la santé de Plat'AU via cURL.
 * Port direct de Service_Platau::executeHealthcheck() et Service_PlatauClient (legacy).
 *
 * Note : ne peut pas utiliser le service PlatauHealthcheck de la passerelle car
 * celui-ci requiert PHP 8.1 (propriétés typées) alors que l'applicatif utilise PHP 7.1.
 *
 * Les URLs PISTE et Plat'AU sont lues depuis PlatauAbstract.php de la passerelle
 * (même mécanisme que le legacy via getConstInFile), afin que les SDIS en prod
 * n'aient pas à modifier l'applicatif quand la passerelle change d'environnement.
 */
class PlatauHealthcheckService
{
    private const HEALTHCHECK_ENDPOINT = 'healthcheck';

    /** @var string */
    private $platauConfigFilePath;

    /** @var string */
    private $platauServiceFilePath;

    /** @var string */
    private $varDir;

    /** @var string */
    private $appEnv;

    /** @var LoggerInterface */
    private $logger;

    /** @var string|null */
    private $pisteClientId;

    /** @var string|null */
    private $pisteClientSecret;

    /** @var ProxyConfigurationService */
    private $proxyConfiguration;

    public function __construct(
        string $platauConfigFile,
        string $projectDir,
        string $appEnv,
        LoggerInterface $logger,
        ProxyConfigurationService $proxyConfiguration
    ) {
        $platauPath = (string) (getenv('PREVARISC_PLATAU_PATH') ?: dirname($projectDir).DIRECTORY_SEPARATOR.'prevarisc-passerelle-platau');
        $this->platauConfigFilePath = $platauPath.DIRECTORY_SEPARATOR.$platauConfigFile;
        $this->platauServiceFilePath = $platauPath.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, ['src', 'Service', 'PlatauAbstract.php']);
        $this->varDir = $projectDir.DIRECTORY_SEPARATOR.'var';
        $this->appEnv = $appEnv;
        $this->logger = $logger;
        $this->proxyConfiguration = $proxyConfiguration;

        $pisteCredentials = $this->getPisteCredentials();
        if (is_array($pisteCredentials)) {
            [$this->pisteClientId, $this->pisteClientSecret] = $pisteCredentials;
        } else {
            $this->pisteClientId = null;
            $this->pisteClientSecret = null;
        }
    }

    /**
     * Vérifie la santé de Plat'AU.
     *
     * @return array<string, mixed> {healthcheckOk: bool, errorOrigin?: string}
     */
    public function executeHealthcheck(): array
    {
        $pisteTokenData = $this->getPisteTokenData();
        $pisteToken = $pisteTokenData['access_token'] ?? null;

        if (null === $pisteToken || !$this->isTokenValid($pisteTokenData)) {
            $pisteData = $this->requestPisteToken();

            if (null === $pisteData) {
                return [
                    'healthcheckOk' => false,
                    'errorOrigin' => 'prevarisc',
                ];
            }

            $this->storePisteToken($pisteData);
            $rawToken = $pisteData['access_token'] ?? null;
            $pisteToken = is_string($rawToken) ? $rawToken : null;
        }

        if (!is_string($pisteToken)) {
            $this->logger->error("Le token PISTE n'est pas disponible.");

            return [
                'healthcheckOk' => false,
                'errorOrigin' => 'prevarisc',
            ];
        }

        $platauHealth = $this->requestPlatauHealthcheck($pisteToken);
        $platauHealthDecoded = json_decode((string) $platauHealth);

        if (!is_object($platauHealthDecoded)) {
            $this->logger->error("Erreur lors de la vérification de la santé de Plat'AU.");

            return [
                'healthcheckOk' => false,
                'errorOrigin' => 'prevarisc',
            ];
        }

        if (true !== ($platauHealthDecoded->etatGeneral ?? null)) {
            $this->logger->error("L'état général de Plat'AU est en erreur.");

            return [
                'healthcheckOk' => false,
                'errorOrigin' => 'PlatAU',
            ];
        }

        if (true !== ($platauHealthDecoded->etatBdd ?? null)) {
            $this->logger->error("L'état BDD de Plat'AU est en erreur.");

            return [
                'healthcheckOk' => false,
                'errorOrigin' => 'PlatAU',
            ];
        }

        return [
            'healthcheckOk' => true,
        ];
    }

    /**
     * Récupère les identifiants PISTE depuis le fichier de configuration Plat'AU.
     *
     * @return array<int, string>|null
     */
    private function getPisteCredentials(): ?array
    {
        if (!file_exists($this->platauConfigFilePath)) {
            $this->logger->warning("Le fichier de configuration Plat'AU est introuvable : ".$this->platauConfigFilePath);

            return null;
        }

        $platauConfigContentAsString = file_get_contents($this->platauConfigFilePath);

        if (false === $platauConfigContentAsString) {
            $this->logger->error("Le fichier de configuration Plat'AU n'a pas pu être récupéré correctement.");

            return null;
        }

        $decodedContent = json_decode($platauConfigContentAsString, true);

        if (!is_array($decodedContent) || !isset($decodedContent['platau.options'])) {
            $this->logger->error("Le fichier de configuration Plat'AU est invalide.");

            return null;
        }

        $platauOptions = $decodedContent['platau.options'];

        if (!is_array($platauOptions) || !isset($platauOptions['PISTE_CLIENT_ID'], $platauOptions['PISTE_CLIENT_SECRET'])) {
            $this->logger->error("Les options Plat'AU sont invalides dans le fichier de configuration.");

            return null;
        }

        $clientId = $platauOptions['PISTE_CLIENT_ID'];
        $clientSecret = $platauOptions['PISTE_CLIENT_SECRET'];

        if (!is_string($clientId) || !is_string($clientSecret)) {
            $this->logger->error('Les identifiants PISTE sont invalides dans le fichier de configuration.');

            return null;
        }

        return [$clientId, $clientSecret];
    }

    /**
     * Demande un nouveau token PISTE via OAuth.
     *
     * @return array<string, mixed>|null
     */
    private function requestPisteToken(): ?array
    {
        $url = $this->getConstInFile($this->platauServiceFilePath, 'PISTE_ACCESS_TOKEN_URL');

        if (null === $url) {
            $this->logger->error("L'URL de PISTE n'a pas pu être récupérée correctement.");

            return null;
        }

        $curlOptions = $this->buildBaseCurlOptions();
        $curlOptions[CURLOPT_URL] = $url;
        $curlOptions[CURLOPT_POSTFIELDS] = sprintf(
            'scope=openid&grant_type=client_credentials&client_id=%s&client_secret=%s',
            $this->pisteClientId,
            $this->pisteClientSecret
        );

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $curlOptions);
        $data = curl_exec($curlHandle);

        if ('' !== ($error = curl_error($curlHandle))) {
            $this->logger->error($error);
            curl_close($curlHandle);

            return null;
        }

        curl_close($curlHandle);

        if (!is_string($data)) {
            return null;
        }

        $decodedData = json_decode($data, true);

        if (!is_array($decodedData)) {
            $this->logger->error("La récupération du token PISTE n'a rien renvoyé. Vérifiez vos identifiants.");

            return null;
        }

        if (array_key_exists('error', $decodedData)) {
            $rawError = $decodedData['error'];
            $errorMsg = is_string($rawError) ? $rawError : (string) json_encode($rawError);
            $this->logger->error(sprintf('Erreur lors de la récupération du token PISTE : %s', $errorMsg));

            return null;
        }

        /*
         * json_decode($data, true) retourne array<mixed, mixed> ; on restreint à array<string, mixed>
         * car les clés JSON sont toujours des chaînes.
         *
         * @phpstan-var array<string, mixed> $decodedData
         */
        return $decodedData;
    }

    /**
     * Effectue le healthcheck sur l'API Plat'AU.
     */
    private function requestPlatauHealthcheck(string $pisteToken): ?string
    {
        $url = $this->getConstInFile($this->platauServiceFilePath, 'PLATAU_URL');

        if (null === $url) {
            $this->logger->error("L'URL de Plat'AU n'a pas pu être récupérée correctement.");

            return null;
        }

        $curlOptions = $this->buildBaseCurlOptions();
        $curlOptions[CURLOPT_URL] = $url.self::HEALTHCHECK_ENDPOINT;
        $curlOptions[CURLOPT_HTTPHEADER] = [
            'Content-Type: application/json',
            sprintf('Authorization: Bearer %s', $pisteToken),
        ];

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $curlOptions);
        $data = curl_exec($curlHandle);

        if ('' !== ($error = curl_error($curlHandle))) {
            $this->logger->error($error);
            curl_close($curlHandle);

            return null;
        }

        curl_close($curlHandle);

        if (!is_string($data) || '' === $data) {
            $this->logger->error("Le healthcheck n'a rien renvoyé. Vérifiez que l'URL de Plat'AU est correctement renseignée.");

            return null;
        }

        return $data;
    }

    /**
     * Construit les options cURL de base avec support du proxy.
     * Port direct de Service_PlatauClient (legacy).
     *
     * @return array<int, mixed>
     */
    private function buildBaseCurlOptions(): array
    {
        $options = [
            CURLOPT_RETURNTRANSFER => 1,
        ];

        return array_replace($options, $this->proxyConfiguration->getCurlOptions());
    }

    /**
     * Récupère le token PISTE stocké s'il existe.
     *
     * @return array<string, mixed>
     */
    private function getPisteTokenData(): array
    {
        $filepath = $this->getPisteEnvironmentFilePath();

        if (!file_exists($filepath)) {
            return [];
        }

        $pisteData = file_get_contents($filepath);

        if (false === $pisteData) {
            return [];
        }

        $decodedRaw = json_decode($pisteData, true);

        if (!is_array($decodedRaw)) {
            return [];
        }

        /*
         * json_decode($rawContent, true) retourne array<mixed, mixed> ; les clés JSON étant toujours
         * des chaînes, on restreint à array<string, mixed>.
         *
         * @phpstan-var array<string, mixed> $decodedRaw
         */
        return $decodedRaw;
    }

    /**
     * Stocke le token PISTE pour le réutiliser tant qu'il n'est pas expiré.
     *
     * @param array<string, mixed> $pisteData
     */
    private function storePisteToken(array $pisteData): void
    {
        $storedData = json_encode(
            array_merge_recursive($pisteData, ['request_time' => time()]),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        $filepath = $this->getPisteEnvironmentFilePath();
        $tmpFile = $filepath.'.tmp.'.uniqid('', true);
        file_put_contents($tmpFile, $storedData, LOCK_EX);
        rename($tmpFile, $filepath);
    }

    /**
     * Détermine si le token est valide (avec une marge de 5 minutes).
     *
     * @param array<string, mixed> $tokenData
     */
    private function isTokenValid(array $tokenData): bool
    {
        if (!isset($tokenData['request_time'], $tokenData['expires_in'])) {
            return false;
        }

        $requestTime = $tokenData['request_time'];
        $expiresIn = $tokenData['expires_in'];

        if (!is_numeric($requestTime) || !is_numeric($expiresIn)) {
            return false;
        }

        return time() < (int) $requestTime + ((int) $expiresIn - 300);
    }

    /**
     * Récupère la valeur d'une constante dans un fichier PHP.
     * Port direct de Service_Platau::getConstInFile().
     * Exemple : `private const PLATAU_URL = 'https://...'` → retourne `https://...`.
     */
    private function getConstInFile(string $filepath, string $const): ?string
    {
        $fileContent = file($filepath);

        if (false === $fileContent) {
            return null;
        }

        foreach ($fileContent as $line) {
            if (false !== strpos($line, $const)) {
                return explode("'", $line)[1];
            }
        }

        return null;
    }

    private function getPisteEnvironmentFilePath(): string
    {
        return $this->varDir.DIRECTORY_SEPARATOR.'piste-'.$this->appEnv.'.json';
    }
}

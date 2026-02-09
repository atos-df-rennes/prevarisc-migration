<?php

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Processeur d'environnement custom pour construire MAILER_DSN depuis paramètres PREVARISC.
 *
 * Permet d'utiliser %env(prevarisc_mailer_dsn:MAILER_DSN)% dans la configuration
 * qui construira automatiquement la DSN depuis PREVARISC_MAIL_* variables.
 *
 * Équivalent legacy : Service_Mail::__construct()
 */
class PrevariscMailerDsnProcessor implements EnvVarProcessorInterface
{
    public static function getProvidedTypes(): array
    {
        return [
            'prevarisc_mailer_dsn' => 'string',
        ];
    }

    /**
     * Construit la DSN Mailer depuis les variables PREVARISC_MAIL_*.
     *
     * @param mixed    $prefix Prefix (prevarisc_mailer_dsn)
     * @param mixed    $name   Nom variable (MAILER_DSN)
     * @param \Closure $getEnv Closure pour récupérer variables env
     *
     * @return string DSN Symfony Mailer
     */
    public function getEnv($prefix, $name, \Closure $getEnv): string
    {
        // Récupération paramètres Prevarisc
        $enabled = $this->getEnvBool('PREVARISC_MAIL_ENABLED', $getEnv);

        if (!$enabled) {
            // Mail désactivé : utilise null transport
            return 'null://null';
        }

        $transport = $this->getEnvString('PREVARISC_MAIL_TRANSPORT', $getEnv, 'smtp');
        $host = $this->getEnvString('PREVARISC_MAIL_HOST', $getEnv, 'localhost');
        $port = $this->getEnvString('PREVARISC_MAIL_PORT', $getEnv, '');
        $username = $this->getEnvString('PREVARISC_MAIL_USERNAME', $getEnv, '');
        $password = $this->getEnvString('PREVARISC_MAIL_PASSWORD', $getEnv, '');

        switch ($transport) {
            case 'smtp':
                return $this->buildSmtpDsn($host, $port, $username, $password);

            case 'sendmail':
            case 'mail':
                return 'sendmail://default';

            default:
                // Fallback : utilise null transport
                return 'null://null';
        }
    }

    /**
     * Construit la DSN SMTP avec authentification optionnelle.
     *
     * @param string $host     Hôte SMTP
     * @param string $port     Port SMTP (optionnel)
     * @param string $username Username (optionnel)
     * @param string $password Password (optionnel)
     *
     * @return string DSN SMTP
     */
    private function buildSmtpDsn(string $host, string $port, string $username, string $password): string
    {
        $dsn = 'smtp://';

        // Authentification si username ET password renseignés
        if ('' !== $username && '' !== $password) {
            $dsn .= urlencode($username).':'.urlencode($password).'@';
        }

        // Hôte (requis)
        $dsn .= $host;

        // Port (optionnel)
        if ('' !== $port) {
            $dsn .= ':'.$port;
        }

        return $dsn;
    }

    /**
     * Récupère une variable d'environnement en tant que bool.
     *
     * @param string   $name   Nom variable
     * @param \Closure $getEnv Closure récupération
     *
     * @return bool Valeur booléenne
     */
    private function getEnvBool(string $name, \Closure $getEnv): bool
    {
        try {
            $value = $getEnv($name);

            if (!is_string($value)) {
                return false;
            }

            return '1' === $value || 'true' === strtolower($value);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Récupère une variable d'environnement en tant que string.
     *
     * @param string   $name    Nom variable
     * @param \Closure $getEnv  Closure récupération
     * @param string   $default Valeur par défaut
     *
     * @return string Valeur string
     */
    private function getEnvString(string $name, \Closure $getEnv, string $default = ''): string
    {
        try {
            $value = $getEnv($name);

            return is_string($value) ? $value : $default;
        } catch (\Exception $exception) {
            return $default;
        }
    }
}

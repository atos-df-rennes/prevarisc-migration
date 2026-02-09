<?php

namespace App\DependencyInjection;

use App\Service\Mail\MailerConfiguration;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Processeur d'environnement custom pour construire MAILER_DSN depuis paramètres PREVARISC.
 *
 * Permet d'utiliser %env(prevarisc_mailer_dsn:MAILER_DSN)% dans la configuration
 * qui construira automatiquement la DSN depuis PREVARISC_MAIL_* variables.
 *
 * Délègue la construction à MailerConfiguration pour réutiliser le code existant.
 *
 * Équivalent legacy : Service_Mail::__construct()
 */
class PrevariscMailerDsnProcessor implements EnvVarProcessorInterface
{
    /** @var MailerConfiguration */
    private $mailerConfiguration;

    public function __construct(MailerConfiguration $mailerConfiguration)
    {
        $this->mailerConfiguration = $mailerConfiguration;
    }

    public static function getProvidedTypes(): array
    {
        return [
            'prevarisc_mailer_dsn' => 'string',
        ];
    }

    /**
     * Construit la DSN Mailer depuis les variables PREVARISC_MAIL_*.
     *
     * Délègue à MailerConfiguration pour éviter duplication de code.
     *
     * @param mixed    $prefix Prefix (prevarisc_mailer_dsn)
     * @param mixed    $name   Nom variable (MAILER_DSN)
     * @param \Closure $getEnv Closure pour récupérer variables env (non utilisé)
     *
     * @return string DSN Symfony Mailer
     */
    public function getEnv($prefix, $name, \Closure $getEnv): string
    {
        // Réutilise MailerConfiguration qui a déjà toute la logique
        return $this->mailerConfiguration->getDsn();
    }
}

<?php

namespace App\Service\Mail;

/**
 * Configuration du Mailer Symfony basée sur les paramètres Prevarisc.
 *
 * Équivalent legacy : Service_Mail::__construct()
 * Construit dynamiquement la DSN Mailer selon la configuration Apache.
 */
class MailerConfiguration
{
    private string $transport;

    private string $host;

    private string $port;

    private string $username;

    private string $password;

    private string $sender;

    private string $senderName;

    private bool $enabled;

    /**
     * @param string $transport  Type transport (smtp, sendmail, mail)
     * @param string $host       Hôte SMTP ou chemin sendmail
     * @param string $port       Port SMTP (optionnel)
     * @param string $username   Username SMTP (optionnel)
     * @param string $password   Password SMTP (optionnel)
     * @param string $sender     Email expéditeur
     * @param string $senderName Nom expéditeur (optionnel)
     * @param bool   $enabled    Mail activé
     */
    public function __construct(
        string $transport,
        string $host,
        string $port,
        string $username,
        string $password,
        string $sender,
        string $senderName,
        bool $enabled
    ) {
        $this->transport = $transport;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->sender = $sender;
        $this->senderName = $senderName;
        $this->enabled = $enabled;
    }

    /**
     * Construit la DSN Symfony Mailer selon la configuration.
     *
     * Formats supportés :
     * - smtp://user:pass@host:port (avec authentification)
     * - smtp://host:port (sans authentification)
     * - sendmail://default (sendmail local)
     *
     * @return string DSN Symfony Mailer
     */
    public function getDsn(): string
    {
        if (!$this->enabled) {
            // Mailer désactivé : utilise transport null
            return 'null://null';
        }

        switch ($this->transport) {
            case 'smtp':
                return $this->buildSmtpDsn();

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
     * @return string DSN SMTP
     */
    private function buildSmtpDsn(): string
    {
        $dsn = 'smtp://';

        // Authentification si username ET password renseignés
        if ('' !== $this->username && '' !== $this->password) {
            $dsn .= rawurlencode($this->username).':'.rawurlencode($this->password).'@';
        }

        // Hôte (requis)
        $dsn .= $this->host;

        // Port (optionnel)
        if ('' !== $this->port) {
            $dsn .= ':'.$this->port;
        }

        return $dsn;
    }

    /**
     * Retourne l'email expéditeur.
     *
     * @return string Email expéditeur
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * Retourne le nom expéditeur (si configuré).
     *
     * @return string Nom expéditeur ou chaîne vide
     */
    public function getSenderName(): string
    {
        return $this->senderName;
    }

    /**
     * Vérifie si le mailer est activé.
     *
     * @return bool True si activé
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}

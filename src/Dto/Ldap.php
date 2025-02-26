<?php

namespace App\Dto;

final class Ldap
{
    /** @var bool */
    private $enabled;

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var string */
    private $baseDn;

    public function __construct(
        bool $enabled,
        string $host,
        int $port,
        string $baseDn
    ) {
        $this->enabled = $enabled;
        $this->host = $host;
        $this->port = $port;
        $this->baseDn = $baseDn;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function baseDn(): string
    {
        return $this->baseDn;
    }
}

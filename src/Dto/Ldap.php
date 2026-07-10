<?php

namespace App\Dto;

final class Ldap
{
    private bool $enabled;

    private string $host;

    private int $port;

    private string $baseDn;

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

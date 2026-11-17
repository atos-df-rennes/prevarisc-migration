<?php

namespace App\Dto;

final readonly class Ldap
{
    public function __construct(private bool $enabled, private string $host, private int $port, private string $baseDn)
    {
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

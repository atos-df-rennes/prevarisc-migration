<?php

namespace App\Dto;

final class Ldap
{
    public function __construct(private readonly bool $enabled, private readonly string $host, private readonly int $port, private readonly string $baseDn)
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

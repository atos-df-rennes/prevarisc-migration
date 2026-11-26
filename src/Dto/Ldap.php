<?php

namespace App\Dto;

use App\Enum\LdapEncryption;

final readonly class Ldap
{
    public function __construct(private bool $enabled, private string $host, private int $port, private string $baseDn, private LdapEncryption $encryption)
    {
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function baseDn(): string
    {
        return $this->baseDn;
    }

    public function uri(): string
    {
        return $this->encryption->uri($this->host, $this->port);
    }
}

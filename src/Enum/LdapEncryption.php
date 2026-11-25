<?php

declare(strict_types=1);

namespace App\Enum;

enum LdapEncryption: string
{
    case None = 'none';
    case Ssl = 'ssl';

    public function port(int $port): int
    {
        return 0 !== $port ? $port : match ($this) {
            self::None => 389,
            self::Ssl => 636,
        };
    }

    public function uri(string $host, int $port): string
    {
        return sprintf('%s://%s:%d', self::Ssl === $this ? 'ldaps' : 'ldap', $host, $this->port($port));
    }
}

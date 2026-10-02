<?php

declare(strict_types=1);

namespace App\Enum;

enum DocumentationSpace: string
{
    case Functional = 'specs';
    case Technical = 'tech';

    public function label(): string
    {
        return match ($this) {
            self::Functional => 'Fonctionnelle',
            self::Technical => 'Technique',
        };
    }

    public function requiresAdmin(): bool
    {
        return self::Technical === $this;
    }
}

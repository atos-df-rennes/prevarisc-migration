<?php

declare(strict_types=1);

namespace App\Platau\Dto;

final readonly class Role
{
    public function __construct(public ?Nomenclature $nomRole = null)
    {
    }
}

<?php

namespace App\Dto;

final class IgnCapabilities
{
    public function __construct(private readonly ?string $url, private readonly ?string $error)
    {
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function error(): ?string
    {
        return $this->error;
    }
}

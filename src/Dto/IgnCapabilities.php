<?php

namespace App\Dto;

final class IgnCapabilities
{
    private ?string $url;

    private ?string $error;

    public function __construct(?string $url, ?string $error)
    {
        $this->url = $url;
        $this->error = $error;
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

<?php

namespace App\Dto;

final class IgnApi
{
    private readonly bool $transparent;

    private readonly bool $baseLayer;

    public function __construct(private readonly string $nom, private readonly string $url, private readonly string $type, private readonly string $layers, private readonly string $format)
    {
        $this->transparent = false;
        $this->baseLayer = false;
    }

    public function nom(): string
    {
        return $this->nom;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function layers(): string
    {
        return $this->layers;
    }

    public function format(): string
    {
        return $this->format;
    }

    public function transparent(): bool
    {
        return $this->transparent;
    }

    public function baseLayer(): bool
    {
        return $this->baseLayer;
    }
}

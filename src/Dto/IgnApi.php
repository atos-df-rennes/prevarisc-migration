<?php

namespace App\Dto;

final class IgnApi
{
    private bool $transparent;

    private bool $baseLayer;

    public function __construct(private string $nom, private string $url, private string $type, private string $layers, private string $format)
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

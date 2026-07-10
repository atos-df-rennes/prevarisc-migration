<?php

namespace App\Dto;

final class IgnApi
{
    private string $nom;

    private string $url;

    private string $type;

    private string $layers;

    private string $format;

    private bool $transparent;

    private bool $baseLayer;

    public function __construct(string $nom, string $url, string $type, string $layers, string $format)
    {
        $this->nom = $nom;
        $this->url = $url;
        $this->type = $type;
        $this->layers = $layers;
        $this->format = $format;
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

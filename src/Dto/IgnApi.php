<?php

namespace App\Dto;

final class IgnApi
{
    /** @var string */
    private $nom;

    /** @var string @ */
    private $url;

    /** @var string */
    private $type;

    /** @var string */
    private $layers;

    /** @var string */
    private $format;

    /** @var bool */
    private $transparent;

    /** @var bool */
    private $isBaseLayer;

    public function __construct(string $nom, string $url, string $type, string $layers, string $format)
    {
        $this->nom = $nom;
        $this->url = $url;
        $this->type = $type;
        $this->layers = $layers;
        $this->format = $format;
        $this->transparent = false;
        $this->isBaseLayer = false;
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

    public function isBaseLayer(): bool
    {
        return $this->isBaseLayer;
    }
}

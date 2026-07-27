<?php

namespace App\Entity;

use App\Repository\CoucheCartoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoucheCartoRepository::class)]
#[ORM\Table(name: 'couchecarto')]
class CoucheCarto
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_COUCHECARTO', type: 'bigint', options: ['unsigned' => true])]
    private $id;

    #[ORM\Column(name: 'NOM_COUCHECARTO', type: 'string', length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(name: 'URL_COUCHECARTO', type: 'string', length: 255, nullable: false)]
    private ?string $url = null;

    #[ORM\Column(name: 'ISBASELAYER_COUCHECARTO', type: 'boolean', nullable: false)]
    private ?bool $baseLayer = null;

    #[ORM\Column(name: 'LAYERS_COUCHECARTO', type: 'string', length: 255, nullable: true)]
    private ?string $layer = null;

    #[ORM\Column(name: 'FORMAT_COUCHECARTO', type: 'string', length: 255, nullable: false)]
    private ?string $format = null;

    #[ORM\Column(name: 'TRANSPARENT_COUCHECARTO', type: 'boolean', nullable: false)]
    private ?bool $transparent = null;

    #[ORM\Column(name: 'TYPE_COUCHECARTO', type: 'string', length: 50, nullable: false)]
    private ?string $type = null;

    #[ORM\Column(name: 'ORDRE_COUCHECARTO', type: 'integer', nullable: true)]
    private ?int $ordre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function isBaseLayer(): ?bool
    {
        return $this->baseLayer;
    }

    public function setBaseLayer(bool $baseLayer): self
    {
        $this->baseLayer = $baseLayer;

        return $this;
    }

    public function getLayer(): ?string
    {
        return $this->layer;
    }

    public function setLayer(?string $layer): self
    {
        $this->layer = $layer;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function isTransparent(): ?bool
    {
        return $this->transparent;
    }

    public function setTransparent(bool $transparent): self
    {
        $this->transparent = $transparent;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }
}

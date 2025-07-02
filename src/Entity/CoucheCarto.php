<?php

namespace App\Entity;

use App\Repository\CoucheCartoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CoucheCartoRepository::class)
 *
 * @ORM\Table(name="couchecarto")
 */
class CoucheCarto
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer", name="ID_COUCHECARTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, name="NOM_COUCHECARTO", nullable=true)
     */
    private $nom;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, name="URL_COUCHECARTO", nullable=true)
     */
    private $url;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="ISBASELAYER_COUCHECARTO")
     */
    private $isBaseLayer;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, name="LAYERS_COUCHECARTO", nullable=true)
     */
    private $layers;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, name="FORMAT_COUCHECARTO")
     */
    private $format;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="TRANSPARENT_COUCHECARTO")
     */
    private $transparent;

    /**
     * @var string
     * @ORM\Column(type="string", length=50, name="TYPE_COUCHECARTO")
     */
    private $type;

    /**
     * @var int
     * @ORM\Column(type="integer", name="ORDRE_COUCHECARTO", nullable=true)
     */
    private $ordre;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
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

    public function getIsBaseLayer(): ?bool
    {
        return $this->isBaseLayer;
    }

    public function setIsBaseLayer(bool $isBaseLayer): self
    {
        $this->isBaseLayer = $isBaseLayer;

        return $this;
    }

    public function getLayers(): ?string
    {
        return $this->layers;
    }

    public function setLayers(string $layers): self
    {
        $this->layers = $layers;

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

    public function getTransparent(): ?bool
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

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }
}

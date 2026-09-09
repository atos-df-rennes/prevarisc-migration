<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\CacheRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CacheRepository::class)]
class Cache
{
    #[ORM\Id]
    #[ORM\Column(name: 'ID_CACHE', type: Types::STRING, length: 250)]
    private ?string $id = null;

    #[ORM\Column(name: 'VALUE_CACHE', type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(name: 'EXPIRE_CACHE', type: Types::INTEGER, nullable: true)]
    private ?int $expire = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getExpire(): ?int
    {
        return $this->expire;
    }

    public function setExpire(?int $expire): self
    {
        $this->expire = $expire;

        return $this;
    }
}

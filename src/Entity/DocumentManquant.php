<?php

namespace App\Entity;

use App\Repository\DocumentManquantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentManquantRepository::class)]
#[ORM\Table(name: 'docmanquant')]
class DocumentManquant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_DOCMANQUANT', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_DOCMANQUANT', type: Types::TEXT, nullable: false)]
    private ?string $libelle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }
}

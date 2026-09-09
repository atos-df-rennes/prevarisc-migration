<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\DossierDocumentUrbanismeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierDocumentUrbanismeRepository::class)]
#[ORM\Table(name: 'dossierdocurba')]
class DossierDocumentUrbanisme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_DOCURBA', type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'NUM_DOCURBA', type: Types::STRING, length: 100, nullable: false)]
    private ?string $numero = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'documentsUrbanisme')]
    #[ORM\JoinColumn(name: 'ID_DOSSIER', referencedColumnName: 'ID_DOSSIER', nullable: false)]
    private ?Dossier $dossier = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }
}

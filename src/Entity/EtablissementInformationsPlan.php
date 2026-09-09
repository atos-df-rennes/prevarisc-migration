<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\EtablissementInformationsPlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementInformationsPlanRepository::class)]
#[ORM\Table(name: 'etablissementinformationsplan')]
class EtablissementInformationsPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_ETABLISSEMENTINFORMATIONSPLAN', type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'NUMERO_ETABLISSEMENTPLAN', type: Types::TEXT, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(name: 'DATE_ETABLISSEMENTPLAN', type: Types::DATE_MUTABLE, nullable: false)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: 'MISEAJOUR_ETABLISSEMENTPLAN', type: Types::BOOLEAN, nullable: false)]
    private ?bool $aMettreAJour = null;

    #[ORM\ManyToOne(targetEntity: TypePlan::class)]
    #[ORM\JoinColumn(name: 'ID_TYPEPLAN', referencedColumnName: 'ID_TYPEPLAN', nullable: false)]
    private ?TypePlan $type = null;

    #[ORM\ManyToOne(targetEntity: EtablissementInformations::class, inversedBy: 'plans')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENTINFORMATIONS', referencedColumnName: 'ID_ETABLISSEMENTINFORMATIONS', nullable: false, options: ['unsigned' => true])]
    private ?EtablissementInformations $etablissementInformations = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function isAMettreAJour(): ?bool
    {
        return $this->aMettreAJour;
    }

    public function setAMettreAJour(bool $aMettreAJour): self
    {
        $this->aMettreAJour = $aMettreAJour;

        return $this;
    }

    public function getType(): ?TypePlan
    {
        return $this->type;
    }

    public function setType(TypePlan $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEtablissementInformations(): ?EtablissementInformations
    {
        return $this->etablissementInformations;
    }

    public function setEtablissementInformations(?EtablissementInformations $etablissementInformations): self
    {
        $this->etablissementInformations = $etablissementInformations;

        return $this;
    }
}

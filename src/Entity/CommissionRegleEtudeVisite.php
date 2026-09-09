<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\CommissionRegleEtudeVisiteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionRegleEtudeVisiteRepository::class)]
#[ORM\Table(name: 'commissionregleetudevisite')]
class CommissionRegleEtudeVisite
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: CommissionRegle::class, inversedBy: 'etudeVisite')]
    #[ORM\JoinColumn(name: 'ID_REGLE', referencedColumnName: 'ID_REGLE', nullable: false, options: ['unsigned' => true])]
    private ?CommissionRegle $regle = null;

    #[ORM\Id]
    #[ORM\Column(name: 'ETUDEVISITE', type: Types::SMALLINT, nullable: false)]
    private int $etudeVisite;

    public function getRegle(): ?CommissionRegle
    {
        return $this->regle;
    }

    public function setRegle(?CommissionRegle $regle): self
    {
        $this->regle = $regle;

        return $this;
    }

    public function getEtudeVisite(): int
    {
        return $this->etudeVisite;
    }

    public function setEtudeVisite(int $etudeVisite): self
    {
        $this->etudeVisite = $etudeVisite;

        return $this;
    }
}

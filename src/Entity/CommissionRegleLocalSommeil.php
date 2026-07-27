<?php

namespace App\Entity;

use App\Repository\CommissionRegleLocalSommeilRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionRegleLocalSommeilRepository::class)]
#[ORM\Table(name: 'commissionreglelocalsommeil')]
class CommissionRegleLocalSommeil
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: CommissionRegle::class, inversedBy: 'localSommeil')]
    #[ORM\JoinColumn(name: 'ID_REGLE', referencedColumnName: 'ID_REGLE', nullable: false, options: ['unsigned' => true])]
    private ?CommissionRegle $regle = null;

    #[ORM\Id]
    #[ORM\Column(name: 'LOCALSOMMEIL', type: 'smallint', nullable: false)]
    private ?int $localSommeil = null;

    public function getRegle(): ?CommissionRegle
    {
        return $this->regle;
    }

    public function setRegle(?CommissionRegle $regle): self
    {
        $this->regle = $regle;

        return $this;
    }

    public function getLocalSommeil(): ?int
    {
        return $this->localSommeil;
    }

    public function setLocalSommeil(int $localSommeil): self
    {
        $this->localSommeil = $localSommeil;

        return $this;
    }
}

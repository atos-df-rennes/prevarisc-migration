<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\CommissionTypeEvenementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionTypeEvenementRepository::class)]
#[ORM\Table(name: 'commissiontypeevenement')]
class CommissionTypeEvenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_COMMISSIONTYPEEVENEMENT', type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'LIBELLE_COMMISSIONTYPEEVENEMENT', type: Types::STRING, length: 255, nullable: false)]
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

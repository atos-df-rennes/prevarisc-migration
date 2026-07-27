<?php

namespace App\Entity;

use App\Repository\DossierTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierTypeRepository::class)]
#[ORM\Table(name: 'dossiertype')]
class DossierType
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_DOSSIERTYPE', type: 'bigint', options: ['unsigned' => true])]
    private $id;

    #[ORM\Column(name: 'LIBELLE_DOSSIERTYPE', type: 'string', length: 100, nullable: false)]
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

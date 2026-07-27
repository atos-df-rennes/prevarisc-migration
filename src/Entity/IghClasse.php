<?php

namespace App\Entity;

use App\Repository\IghClasseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IghClasseRepository::class)]
#[ORM\Table(name: 'classe')]
class IghClasse
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_CLASSE', type: 'integer', options: ['unsigned' => true])]
    private $id;

    #[ORM\Column(name: 'LIBELLE_CLASSE', type: 'string', length: 100, nullable: false)]
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

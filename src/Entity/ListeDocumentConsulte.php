<?php

namespace App\Entity;

use App\Repository\ListeDocumentConsulteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListeDocumentConsulteRepository::class)]
#[ORM\Table(name: 'listedocconsulte')]
class ListeDocumentConsulte
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_DOC', type: 'bigint')]
    private $id;

    #[ORM\Column(name: 'LIBELLE_DOC', type: 'text', nullable: false)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'VISITE_DOC', type: 'boolean', nullable: false)]
    private ?bool $visite = null;

    #[ORM\Column(name: 'ETUDE_DOC', type: 'boolean', nullable: false)]
    private ?bool $etude = null;

    #[ORM\Column(name: 'VISITERT_DOC', type: 'boolean', nullable: false)]
    private ?bool $receptionTravaux = null;

    #[ORM\Column(name: 'VISITEVAO_DOC', type: 'boolean', nullable: false)]
    private ?bool $avantOuverture = null;

    #[ORM\Column(name: 'ORDRE_DOC', type: 'integer', nullable: false)]
    private ?int $ordre = null;

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

    public function isVisite(): ?bool
    {
        return $this->visite;
    }

    public function setVisite(bool $visite): self
    {
        $this->visite = $visite;

        return $this;
    }

    public function isEtude(): ?bool
    {
        return $this->etude;
    }

    public function setEtude(bool $etude): self
    {
        $this->etude = $etude;

        return $this;
    }

    public function isReceptionTravaux(): ?bool
    {
        return $this->receptionTravaux;
    }

    public function setReceptionTravaux(bool $receptionTravaux): self
    {
        $this->receptionTravaux = $receptionTravaux;

        return $this;
    }

    public function isAvantOuverture(): ?bool
    {
        return $this->avantOuverture;
    }

    public function setAvantOuverture(bool $avantOuverture): self
    {
        $this->avantOuverture = $avantOuverture;

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

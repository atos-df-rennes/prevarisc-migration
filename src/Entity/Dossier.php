<?php

namespace App\Entity;

use App\Repository\DossierRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DossierRepository::class)
 */
class Dossier
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_DOSSIER", type="bigint")
     */
    private $id;

    /**
     * @var ?string
     *
     * @ORM\Column(name="AVIS_DOSSIER_COMMISSION_LIBELLE", type="string", length=255, nullable=true)
     */
    private $avisDossierCommissionLibelle;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAvisDossierCommissionLibelle(): ?string
    {
        return $this->avisDossierCommissionLibelle;
    }

    public function setAvisDossierCommissionLibelle(?string $avisDossierCommissionLibelle): self
    {
        $this->avisDossierCommissionLibelle = $avisDossierCommissionLibelle;

        return $this;
    }
}

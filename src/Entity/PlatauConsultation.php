<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\PlatauConsultationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlatauConsultationRepository::class)]
#[ORM\Table(name: 'platauconsultation')]
class PlatauConsultation
{
    #[ORM\Id]
    #[ORM\Column(name: 'ID_PLATAU', type: Types::STRING, length: 11, nullable: false, options: ['fixed' => true])]
    private ?string $identifiant = null;

    #[ORM\Column(name: 'STATUT_AVIS', type: Types::STRING, length: 50, nullable: true)]
    private ?string $statutAvis = null;

    #[ORM\Column(name: 'DATE_AVIS', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAvis = null;

    #[ORM\Column(name: 'STATUT_PEC', type: Types::STRING, length: 50, nullable: true)]
    private ?string $statutPriseEnCompte = null;

    #[ORM\Column(name: 'DATE_PEC', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePriseEnCompte = null;

    #[ORM\Column(name: 'DATE_REPONSE_ATTENDUE', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateReponseAttendue = null;

    public function getIdentifiant(): ?string
    {
        return $this->identifiant;
    }

    public function setIdentifiant(string $identifiant): self
    {
        $this->identifiant = $identifiant;

        return $this;
    }

    public function getStatutAvis(): ?string
    {
        return $this->statutAvis;
    }

    public function setStatutAvis(?string $statutAvis): self
    {
        $this->statutAvis = $statutAvis;

        return $this;
    }

    public function getDateAvis(): ?\DateTimeInterface
    {
        return $this->dateAvis;
    }

    public function setDateAvis(?\DateTimeInterface $dateAvis): self
    {
        $this->dateAvis = $dateAvis;

        return $this;
    }

    public function getStatutPriseEnCompte(): ?string
    {
        return $this->statutPriseEnCompte;
    }

    public function setStatutPriseEnCompte(?string $statutPriseEnCompte): self
    {
        $this->statutPriseEnCompte = $statutPriseEnCompte;

        return $this;
    }

    public function getDatePriseEnCompte(): ?\DateTimeInterface
    {
        return $this->datePriseEnCompte;
    }

    public function setDatePriseEnCompte(?\DateTimeInterface $datePriseEnCompte): self
    {
        $this->datePriseEnCompte = $datePriseEnCompte;

        return $this;
    }

    public function getDateReponseAttendue(): ?\DateTimeInterface
    {
        return $this->dateReponseAttendue;
    }

    public function setDateReponseAttendue(?\DateTimeInterface $dateReponseAttendue): self
    {
        $this->dateReponseAttendue = $dateReponseAttendue;

        return $this;
    }
}

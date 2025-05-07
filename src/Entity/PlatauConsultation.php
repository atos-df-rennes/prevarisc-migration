<?php

namespace App\Entity;

use App\Repository\PlatauConsultationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PlatauConsultationRepository::class)
 * 
 * @ORM\Table(name="platauconsultation")
 */
class PlatauConsultation
{
    /**
     * @var string
     * 
     * @ORM\Id
     * 
     * @ORM\GeneratedValue
     * 
     * @ORM\Column(name="ID_PLATAU", type="string")
     */
    private $id;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="STATUT_AVIS", type="string", length=50, nullable=true)
     */
    private $statutAvis;

    /**
     * @var ?\DateTimeInterface
     * 
     * @ORM\Column(name="DATE_AVIS", type="date", nullable=true)
     */
    private $dateAvis;

    /**
     * @var ?string
     * 
     * @ORM\Column(name="STATUT_PEC", type="string", length=50, nullable=true)
     */
    private $statutPec;

    /**
     * @var ?\DateTimeInterface
     * 
     * @ORM\Column(name="DATE_PEC", type="date", nullable=true)
     */
    private $datePec;

    /**
     * @var ?\DateTimeInterface
     * 
     * @ORM\Column(name="DATE_REPONSE_ATTENDUE", type="date", nullable=true)
     */
    private $dateReponseAttendue;

    /**
     * @var Dossier
     * 
     * @ORM\OneToOne(targetEntity=Dossier::class, mappedBy="platauConsultation")
     */
    private $dossier;

    public function getId(): ?string
    {
        return $this->id;
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

    public function getStatutPec(): ?string
    {
        return $this->statutPec;
    }

    public function setStatutPec(?string $statutPec): self
    {
        $this->statutPec = $statutPec;

        return $this;
    }

    public function getDatePec(): ?\DateTimeInterface
    {
        return $this->datePec;
    }

    public function setDatePec(?\DateTimeInterface $datePec): self
    {
        $this->datePec = $datePec;

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

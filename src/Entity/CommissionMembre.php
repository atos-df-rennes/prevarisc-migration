<?php

namespace App\Entity;

use App\Repository\CommissionMembreRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommissionMembreRepository::class)
 */
class CommissionMembre
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_COMMISSIONMEMBRE", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBELLE_COMMISSIONMEMBRE", type="string", length=255, nullable=false)
     */
    private $libelle;

    /**
     * @var int
     *
     * @ORM\Column(name="PRESENCE_COMMISSIONMEMBRE", type="integer", nullable=false)
     */
    private $presence;

    /**
     * @var ?string
     *
     * @ORM\Column(name="COURRIER_CONVOCATIONVISITE", type="string", length=255, nullable=true)
     */
    private $courrierConvocationVisite;

    /**
     * @var ?string
     *
     * @ORM\Column(name="COURRIER_CONVOCATIONSALLE", type="string", length=255, nullable=true)
     */
    private $courrierConvocationSalle;

    /**
     * @var ?string
     *
     * @ORM\Column(name="COURRIER_ODJ", type="string", length=255, nullable=true)
     */
    private $courrierOrdreDuJour;

    /**
     * @var ?string
     *
     * @ORM\Column(name="COURRIER_PV", type="string", length=255, nullable=true)
     */
    private $courrierProcesVerbal;

    /**
     * @var ?Groupement
     *
     * @ORM\ManyToOne(targetEntity=Groupement::class)
     *
     * @ORM\JoinColumn(name="ID_GROUPEMENT", referencedColumnName="ID_GROUPEMENT", options={"unsigned":true})
     */
    private $groupement;

    /**
     * @var ?UtilisateurInformations
     *
     * @ORM\ManyToOne(targetEntity=UtilisateurInformations::class)
     *
     * @ORM\JoinColumn(name="ID_UTILISATEURINFORMATIONS", referencedColumnName="ID_UTILISATEURINFORMATIONS", options={"unsigned":true})
     */
    private $utilisateurInformations;

    /**
     * @var ?Commission
     *
     * @ORM\ManyToOne(targetEntity=Commission::class, inversedBy="membres")
     *
     * @ORM\JoinColumn(name="ID_COMMISSION", referencedColumnName="ID_COMMISSION", nullable=false, options={"unsigned":true})
     */
    private $commission;

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

    public function getPresence(): ?int
    {
        return $this->presence;
    }

    public function setPresence(int $presence): self
    {
        $this->presence = $presence;

        return $this;
    }

    public function getCourrierConvocationVisite(): ?string
    {
        return $this->courrierConvocationVisite;
    }

    public function setCourrierConvocationVisite(?string $courrierConvocationVisite): self
    {
        $this->courrierConvocationVisite = $courrierConvocationVisite;

        return $this;
    }

    public function getCourrierConvocationSalle(): ?string
    {
        return $this->courrierConvocationSalle;
    }

    public function setCourrierConvocationSalle(?string $courrierConvocationSalle): self
    {
        $this->courrierConvocationSalle = $courrierConvocationSalle;

        return $this;
    }

    public function getCourrierOrdreDuJour(): ?string
    {
        return $this->courrierOrdreDuJour;
    }

    public function setCourrierOrdreDuJour(?string $courrierOrdreDuJour): self
    {
        $this->courrierOrdreDuJour = $courrierOrdreDuJour;

        return $this;
    }

    public function getCourrierProcesVerbal(): ?string
    {
        return $this->courrierProcesVerbal;
    }

    public function setCourrierProcesVerbal(?string $courrierProcesVerbal): self
    {
        $this->courrierProcesVerbal = $courrierProcesVerbal;

        return $this;
    }

    public function getGroupement(): ?Groupement
    {
        return $this->groupement;
    }

    public function setGroupement(?Groupement $groupement): self
    {
        $this->groupement = $groupement;

        return $this;
    }

    public function getUtilisateurInformations(): ?UtilisateurInformations
    {
        return $this->utilisateurInformations;
    }

    public function setUtilisateurInformations(?UtilisateurInformations $utilisateurInformations): self
    {
        $this->utilisateurInformations = $utilisateurInformations;

        return $this;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): self
    {
        $this->commission = $commission;

        return $this;
    }
}

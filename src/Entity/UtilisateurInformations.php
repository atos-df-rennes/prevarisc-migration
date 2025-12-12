<?php

namespace App\Entity;

use App\Repository\UtilisateurInformationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UtilisateurInformationsRepository::class)
 *
 * @ORM\Table(name="utilisateurinformations")
 */
class UtilisateurInformations
{
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(name="ID_UTILISATEURINFORMATIONS", type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @var ?string
     *
     * @ORM\Column(name="NOM_UTILISATEURINFORMATIONS", type="string", length=50, nullable=true)
     */
    private $nom;

    /**
     * @var ?string
     *
     * @ORM\Column(name="PRENOM_UTILISATEURINFORMATIONS", type="string", length=50, nullable=true)
     */
    private $prenom;

    /**
     * @var ?string
     *
     * @ORM\Column(name="TELFIXE_UTILISATEURINFORMATIONS", type="string", length=20, nullable=true)
     */
    private $telephoneFixe;

    /**
     * @var ?string
     *
     * @ORM\Column(name="TELPORTABLE_UTILISATEURINFORMATIONS", type="string", length=20, nullable=true)
     */
    private $telephonePortable;

    /**
     * @var ?string
     *
     * @ORM\Column(name="TELFAX_UTILISATEURINFORMATIONS", type="string", length=20, nullable=true)
     */
    private $telephoneFax;

    /**
     * @var ?string
     *
     * @ORM\Column(name="MAIL_UTILISATEURINFORMATIONS", type="string", length=50, nullable=true)
     */
    private $mail;

    /**
     * @var ?string
     *
     * @ORM\Column(name="SOCIETE_UTILISATEURINFORMATIONS", type="string", length=100, nullable=true)
     */
    private $societe;

    /**
     * @var ?string
     *
     * @ORM\Column(name="NUMEROADRESSE_UTILISATEURINFORMATIONS", type="string", length=10, nullable=true)
     */
    private $numeroAdresse;

    /**
     * @var ?string
     *
     * @ORM\Column(name="RUEADRESSE_UTILISATEURINFORMATIONS", type="string", length=255, nullable=true)
     */
    private $rueAdresse;

    /**
     * @var ?string
     *
     * @ORM\Column(name="CPADRESSE_UTILISATEURINFORMATIONS", type="string", length=5, nullable=true)
     */
    private $codePostalAdresse;

    /**
     * @var ?string
     *
     * @ORM\Column(name="VILLEADRESSE_UTILISATEURINFORMATIONS", type="string", length=255, nullable=true)
     */
    private $villeAdresse;

    /**
     * @var ?string
     *
     * @ORM\Column(name="WEB_UTILISATEURINFORMATIONS", type="string", length=100, nullable=true)
     */
    private $siteWeb;

    /**
     * @var ?string
     *
     * @ORM\Column(name="OBS_UTILISATEURINFORMATIONS", type="text", nullable=true)
     */
    private $observations;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATE_PRV2", type="datetime", nullable=true)
     */
    private $datePrv2;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATE_RECYCLAGE", type="datetime", nullable=true)
     */
    private $dateRecyclage;

    /**
     * @var ?\DateTimeInterface
     *
     * @ORM\Column(name="DATE_SID", type="datetime", nullable=true)
     */
    private $dateSid;

    /**
     * @var ?string
     *
     * @ORM\Column(name="GRADE_UTILISATEURINFORMATIONS", type="string", length=255, nullable=true)
     */
    private $grade;

    /**
     * @var ?UtilisateurCivilite
     *
     * @ORM\ManyToOne(targetEntity=UtilisateurCivilite::class)
     *
     * @ORM\JoinColumn(name="ID_UTILISATEURCIVILITE", referencedColumnName="ID_UTILISATEURCIVILITE", nullable=true, options={"unsigned":true})
     */
    private $civilite;

    /**
     * @var ?Fonction
     *
     * @ORM\ManyToOne(targetEntity=Fonction::class, inversedBy="utilisateurInformations")
     *
     * @ORM\JoinColumn(name="ID_FONCTION", referencedColumnName="ID_FONCTION", nullable=true, options={"unsigned":true})
     */
    private $fonction;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephoneFixe(): ?string
    {
        return $this->telephoneFixe;
    }

    public function setTelephoneFixe(?string $telephoneFixe): self
    {
        $this->telephoneFixe = $telephoneFixe;

        return $this;
    }

    public function getTelephonePortable(): ?string
    {
        return $this->telephonePortable;
    }

    public function setTelephonePortable(?string $telephonePortable): self
    {
        $this->telephonePortable = $telephonePortable;

        return $this;
    }

    public function getTelephoneFax(): ?string
    {
        return $this->telephoneFax;
    }

    public function setTelephoneFax(?string $telephoneFax): self
    {
        $this->telephoneFax = $telephoneFax;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(?string $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getSociete(): ?string
    {
        return $this->societe;
    }

    public function setSociete(?string $societe): self
    {
        $this->societe = $societe;

        return $this;
    }

    public function getNumeroAdresse(): ?string
    {
        return $this->numeroAdresse;
    }

    public function setNumeroAdresse(?string $numeroAdresse): self
    {
        $this->numeroAdresse = $numeroAdresse;

        return $this;
    }

    public function getRueAdresse(): ?string
    {
        return $this->rueAdresse;
    }

    public function setRueAdresse(?string $rueAdresse): self
    {
        $this->rueAdresse = $rueAdresse;

        return $this;
    }

    public function getCodePostalAdresse(): ?string
    {
        return $this->codePostalAdresse;
    }

    public function setCodePostalAdresse(?string $codePostalAdresse): self
    {
        $this->codePostalAdresse = $codePostalAdresse;

        return $this;
    }

    public function getVilleAdresse(): ?string
    {
        return $this->villeAdresse;
    }

    public function setVilleAdresse(?string $villeAdresse): self
    {
        $this->villeAdresse = $villeAdresse;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): self
    {
        $this->siteWeb = $siteWeb;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;

        return $this;
    }

    public function getDatePrv2(): ?\DateTimeInterface
    {
        return $this->datePrv2;
    }

    public function setDatePrv2(?\DateTimeInterface $datePrv2): self
    {
        $this->datePrv2 = $datePrv2;

        return $this;
    }

    public function getDateRecyclage(): ?\DateTimeInterface
    {
        return $this->dateRecyclage;
    }

    public function setDateRecyclage(?\DateTimeInterface $dateRecyclage): self
    {
        $this->dateRecyclage = $dateRecyclage;

        return $this;
    }

    public function getDateSid(): ?\DateTimeInterface
    {
        return $this->dateSid;
    }

    public function setDateSid(?\DateTimeInterface $dateSid): self
    {
        $this->dateSid = $dateSid;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): self
    {
        $this->grade = $grade;

        return $this;
    }

    public function getCivilite(): ?UtilisateurCivilite
    {
        return $this->civilite;
    }

    public function setCivilite(?UtilisateurCivilite $civilite): self
    {
        $this->civilite = $civilite;

        return $this;
    }

    /**
     * Retourne le nom complet (Nom Prénom)
     * Fallback sur le nom ou prénom seul si l'un des deux est vide.
     */
    public function getFullName(): string
    {
        if ($this->nom && $this->prenom) {
            return trim($this->nom.' '.$this->prenom);
        }

        if ($this->nom) {
            return $this->nom;
        }

        if ($this->prenom) {
            return $this->prenom;
        }

        return '';
    }

    public function getFonction(): ?Fonction
    {
        return $this->fonction;
    }

    public function setFonction(?Fonction $fonction): self
    {
        $this->fonction = $fonction;

        return $this;
    }
}

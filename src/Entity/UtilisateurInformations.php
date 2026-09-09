<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Entity\Trait\GetIdOrFailTrait;
use App\Repository\UtilisateurInformationsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurInformationsRepository::class)]
#[ORM\Table(name: 'utilisateurinformations')]
class UtilisateurInformations
{
    use GetIdOrFailTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_UTILISATEURINFORMATIONS', type: Types::BIGINT, options: ['unsigned' => true])]
    private ?string $id = null;

    #[ORM\Column(name: 'NOM_UTILISATEURINFORMATIONS', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $nom = null;

    #[ORM\Column(name: 'PRENOM_UTILISATEURINFORMATIONS', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $prenom = null;

    #[ORM\Column(name: 'TELFIXE_UTILISATEURINFORMATIONS', type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $telephoneFixe = null;

    #[ORM\Column(name: 'TELPORTABLE_UTILISATEURINFORMATIONS', type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $telephonePortable = null;

    #[ORM\Column(name: 'TELFAX_UTILISATEURINFORMATIONS', type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $telephoneFax = null;

    #[ORM\Column(name: 'MAIL_UTILISATEURINFORMATIONS', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $mail = null;

    #[ORM\Column(name: 'SOCIETE_UTILISATEURINFORMATIONS', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $societe = null;

    #[ORM\Column(name: 'NUMEROADRESSE_UTILISATEURINFORMATIONS', type: Types::STRING, length: 10, nullable: true)]
    #[Assert\Length(max: 10)]
    private ?string $numeroAdresse = null;

    #[ORM\Column(name: 'RUEADRESSE_UTILISATEURINFORMATIONS', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $rueAdresse = null;

    #[ORM\Column(name: 'CPADRESSE_UTILISATEURINFORMATIONS', type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Length(max: 5)]
    private ?string $codePostalAdresse = null;

    #[ORM\Column(name: 'VILLEADRESSE_UTILISATEURINFORMATIONS', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $villeAdresse = null;

    #[ORM\Column(name: 'WEB_UTILISATEURINFORMATIONS', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $siteWeb = null;

    #[ORM\Column(name: 'OBS_UTILISATEURINFORMATIONS', type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(name: 'DATE_PRV2', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePrv2 = null;

    #[ORM\Column(name: 'DATE_RECYCLAGE', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRecyclage = null;

    #[ORM\Column(name: 'DATE_SID', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSid = null;

    #[ORM\Column(name: 'GRADE_UTILISATEURINFORMATIONS', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $grade = null;

    #[ORM\ManyToOne(targetEntity: UtilisateurCivilite::class)]
    #[ORM\JoinColumn(name: 'ID_UTILISATEURCIVILITE', referencedColumnName: 'ID_UTILISATEURCIVILITE', nullable: true, options: ['unsigned' => true])]
    private ?UtilisateurCivilite $civilite = null;

    #[ORM\ManyToOne(targetEntity: Fonction::class, inversedBy: 'utilisateurInformations')]
    #[ORM\JoinColumn(name: 'ID_FONCTION', referencedColumnName: 'ID_FONCTION', nullable: true, options: ['unsigned' => true])]
    private ?Fonction $fonction = null;

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
        if ($this->getNom() && $this->getPrenom()) {
            return trim($this->getNom().' '.$this->getPrenom());
        }

        if ($this->getNom()) {
            return $this->getNom();
        }

        if ($this->getPrenom()) {
            return $this->getPrenom();
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

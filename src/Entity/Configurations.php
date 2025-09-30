<?php

namespace App\Entity;

use App\Repository\ConfigurationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConfigurationsRepository::class)
 */
class Configurations
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $prevariscDebugEnabled;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $prevariscEnforceSecurity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $prevariscPluginIgnkey;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $prevariscLocalSommeilTypes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $prevariscCommissionCommunaleType;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $prevariscRisquesNaturelsExemples;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $prevariscRisquesTechnologiquesExemples;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $prevariscDashboardNextCommissionsDays;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $prevariscDashboardDossiersSansAvisDays;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $prevariscDashboardCourrierSansReponseDays;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $prevariscDateCommissionRelancePeriodicite;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $prevariscDescriptifPersonnalise;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $prevariscUnitePeriodiciteAnnees;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $prevariscDeactivatePlatau;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $prevariscDossiersMaxAAfficher;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $prevariscPlatauNoHealthcheck;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrevariscDebugEnabled(): ?bool
    {
        return $this->prevariscDebugEnabled;
    }

    public function setPrevariscDebugEnabled(?bool $prevariscDebugEnabled): self
    {
        $this->prevariscDebugEnabled = $prevariscDebugEnabled;

        return $this;
    }

    public function getPrevariscEnforceSecurity(): ?bool
    {
        return $this->prevariscEnforceSecurity;
    }

    public function setPrevariscEnforceSecurity(?bool $prevariscEnforceSecurity): self
    {
        $this->prevariscEnforceSecurity = $prevariscEnforceSecurity;

        return $this;
    }

    public function getPrevariscPluginIgnkey(): ?string
    {
        return $this->prevariscPluginIgnkey;
    }

    public function setPrevariscPluginIgnkey(?string $prevariscPluginIgnkey): self
    {
        $this->prevariscPluginIgnkey = $prevariscPluginIgnkey;

        return $this;
    }

    public function getPrevariscLocalSommeilTypes(): ?string
    {
        return $this->prevariscLocalSommeilTypes;
    }

    public function setPrevariscLocalSommeilTypes(?string $prevariscLocalSommeilTypes): self
    {
        $this->prevariscLocalSommeilTypes = $prevariscLocalSommeilTypes;

        return $this;
    }

    public function getPrevariscCommissionCommunaleType(): ?int
    {
        return $this->prevariscCommissionCommunaleType;
    }

    public function setPrevariscCommissionCommunaleType(?int $prevariscCommissionCommunaleType): self
    {
        $this->prevariscCommissionCommunaleType = $prevariscCommissionCommunaleType;

        return $this;
    }

    public function getPrevariscRisquesNaturelsExemples(): ?string
    {
        return $this->prevariscRisquesNaturelsExemples;
    }

    public function setPrevariscRisquesNaturelsExemples(?string $prevariscRisquesNaturelsExemples): self
    {
        $this->prevariscRisquesNaturelsExemples = $prevariscRisquesNaturelsExemples;

        return $this;
    }

    public function getPrevariscRisquesTechnologiquesExemples(): ?string
    {
        return $this->prevariscRisquesTechnologiquesExemples;
    }

    public function setPrevariscRisquesTechnologiquesExemples(?string $prevariscRisquesTechnologiquesExemples): self
    {
        $this->prevariscRisquesTechnologiquesExemples = $prevariscRisquesTechnologiquesExemples;

        return $this;
    }

    public function getPrevariscDashboardNextCommissionsDays(): ?int
    {
        return $this->prevariscDashboardNextCommissionsDays;
    }

    public function setPrevariscDashboardNextCommissionsDays(?int $prevariscDashboardNextCommissionsDays): self
    {
        $this->prevariscDashboardNextCommissionsDays = $prevariscDashboardNextCommissionsDays;

        return $this;
    }

    public function getPrevariscDashboardDossiersSansAvisDays(): ?int
    {
        return $this->prevariscDashboardDossiersSansAvisDays;
    }

    public function setPrevariscDashboardDossiersSansAvisDays(?int $prevariscDashboardDossiersSansAvisDays): self
    {
        $this->prevariscDashboardDossiersSansAvisDays = $prevariscDashboardDossiersSansAvisDays;

        return $this;
    }

    public function getPrevariscDashboardCourrierSansReponseDays(): ?int
    {
        return $this->prevariscDashboardCourrierSansReponseDays;
    }

    public function setPrevariscDashboardCourrierSansReponseDays(?int $prevariscDashboardCourrierSansReponseDays): self
    {
        $this->prevariscDashboardCourrierSansReponseDays = $prevariscDashboardCourrierSansReponseDays;

        return $this;
    }

    public function getPrevariscDateCommissionRelancePeriodicite(): ?bool
    {
        return $this->prevariscDateCommissionRelancePeriodicite;
    }

    public function setPrevariscDateCommissionRelancePeriodicite(?bool $prevariscDateCommissionRelancePeriodicite): self
    {
        $this->prevariscDateCommissionRelancePeriodicite = $prevariscDateCommissionRelancePeriodicite;

        return $this;
    }

    public function getPrevariscDescriptifPersonnalise(): ?bool
    {
        return $this->prevariscDescriptifPersonnalise;
    }

    public function setPrevariscDescriptifPersonnalise(?bool $prevariscDescriptifPersonnalise): self
    {
        $this->prevariscDescriptifPersonnalise = $prevariscDescriptifPersonnalise;

        return $this;
    }

    public function getPrevariscUnitePeriodiciteAnnees(): ?bool
    {
        return $this->prevariscUnitePeriodiciteAnnees;
    }

    public function setPrevariscUnitePeriodiciteAnnees(?bool $prevariscUnitePeriodiciteAnnees): self
    {
        $this->prevariscUnitePeriodiciteAnnees = $prevariscUnitePeriodiciteAnnees;

        return $this;
    }

    public function getPrevariscDeactivatePlatau(): ?bool
    {
        return $this->prevariscDeactivatePlatau;
    }

    public function setPrevariscDeactivatePlatau(?bool $prevariscDeactivatePlatau): self
    {
        $this->prevariscDeactivatePlatau = $prevariscDeactivatePlatau;

        return $this;
    }

    public function getPrevariscDossiersMaxAAfficher(): ?int
    {
        return $this->prevariscDossiersMaxAAfficher;
    }

    public function setPrevariscDossiersMaxAAfficher(?int $prevariscDossiersMaxAAfficher): self
    {
        $this->prevariscDossiersMaxAAfficher = $prevariscDossiersMaxAAfficher;

        return $this;
    }

    public function getPrevariscPlatauNoHealthcheck(): ?bool
    {
        return $this->prevariscPlatauNoHealthcheck;
    }

    public function setPrevariscPlatauNoHealthcheck(?bool $prevariscPlatauNoHealthcheck): self
    {
        $this->prevariscPlatauNoHealthcheck = $prevariscPlatauNoHealthcheck;

        return $this;
    }
}

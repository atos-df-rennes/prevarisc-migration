<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\EtablissementInformationsTypesActivitesSecondairesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementInformationsTypesActivitesSecondairesRepository::class)]
#[ORM\Table(name: 'etablissementinformationstypesactivitessecondaires')]
class EtablissementInformationsTypesActivitesSecondaires
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_ETABLISSEMENTINFORMATIONSTYPESACTIVITESSECONDAIRES', type: Types::BIGINT, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EtablissementInformations::class, inversedBy: 'typesActivitesSecondaires')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENTINFORMATIONS', referencedColumnName: 'ID_ETABLISSEMENTINFORMATIONS', nullable: false, options: ['unsigned' => true])]
    private ?EtablissementInformations $etablissementInformations = null;

    #[ORM\ManyToOne(targetEntity: EtablissementType::class)]
    #[ORM\JoinColumn(name: 'ID_TYPE_SECONDAIRE', referencedColumnName: 'ID_TYPE', nullable: false, options: ['unsigned' => true])]
    private ?EtablissementType $type = null;

    #[ORM\ManyToOne(targetEntity: EtablissementActivite::class)]
    #[ORM\JoinColumn(name: 'ID_TYPEACTIVITE_SECONDAIRE', referencedColumnName: 'ID_TYPEACTIVITE', nullable: false, options: ['unsigned' => true])]
    private ?EtablissementActivite $activite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtablissementInformations(): ?EtablissementInformations
    {
        return $this->etablissementInformations;
    }

    public function setEtablissementInformations(?EtablissementInformations $etablissementInformations): self
    {
        $this->etablissementInformations = $etablissementInformations;

        return $this;
    }

    public function getType(): ?EtablissementType
    {
        return $this->type;
    }

    public function setType(EtablissementType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getActivite(): ?EtablissementActivite
    {
        return $this->activite;
    }

    public function setActivite(EtablissementActivite $activite): self
    {
        $this->activite = $activite;

        return $this;
    }
}

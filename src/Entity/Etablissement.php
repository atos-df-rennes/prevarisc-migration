<?php

namespace App\Entity;

use App\Constant\DossierNature;
use App\Constant\GenreEtablissement;
use App\Doctrine\ContactCriteria;
use App\Repository\EtablissementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\Common\Collections\Selectable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementRepository::class)]
#[ORM\Table]
#[ORM\Index(columns: ['DATESUPPRESSION_ETABLISSEMENT'])]
class Etablissement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_ETABLISSEMENT', type: Types::BIGINT, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'DATESUPPRESSION_ETABLISSEMENT', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSuppression = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'DELETED_BY', referencedColumnName: 'ID_UTILISATEUR', options: ['unsigned' => true])]
    private ?Utilisateur $deletedBy = null;

    /**
     * @var Collection<int, EtablissementInformations>
     */
    #[ORM\OneToMany(targetEntity: EtablissementInformations::class, mappedBy: 'etablissement')]
    private Collection $informations;

    /**
     * @var Collection<int, Dossier>
     */
    #[ORM\ManyToMany(targetEntity: Dossier::class, mappedBy: 'etablissements')]
    private Collection $dossiers;

    /**
     * @var Collection<int, EtablissementAdresse>
     */
    #[ORM\OneToMany(targetEntity: EtablissementAdresse::class, mappedBy: 'etablissement', orphanRemoval: true)]
    private Collection $adresses;

    #[ORM\Column(name: 'NUMEROID_ETABLISSEMENT', type: Types::STRING, length: 50, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(name: 'TELEPHONE_ETABLISSEMENT', type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'FAX_ETABLISSEMENT', type: Types::STRING, length: 20, nullable: true)]
    private ?string $fax = null;

    #[ORM\Column(name: 'COURRIEL_ETABLISSEMENT', type: Types::STRING, length: 75, nullable: true)]
    private ?string $courriel = null;

    #[ORM\Column(name: 'NBPREV_ETABLISSEMENT', type: Types::SMALLINT, nullable: true)]
    private ?int $nbPrev = null;

    #[ORM\Column(name: 'DUREEVISITE_ETABLISSEMENT', type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $dureeVisite = null;

    /**
     * @var Collection<int, Etablissement>
     */
    #[ORM\JoinTable(name: 'etablissementlie')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_FILS_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: Etablissement::class, inversedBy: 'parents')]
    private Collection $fils;

    /**
     * @var Collection<int, Etablissement>
     */
    #[ORM\JoinTable(name: 'etablissementlie')]
    #[ORM\JoinColumn(name: 'ID_FILS_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: Etablissement::class, mappedBy: 'fils')]
    private Collection $parents;

    #[ORM\OneToOne(targetEntity: Dossier::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'ID_DOSSIER_DONNANT_AVIS', referencedColumnName: 'ID_DOSSIER', nullable: true)]
    private ?Dossier $dossierDonnantAvis = null;

    #[ORM\Column(name: 'DESCTECH_RISQUES_NATURELS_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $risquesNaturels = null;

    #[ORM\Column(name: 'DESCTECH_RISQUES_TECHNOLOGIQUES_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $risquesTechnologiques = null;

    /**
     * @var Collection<int, EtablissementDisplayRubrique>
     */
    #[ORM\OneToMany(targetEntity: EtablissementDisplayRubrique::class, mappedBy: 'etablissement', orphanRemoval: true)]
    private Collection $displayRubriques;

    /**
     * @var Collection<int, Valeur>
     */
    #[ORM\JoinTable(name: 'etablissementvaleur')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', onDelete: 'CASCADE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_VALEUR', referencedColumnName: 'ID_VALEUR', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: Valeur::class, cascade: ['persist', 'remove'])]
    private Collection $valeurs;

    /**
     * @var Collection<int, UtilisateurInformations>
     */
    #[ORM\JoinTable(name: 'etablissementcontact')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_UTILISATEURINFORMATIONS', referencedColumnName: 'ID_UTILISATEURINFORMATIONS', options: ['unsigned' => true])]
    #[ORM\ManyToMany(targetEntity: UtilisateurInformations::class)]
    private Collection $contacts;

    /**
     * @var Collection<int, TexteApplicable>
     */
    #[ORM\JoinTable(name: 'etablissementtextapp')]
    #[ORM\JoinColumn(name: 'ID_ETABLISSEMENT', referencedColumnName: 'ID_ETABLISSEMENT', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'ID_TEXTESAPPL', referencedColumnName: 'ID_TEXTESAPPL')]
    #[ORM\ManyToMany(targetEntity: TexteApplicable::class)]
    private Collection $textesApplicables;

    /**
     * @var Collection<int, EtablissementPieceJointe>
     */
    #[ORM\OneToMany(targetEntity: EtablissementPieceJointe::class, mappedBy: 'etablissement', orphanRemoval: true)]
    private Collection $piecesJointes;

    #[ORM\Column(name: 'DESCRIPTIF_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $descriptif = null;

    #[ORM\Column(name: 'DESCRIPTIF_HISTORIQUE_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $historiqueDescriptif = null;

    #[ORM\Column(name: 'DESCRIPTIF_DEROGATIONS_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $descriptifDerogation = null;

    #[ORM\Column(name: 'DESCTECH_IMPLANTATION_SURFACE_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechImplantationSurface = null;

    #[ORM\Column(name: 'DESCTECH_IMPLANTATION_SURFACETOTALE_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechImplantationSurfaceTotale = null;

    #[ORM\Column(name: 'DESCTECH_IMPLANTATION_SURFACEACCPUBLIC_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechImplantationSurfaceAccPublic = null;

    #[ORM\Column(name: 'DESCTECH_IMPLANTATION_SHON_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechImplantationShon = null;

    #[ORM\Column(name: 'DESCTECH_IMPLANTATION_NBNIVEAUX_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechImplantationNbNiveaux = null;

    #[ORM\Column(name: 'DESCTECH_IMPLANTATION_PBDN_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechImplantationPbdn = null;

    #[ORM\Column(name: 'DESCTECH_DESSERTE_NBFACADELIBRE_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechDesserteNbFacadeLibre = null;

    #[ORM\Column(name: 'DESCTECH_DESSERTE_VOIEENGIN_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechDesserteVoieEngin = null;

    #[ORM\Column(name: 'DESCTECH_DESSERTE_VOIEECHELLE_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechDesserteVoieEchelle = null;

    #[ORM\Column(name: 'DESCTECH_DESSERTE_ESPACELIBRE_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechDesserteEspaceLibre = null;

    #[ORM\Column(name: 'DESCTECH_ISOLEMENT_LATERALCF_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechIsolementLateralCf = null;

    #[ORM\Column(name: 'DESCTECH_ISOLEMENT_SUPERPOSECF_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechIsolementSuperposeCf = null;

    #[ORM\Column(name: 'DESCTECH_ISOLEMENT_VISAVIS_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechIsolementVisAVis = null;

    #[ORM\Column(name: 'DESCTECH_STABILITE_STRUCTURESF_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechStabiliteStructureSf = null;

    #[ORM\Column(name: 'DESCTECH_STABILITE_PLANCHERSF_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechStabilitePlancher = null;

    #[ORM\Column(name: 'DESCTECH_DISTRIBUTION_CLOISONNEMENTTRAD_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechDistributionCloisonnementTrad = null;

    #[ORM\Column(name: 'DESCTECH_DISTRIBUTION_SECTEURS_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechDistributionSecteurs = null;

    #[ORM\Column(name: 'DESCTECH_DISTRIBUTION_COMPARTIMENTS_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechDistributionCompartiments = null;

    #[ORM\Column(name: 'DESCTECH_LOCAUXARISQUE_NBRISQUESMOYENS_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechLocauxRisqueNbMoyens = null;

    #[ORM\Column(name: 'DESCTECH_LOCAUXARISQUE_NBRISQUESIMPORTANTS_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechLocauxRisqueNbImportants = null;

    #[ORM\Column(name: 'DESCTECH_ESPACES_NOMBRE_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechEspacesNombre = null;

    #[ORM\Column(name: 'DESCTECH_ESPACES_NIVEAUCONCERNE_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechEspacesNiveauConcerne = null;

    #[ORM\Column(name: 'DESCTECH_DESENFUMAGE_NATUREL_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechDesenfumageNaturel = null;

    #[ORM\Column(name: 'DESCTECH_DESENFUMAGE_MECANIQUE_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechDesenfumageMecanique = null;

    #[ORM\Column(name: 'DESCTECH_DESENFUMAGE_COMMENTAIRE_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $desctechDesenfumageCommentaire = null;

    #[ORM\Column(name: 'DESCTECH_CHAUFFERIES_NB_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechChaufferiesNb = null;

    #[ORM\Column(name: 'DESCTECH_CHAUFFERIES_PUISSMAX_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechChaufferiesPuissMax = null;

    #[ORM\Column(name: 'DESCTECH_CHAUFFERIES_PUISSANCETOTALE', type: Types::INTEGER, nullable: true)]
    private ?int $desctechChaufferiesPuissanceTotale = null;

    #[ORM\Column(name: 'DESCTECH_CHAUFFERIES_NB30KW', type: Types::INTEGER, nullable: true)]
    private ?int $desctechChaufferiesNb30Kw = null;

    #[ORM\Column(name: 'DESCTECH_CHAUFFERIES_NB70KW', type: Types::INTEGER, nullable: true)]
    private ?int $desctechChaufferiesNb70Kw = null;

    #[ORM\Column(name: 'DESCTECH_CHAUFFERIES_NB2MW', type: Types::INTEGER, nullable: true)]
    private ?int $desctechChaufferiesNb2Mw = null;

    #[ORM\Column(name: 'DESCTECH_COUPURENRJ_GAZ_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $desctechCoupureNrjGaz = null;

    #[ORM\Column(name: 'DESCTECH_COUPURENRJ_ELEC_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $desctechCoupureNrjElec = null;

    #[ORM\Column(name: 'DESCTECH_COUPURENRJ_PHOTOVOLTAIQUE_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $desctechCoupureNrjPhotovoltaique = null;

    #[ORM\Column(name: 'DESCTECH_COUPURENRJ_AUTRE_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $desctechCoupureNrjAutre = null;

    #[ORM\Column(name: 'DESCTECH_ASCENSEURS_NBTOTAL_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechAscenseursNbTotal = null;

    #[ORM\Column(name: 'DESCTECH_ASCENSEURS_NBAS4_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechAscenseursNbAs4 = null;

    #[ORM\Column(name: 'DESCTECH_MOYENSSECOURS_COLONNESSECHES_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechMoyensSecoursColonnesSeches = null;

    #[ORM\Column(name: 'DESCTECH_MOYENSSECOURS_COLONNESHUMIDES_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechMoyensSecoursColonnesHumides = null;

    #[ORM\Column(name: 'DESCTECH_MOYENSSECOURS_RIA_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechMoyensSecoursRia = null;

    #[ORM\Column(name: 'DESCTECH_MOYENSSECOURS_SPRINKLEUR_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechMoyensSecoursSprinkleur = null;

    #[ORM\Column(name: 'DESCTECH_MOYENSSECOURS_BROUILLARDEAU_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechMoyensSecoursBrouillardEau = null;

    #[ORM\Column(name: 'DESCTECH_PCSECU_PRESENCE_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechPcSecuPresence = null;

    #[ORM\Column(name: 'DESCTECH_PCSECU_LOCALISATION_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $desctechPcSecuLocalisation = null;

    #[ORM\Column(name: 'DESCTECH_SSI_PRESENCE_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechSsiPresence = null;

    #[ORM\Column(name: 'DESCTECH_SSI_CATEGORIE_ETABLISSEMENT', type: Types::STRING, length: 1, nullable: true)]
    private ?string $desctechSsiCategorie = null;

    #[ORM\Column(name: 'DESCTECH_SSI_ALARME_TYPE_ETABLISSEMENT', type: Types::STRING, length: 2, nullable: true)]
    private ?string $desctechSsiAlarmeType = null;

    #[ORM\Column(name: 'DESCTECH_SERVICESECU_EL18_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechServiceSecuEl18 = null;

    #[ORM\Column(name: 'DESCTECH_SERVICESECU_PERSONNELSDESIGNES_ETABLISSEMENT', type: Types::BOOLEAN, nullable: true)]
    private ?bool $desctechServiceSecuPersonnelsDesignes = null;

    #[ORM\Column(name: 'DESCTECH_SERVICESECU_AGENTDESECU_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechServiceSecuAgentSecu = null;

    #[ORM\Column(name: 'DESCTECH_SERVICESECU_CHEFEQUIPE_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechServiceSecuChefEquipe = null;

    #[ORM\Column(name: 'DESCTECH_SERVICESECU_CHEFDESERVICESECU_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechServiceSecuChefDeService = null;

    #[ORM\Column(name: 'DESCTECH_SERVICESECU_SP_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechServiceSecuSp = null;

    #[ORM\Column(name: 'DESCTECH_SERVICESECU_COMMENTAIRESP_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $desctechServiceSecuCommentaireSp = null;

    #[ORM\Column(name: 'DESCTECH_DEFENSE_PTEAU_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechDefensePtEau = null;

    #[ORM\Column(name: 'DESCTECH_DEFENSE_VOLUMEPTEAU_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechDefenseVolumePtEau = null;

    #[ORM\Column(name: 'DESCTECH_DEFENSE_PTEAUCOMMENTAIRE_ETABLISSEMENT', type: Types::TEXT, nullable: true)]
    private ?string $desctechDefensePtEauCommentaire = null;

    #[ORM\Column(name: 'DESCTECH_DEFENSE_PI_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechDefensePi = null;

    #[ORM\Column(name: 'DESCTECH_DEFENSE_BI_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechDefenseBi = null;

    #[ORM\Column(name: 'DESCTECH_DEFENSE_DEBITSIMULTANE_ETABLISSEMENT', type: Types::INTEGER, nullable: true)]
    private ?int $desctechDefenseDebitSimultane = null;

    public function __construct()
    {
        $this->dossiers = new ArrayCollection();
        $this->adresses = new ArrayCollection();
        $this->fils = new ArrayCollection();
        $this->parents = new ArrayCollection();
        $this->displayRubriques = new ArrayCollection();
        $this->valeurs = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->textesApplicables = new ArrayCollection();
        $this->piecesJointes = new ArrayCollection();
        $this->informations = new ArrayCollection();
    }

    // @fixme: il faudra le passer nullable quand on migrera l'ajout d'un établissement
    public function getId(): int
    {
        return $this->id;
    }

    public function getDateSuppression(): ?\DateTimeInterface
    {
        return $this->dateSuppression;
    }

    public function setDateSuppression(?\DateTimeInterface $dateSuppression): self
    {
        $this->dateSuppression = $dateSuppression;

        return $this;
    }

    public function getDeletedBy(): ?Utilisateur
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?Utilisateur $deletedBy): self
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    /**
     * @return Collection<int, EtablissementInformations>
     */
    public function getInformations(): Collection
    {
        return $this->informations;
    }

    /**
     * Retourne le Criteria permettant de récupérer la dernière information (ID le plus élevé).
     * Utilisable dans les entités (matching en mémoire) et dans les repositories (sous-requête DQL).
     */
    public static function createLatestInformationsCriteria(): Criteria
    {
        return Criteria::create(true)
            ->orderBy(['id' => Order::Descending])
            ->setMaxResults(1);
    }

    /**
     * Retourne l'historique le plus récent, ou null si aucune information n'existe.
     */
    public function getLatestInformationsOrNull(): ?EtablissementInformations
    {
        try {
            return $this->getLatestInformations();
        } catch (\OutOfBoundsException) {
            return null;
        }
    }

    /**
     * Retourne l'historique le plus récent.
     */
    public function getLatestInformations(): EtablissementInformations
    {
        assert($this->informations instanceof Selectable);
        $result = $this->informations->matching(self::createLatestInformationsCriteria());

        if ($result->isEmpty()) {
            throw new \OutOfBoundsException("Aucune information existante pour l'établissement ".$this->id);
        }

        /** @var EtablissementInformations $latest */
        $latest = $result->first();

        return $latest;
    }

    public function getCategorieEffective(): ?EtablissementCategorie
    {
        $informations = $this->getLatestInformations();
        $genre = $informations->getGenre();

        if ($genre instanceof Genre && GenreEtablissement::CELLULE === $genre->getId()) {
            foreach ($this->getParents() as $parent) {
                if (null === $parent->getDateSuppression()) {
                    $parentInformations = $parent->getLatestInformationsOrNull();

                    return $parentInformations?->getCategorie();
                }
            }
        }

        return $informations->getCategorie();
    }

    /**
     * Retourne la fiche d'informations en vigueur à une date donnée (iso-legacy Search.php) :
     * - la fiche la plus récente dont la date est antérieure ou égale à $date, si elle existe ;
     * - sinon, la fiche la plus ancienne de tout l'historique (fallback legacy) ;
     * - null si l'établissement n'a aucune fiche d'informations.
     */
    public function getInformationsAtDate(\DateTimeInterface $date): ?EtablissementInformations
    {
        assert($this->informations instanceof Selectable);

        $anterieures = $this->informations->matching(
            Criteria::create(true)->where(Criteria::expr()->lte('date', $date))->orderBy(['date' => Order::Descending])
                ->setMaxResults(1)
        );

        if (!$anterieures->isEmpty()) {
            /** @var EtablissementInformations $result */
            $result = $anterieures->first();

            return $result;
        }

        $plusAncienne = $this->informations->matching(
            Criteria::create(true)->orderBy(['date' => Order::Ascending])
                ->setMaxResults(1)
        );

        if ($plusAncienne->isEmpty()) {
            return null;
        }

        /** @var EtablissementInformations $result */
        $result = $plusAncienne->first();

        return $result;
    }

    public function addInformations(EtablissementInformations $informations): self
    {
        if (!$this->informations->contains($informations)) {
            $this->informations[] = $informations;
            $informations->setEtablissement($this);
        }

        return $this;
    }

    public function removeInformations(EtablissementInformations $informations): self
    {
        // set the owning side to null (unless already changed)
        if ($this->informations->removeElement($informations) && $informations->getEtablissement() === $this) {
            $informations->setEtablissement(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Dossier>
     */
    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    public function addDossier(Dossier $dossier): self
    {
        if (!$this->dossiers->contains($dossier)) {
            $this->dossiers[] = $dossier;
            $dossier->addEtablissement($this);
        }

        return $this;
    }

    public function removeDossier(Dossier $dossier): self
    {
        if ($this->dossiers->removeElement($dossier)) {
            $dossier->removeEtablissement($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, EtablissementAdresse>
     */
    public function getAdresses(): Collection
    {
        return $this->adresses;
    }

    public function addAdress(EtablissementAdresse $adress): self
    {
        if (!$this->adresses->contains($adress)) {
            $this->adresses[] = $adress;
            $adress->setEtablissement($this);
        }

        return $this;
    }

    public function removeAdress(EtablissementAdresse $adress): self
    {
        // set the owning side to null (unless already changed)
        if ($this->adresses->removeElement($adress) && $adress->getEtablissement() === $this) {
            $adress->setEtablissement(null);
        }

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): self
    {
        $this->fax = $fax;

        return $this;
    }

    public function getCourriel(): ?string
    {
        return $this->courriel;
    }

    public function setCourriel(?string $courriel): self
    {
        $this->courriel = $courriel;

        return $this;
    }

    public function getNbPrev(): ?int
    {
        return $this->nbPrev;
    }

    public function setNbPrev(?int $nbPrev): self
    {
        $this->nbPrev = $nbPrev;

        return $this;
    }

    public function getDureeVisite(): ?\DateTime
    {
        return $this->dureeVisite;
    }

    public function setDureeVisite(?\DateTime $dureeVisite): self
    {
        $this->dureeVisite = $dureeVisite;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFils(): Collection
    {
        return $this->fils;
    }

    /**
     * Établissements fils non supprimés, alignés sur le legacy (Model_DbTable_Search::setItem('etablissement'))
     * qui exclut systématiquement les établissements avec DATESUPPRESSION_ETABLISSEMENT renseignée.
     *
     * @return Collection<int, self>
     */
    public function getFilsActifs(): Collection
    {
        return $this->fils->filter(static fn (self $fils): bool => !$fils->getDateSuppression() instanceof \DateTimeInterface);
    }

    public function addFils(self $fils): self
    {
        if (!$this->fils->contains($fils)) {
            $this->fils[] = $fils;
        }

        return $this;
    }

    public function removeFils(self $fils): self
    {
        $this->fils->removeElement($fils);

        return $this;
    }

    /**
     * Le schéma de base de données exige un ManyToMany alors qu'on ne peut avoir qu'un seul parent.
     * Cette méthode renvoie donc le 1er élément de la Collection ou null s'il n'y a pas de parent.
     */
    public function getParent(): ?Etablissement
    {
        return $this->parents->first() ?: null;
    }

    /**
     * @return Collection<int, self>
     */
    public function getParents(): Collection
    {
        return $this->parents;
    }

    public function addParent(self $parent): self
    {
        if (!$this->parents->contains($parent)) {
            $this->parents[] = $parent;
            $parent->addFils($this);
        }

        return $this;
    }

    public function removeParent(self $parent): self
    {
        if ($this->parents->removeElement($parent)) {
            $parent->removeFils($this);
        }

        return $this;
    }

    public function getDossierDonnantAvis(): ?Dossier
    {
        return $this->dossierDonnantAvis;
    }

    public function setDossierDonnantAvis(?Dossier $dossierDonnantAvis): self
    {
        $this->dossierDonnantAvis = $dossierDonnantAvis;

        return $this;
    }

    public function getRisquesNaturels(): ?string
    {
        return $this->risquesNaturels;
    }

    public function setRisquesNaturels(?string $risquesNaturels): self
    {
        $this->risquesNaturels = $risquesNaturels;

        return $this;
    }

    public function getRisquesTechnologiques(): ?string
    {
        return $this->risquesTechnologiques;
    }

    public function setRisquesTechnologiques(?string $risquesTechnologiques): self
    {
        $this->risquesTechnologiques = $risquesTechnologiques;

        return $this;
    }

    public function hasAvisDiffere(): bool
    {
        $dossiersAvisDiffere = $this->getDossiers()->filter(fn (Dossier $dossier): bool => $dossier->hasAvisDiffere()
            && !$dossier->getDateSuppression() instanceof \DateTimeInterface);

        return !$dossiersAvisDiffere->isEmpty();
    }

    public function hasEcheancierTravaux(): bool
    {
        $dossiersEcheancier = $this->getDossiers()->filter(function (Dossier $dossier): bool {
            $nature = $dossier->getNature();

            if (!$nature instanceof DossierNatureListe) {
                return false;
            }

            return DossierNature::ECHEANCIER_TRAVAUX === $nature->getId()
                && !$dossier->getDateSuppression() instanceof \DateTimeInterface;
        });

        return !$dossiersEcheancier->isEmpty();
    }

    /**
     * @return Collection<int, EtablissementDisplayRubrique>
     */
    public function getDisplayRubriques(): Collection
    {
        return $this->displayRubriques;
    }

    public function addDisplayRubrique(EtablissementDisplayRubrique $displayRubrique): self
    {
        if (!$this->displayRubriques->contains($displayRubrique)) {
            $this->displayRubriques[] = $displayRubrique;
            $displayRubrique->setEtablissement($this);
        }

        return $this;
    }

    public function removeDisplayRubrique(EtablissementDisplayRubrique $displayRubrique): self
    {
        // set the owning side to null (unless already changed)
        if ($this->displayRubriques->removeElement($displayRubrique) && $displayRubrique->getEtablissement() === $this) {
            $displayRubrique->setEtablissement(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, UtilisateurInformations>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    /**
     * @return Collection<int, UtilisateurInformations>
     */
    public function getSortedContacts(): Collection
    {
        assert($this->contacts instanceof Selectable);

        /* @phpstan-ignore-next-line */
        return $this->contacts->matching(ContactCriteria::sortedByName());
    }

    public function addContact(UtilisateurInformations $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
        }

        return $this;
    }

    public function removeContact(UtilisateurInformations $contact): self
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * @return Collection<int, TexteApplicable>
     */
    public function getTextesApplicables(): Collection
    {
        return $this->textesApplicables;
    }

    public function addTextesApplicable(TexteApplicable $textesApplicable): self
    {
        if (!$this->textesApplicables->contains($textesApplicable)) {
            $this->textesApplicables[] = $textesApplicable;
        }

        return $this;
    }

    public function removeTextesApplicable(TexteApplicable $textesApplicable): self
    {
        $this->textesApplicables->removeElement($textesApplicable);

        return $this;
    }

    /**
     * @return Collection<int, EtablissementPieceJointe>
     */
    public function getPiecesJointes(): Collection
    {
        return $this->piecesJointes;
    }

    public function addPiecesJointe(EtablissementPieceJointe $piecesJointe): self
    {
        if (!$this->piecesJointes->contains($piecesJointe)) {
            $this->piecesJointes[] = $piecesJointe;
            $piecesJointe->setEtablissement($this);
        }

        return $this;
    }

    public function removePiecesJointe(EtablissementPieceJointe $piecesJointe): self
    {
        // set the owning side to null (unless already changed)
        if ($this->piecesJointes->removeElement($piecesJointe) && $piecesJointe->getEtablissement() === $this) {
            $piecesJointe->setEtablissement(null);
        }

        return $this;
    }

    public function getDescriptif(): ?string
    {
        return $this->descriptif;
    }

    public function setDescriptif(?string $descriptif): self
    {
        $this->descriptif = $descriptif;

        return $this;
    }

    public function getHistoriqueDescriptif(): ?string
    {
        return $this->historiqueDescriptif;
    }

    public function setHistoriqueDescriptif(?string $historiqueDescriptif): self
    {
        $this->historiqueDescriptif = $historiqueDescriptif;

        return $this;
    }

    public function getDescriptifDerogation(): ?string
    {
        return $this->descriptifDerogation;
    }

    public function setDescriptifDerogation(?string $descriptifDerogation): self
    {
        $this->descriptifDerogation = $descriptifDerogation;

        return $this;
    }

    public function getDesctechImplantationSurface(): ?int
    {
        return $this->desctechImplantationSurface;
    }

    public function setDesctechImplantationSurface(?int $v): self
    {
        $this->desctechImplantationSurface = $v;

        return $this;
    }

    public function getDesctechImplantationSurfaceTotale(): ?int
    {
        return $this->desctechImplantationSurfaceTotale;
    }

    public function setDesctechImplantationSurfaceTotale(?int $v): self
    {
        $this->desctechImplantationSurfaceTotale = $v;

        return $this;
    }

    public function getDesctechImplantationSurfaceAccPublic(): ?int
    {
        return $this->desctechImplantationSurfaceAccPublic;
    }

    public function setDesctechImplantationSurfaceAccPublic(?int $v): self
    {
        $this->desctechImplantationSurfaceAccPublic = $v;

        return $this;
    }

    public function getDesctechImplantationShon(): ?int
    {
        return $this->desctechImplantationShon;
    }

    public function setDesctechImplantationShon(?int $v): self
    {
        $this->desctechImplantationShon = $v;

        return $this;
    }

    public function getDesctechImplantationNbNiveaux(): ?int
    {
        return $this->desctechImplantationNbNiveaux;
    }

    public function setDesctechImplantationNbNiveaux(?int $v): self
    {
        $this->desctechImplantationNbNiveaux = $v;

        return $this;
    }

    public function getDesctechImplantationPbdn(): ?int
    {
        return $this->desctechImplantationPbdn;
    }

    public function setDesctechImplantationPbdn(?int $v): self
    {
        $this->desctechImplantationPbdn = $v;

        return $this;
    }

    public function getDesctechDesserteNbFacadeLibre(): ?int
    {
        return $this->desctechDesserteNbFacadeLibre;
    }

    public function setDesctechDesserteNbFacadeLibre(?int $v): self
    {
        $this->desctechDesserteNbFacadeLibre = $v;

        return $this;
    }

    public function getDesctechDesserteVoieEngin(): ?int
    {
        return $this->desctechDesserteVoieEngin;
    }

    public function setDesctechDesserteVoieEngin(?int $v): self
    {
        $this->desctechDesserteVoieEngin = $v;

        return $this;
    }

    public function getDesctechDesserteVoieEchelle(): ?int
    {
        return $this->desctechDesserteVoieEchelle;
    }

    public function setDesctechDesserteVoieEchelle(?int $v): self
    {
        $this->desctechDesserteVoieEchelle = $v;

        return $this;
    }

    public function getDesctechDesserteEspaceLibre(): ?int
    {
        return $this->desctechDesserteEspaceLibre;
    }

    public function setDesctechDesserteEspaceLibre(?int $v): self
    {
        $this->desctechDesserteEspaceLibre = $v;

        return $this;
    }

    public function getDesctechIsolementLateralCf(): ?int
    {
        return $this->desctechIsolementLateralCf;
    }

    public function setDesctechIsolementLateralCf(?int $v): self
    {
        $this->desctechIsolementLateralCf = $v;

        return $this;
    }

    public function getDesctechIsolementSuperposeCf(): ?int
    {
        return $this->desctechIsolementSuperposeCf;
    }

    public function setDesctechIsolementSuperposeCf(?int $v): self
    {
        $this->desctechIsolementSuperposeCf = $v;

        return $this;
    }

    public function getDesctechIsolementVisAVis(): ?int
    {
        return $this->desctechIsolementVisAVis;
    }

    public function setDesctechIsolementVisAVis(?int $v): self
    {
        $this->desctechIsolementVisAVis = $v;

        return $this;
    }

    public function getDesctechStabiliteStructureSf(): ?int
    {
        return $this->desctechStabiliteStructureSf;
    }

    public function setDesctechStabiliteStructureSf(?int $v): self
    {
        $this->desctechStabiliteStructureSf = $v;

        return $this;
    }

    public function getDesctechStabilitePlancher(): ?int
    {
        return $this->desctechStabilitePlancher;
    }

    public function setDesctechStabilitePlancher(?int $v): self
    {
        $this->desctechStabilitePlancher = $v;

        return $this;
    }

    public function getDesctechDistributionCloisonnementTrad(): ?bool
    {
        return $this->desctechDistributionCloisonnementTrad;
    }

    public function setDesctechDistributionCloisonnementTrad(?bool $v): self
    {
        $this->desctechDistributionCloisonnementTrad = $v;

        return $this;
    }

    public function getDesctechDistributionSecteurs(): ?bool
    {
        return $this->desctechDistributionSecteurs;
    }

    public function setDesctechDistributionSecteurs(?bool $v): self
    {
        $this->desctechDistributionSecteurs = $v;

        return $this;
    }

    public function getDesctechDistributionCompartiments(): ?bool
    {
        return $this->desctechDistributionCompartiments;
    }

    public function setDesctechDistributionCompartiments(?bool $v): self
    {
        $this->desctechDistributionCompartiments = $v;

        return $this;
    }

    public function getDesctechLocauxRisqueNbMoyens(): ?int
    {
        return $this->desctechLocauxRisqueNbMoyens;
    }

    public function setDesctechLocauxRisqueNbMoyens(?int $v): self
    {
        $this->desctechLocauxRisqueNbMoyens = $v;

        return $this;
    }

    public function getDesctechLocauxRisqueNbImportants(): ?int
    {
        return $this->desctechLocauxRisqueNbImportants;
    }

    public function setDesctechLocauxRisqueNbImportants(?int $v): self
    {
        $this->desctechLocauxRisqueNbImportants = $v;

        return $this;
    }

    public function getDesctechEspacesNombre(): ?int
    {
        return $this->desctechEspacesNombre;
    }

    public function setDesctechEspacesNombre(?int $v): self
    {
        $this->desctechEspacesNombre = $v;

        return $this;
    }

    public function getDesctechEspacesNiveauConcerne(): ?int
    {
        return $this->desctechEspacesNiveauConcerne;
    }

    public function setDesctechEspacesNiveauConcerne(?int $v): self
    {
        $this->desctechEspacesNiveauConcerne = $v;

        return $this;
    }

    public function getDesctechDesenfumageNaturel(): ?bool
    {
        return $this->desctechDesenfumageNaturel;
    }

    public function setDesctechDesenfumageNaturel(?bool $v): self
    {
        $this->desctechDesenfumageNaturel = $v;

        return $this;
    }

    public function getDesctechDesenfumageMecanique(): ?bool
    {
        return $this->desctechDesenfumageMecanique;
    }

    public function setDesctechDesenfumageMecanique(?bool $v): self
    {
        $this->desctechDesenfumageMecanique = $v;

        return $this;
    }

    public function getDesctechDesenfumageCommentaire(): ?string
    {
        return $this->desctechDesenfumageCommentaire;
    }

    public function setDesctechDesenfumageCommentaire(?string $v): self
    {
        $this->desctechDesenfumageCommentaire = $v;

        return $this;
    }

    public function getDesctechChaufferiesNb(): ?int
    {
        return $this->desctechChaufferiesNb;
    }

    public function setDesctechChaufferiesNb(?int $v): self
    {
        $this->desctechChaufferiesNb = $v;

        return $this;
    }

    public function getDesctechChaufferiesPuissMax(): ?int
    {
        return $this->desctechChaufferiesPuissMax;
    }

    public function setDesctechChaufferiesPuissMax(?int $v): self
    {
        $this->desctechChaufferiesPuissMax = $v;

        return $this;
    }

    public function getDesctechChaufferiesPuissanceTotale(): ?int
    {
        return $this->desctechChaufferiesPuissanceTotale;
    }

    public function setDesctechChaufferiesPuissanceTotale(?int $v): self
    {
        $this->desctechChaufferiesPuissanceTotale = $v;

        return $this;
    }

    public function getDesctechChaufferiesNb30Kw(): ?int
    {
        return $this->desctechChaufferiesNb30Kw;
    }

    public function setDesctechChaufferiesNb30Kw(?int $v): self
    {
        $this->desctechChaufferiesNb30Kw = $v;

        return $this;
    }

    public function getDesctechChaufferiesNb70Kw(): ?int
    {
        return $this->desctechChaufferiesNb70Kw;
    }

    public function setDesctechChaufferiesNb70Kw(?int $v): self
    {
        $this->desctechChaufferiesNb70Kw = $v;

        return $this;
    }

    public function getDesctechChaufferiesNb2Mw(): ?int
    {
        return $this->desctechChaufferiesNb2Mw;
    }

    public function setDesctechChaufferiesNb2Mw(?int $v): self
    {
        $this->desctechChaufferiesNb2Mw = $v;

        return $this;
    }

    public function getDesctechCoupureNrjGaz(): ?string
    {
        return $this->desctechCoupureNrjGaz;
    }

    public function setDesctechCoupureNrjGaz(?string $v): self
    {
        $this->desctechCoupureNrjGaz = $v;

        return $this;
    }

    public function getDesctechCoupureNrjElec(): ?string
    {
        return $this->desctechCoupureNrjElec;
    }

    public function setDesctechCoupureNrjElec(?string $v): self
    {
        $this->desctechCoupureNrjElec = $v;

        return $this;
    }

    public function getDesctechCoupureNrjPhotovoltaique(): ?string
    {
        return $this->desctechCoupureNrjPhotovoltaique;
    }

    public function setDesctechCoupureNrjPhotovoltaique(?string $v): self
    {
        $this->desctechCoupureNrjPhotovoltaique = $v;

        return $this;
    }

    public function getDesctechCoupureNrjAutre(): ?string
    {
        return $this->desctechCoupureNrjAutre;
    }

    public function setDesctechCoupureNrjAutre(?string $v): self
    {
        $this->desctechCoupureNrjAutre = $v;

        return $this;
    }

    public function getDesctechAscenseursNbTotal(): ?int
    {
        return $this->desctechAscenseursNbTotal;
    }

    public function setDesctechAscenseursNbTotal(?int $v): self
    {
        $this->desctechAscenseursNbTotal = $v;

        return $this;
    }

    public function getDesctechAscenseursNbAs4(): ?int
    {
        return $this->desctechAscenseursNbAs4;
    }

    public function setDesctechAscenseursNbAs4(?int $v): self
    {
        $this->desctechAscenseursNbAs4 = $v;

        return $this;
    }

    public function getDesctechMoyensSecoursColonnesSeches(): ?bool
    {
        return $this->desctechMoyensSecoursColonnesSeches;
    }

    public function setDesctechMoyensSecoursColonnesSeches(?bool $v): self
    {
        $this->desctechMoyensSecoursColonnesSeches = $v;

        return $this;
    }

    public function getDesctechMoyensSecoursColonnesHumides(): ?bool
    {
        return $this->desctechMoyensSecoursColonnesHumides;
    }

    public function setDesctechMoyensSecoursColonnesHumides(?bool $v): self
    {
        $this->desctechMoyensSecoursColonnesHumides = $v;

        return $this;
    }

    public function getDesctechMoyensSecoursRia(): ?bool
    {
        return $this->desctechMoyensSecoursRia;
    }

    public function setDesctechMoyensSecoursRia(?bool $v): self
    {
        $this->desctechMoyensSecoursRia = $v;

        return $this;
    }

    public function getDesctechMoyensSecoursSprinkleur(): ?bool
    {
        return $this->desctechMoyensSecoursSprinkleur;
    }

    public function setDesctechMoyensSecoursSprinkleur(?bool $v): self
    {
        $this->desctechMoyensSecoursSprinkleur = $v;

        return $this;
    }

    public function getDesctechMoyensSecoursBrouillardEau(): ?bool
    {
        return $this->desctechMoyensSecoursBrouillardEau;
    }

    public function setDesctechMoyensSecoursBrouillardEau(?bool $v): self
    {
        $this->desctechMoyensSecoursBrouillardEau = $v;

        return $this;
    }

    public function getDesctechPcSecuPresence(): ?bool
    {
        return $this->desctechPcSecuPresence;
    }

    public function setDesctechPcSecuPresence(?bool $v): self
    {
        $this->desctechPcSecuPresence = $v;

        return $this;
    }

    public function getDesctechPcSecuLocalisation(): ?string
    {
        return $this->desctechPcSecuLocalisation;
    }

    public function setDesctechPcSecuLocalisation(?string $v): self
    {
        $this->desctechPcSecuLocalisation = $v;

        return $this;
    }

    public function getDesctechSsiPresence(): ?bool
    {
        return $this->desctechSsiPresence;
    }

    public function setDesctechSsiPresence(?bool $v): self
    {
        $this->desctechSsiPresence = $v;

        return $this;
    }

    public function getDesctechSsiCategorie(): ?string
    {
        return $this->desctechSsiCategorie;
    }

    public function setDesctechSsiCategorie(?string $v): self
    {
        $this->desctechSsiCategorie = $v;

        return $this;
    }

    public function getDesctechSsiAlarmeType(): ?string
    {
        return $this->desctechSsiAlarmeType;
    }

    public function setDesctechSsiAlarmeType(?string $v): self
    {
        $this->desctechSsiAlarmeType = $v;

        return $this;
    }

    public function getDesctechServiceSecuEl18(): ?bool
    {
        return $this->desctechServiceSecuEl18;
    }

    public function setDesctechServiceSecuEl18(?bool $v): self
    {
        $this->desctechServiceSecuEl18 = $v;

        return $this;
    }

    public function getDesctechServiceSecuPersonnelsDesignes(): ?bool
    {
        return $this->desctechServiceSecuPersonnelsDesignes;
    }

    public function setDesctechServiceSecuPersonnelsDesignes(?bool $v): self
    {
        $this->desctechServiceSecuPersonnelsDesignes = $v;

        return $this;
    }

    public function getDesctechServiceSecuAgentSecu(): ?int
    {
        return $this->desctechServiceSecuAgentSecu;
    }

    public function setDesctechServiceSecuAgentSecu(?int $v): self
    {
        $this->desctechServiceSecuAgentSecu = $v;

        return $this;
    }

    public function getDesctechServiceSecuChefEquipe(): ?int
    {
        return $this->desctechServiceSecuChefEquipe;
    }

    public function setDesctechServiceSecuChefEquipe(?int $v): self
    {
        $this->desctechServiceSecuChefEquipe = $v;

        return $this;
    }

    public function getDesctechServiceSecuChefDeService(): ?int
    {
        return $this->desctechServiceSecuChefDeService;
    }

    public function setDesctechServiceSecuChefDeService(?int $v): self
    {
        $this->desctechServiceSecuChefDeService = $v;

        return $this;
    }

    public function getDesctechServiceSecuSp(): ?int
    {
        return $this->desctechServiceSecuSp;
    }

    public function setDesctechServiceSecuSp(?int $v): self
    {
        $this->desctechServiceSecuSp = $v;

        return $this;
    }

    public function getDesctechServiceSecuCommentaireSp(): ?string
    {
        return $this->desctechServiceSecuCommentaireSp;
    }

    public function setDesctechServiceSecuCommentaireSp(?string $v): self
    {
        $this->desctechServiceSecuCommentaireSp = $v;

        return $this;
    }

    public function getDesctechDefensePtEau(): ?int
    {
        return $this->desctechDefensePtEau;
    }

    public function setDesctechDefensePtEau(?int $v): self
    {
        $this->desctechDefensePtEau = $v;

        return $this;
    }

    public function getDesctechDefenseVolumePtEau(): ?int
    {
        return $this->desctechDefenseVolumePtEau;
    }

    public function setDesctechDefenseVolumePtEau(?int $v): self
    {
        $this->desctechDefenseVolumePtEau = $v;

        return $this;
    }

    public function getDesctechDefensePtEauCommentaire(): ?string
    {
        return $this->desctechDefensePtEauCommentaire;
    }

    public function setDesctechDefensePtEauCommentaire(?string $v): self
    {
        $this->desctechDefensePtEauCommentaire = $v;

        return $this;
    }

    public function getDesctechDefensePi(): ?int
    {
        return $this->desctechDefensePi;
    }

    public function setDesctechDefensePi(?int $v): self
    {
        $this->desctechDefensePi = $v;

        return $this;
    }

    public function getDesctechDefenseBi(): ?int
    {
        return $this->desctechDefenseBi;
    }

    public function setDesctechDefenseBi(?int $v): self
    {
        $this->desctechDefenseBi = $v;

        return $this;
    }

    public function getDesctechDefenseDebitSimultane(): ?int
    {
        return $this->desctechDefenseDebitSimultane;
    }

    public function setDesctechDefenseDebitSimultane(?int $v): self
    {
        $this->desctechDefenseDebitSimultane = $v;

        return $this;
    }

    /**
     * @return Collection<int, Valeur>
     */
    public function getValeurs(): Collection
    {
        return $this->valeurs;
    }
}

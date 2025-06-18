<?php

namespace App\Dto;

use App\Entity\Commission;
use App\Entity\Dossier;
use App\Entity\DossierAffectation;
use App\Entity\DossierDocManquant;
use App\Entity\DossierDocurba;
use App\Entity\Avis;
use App\Entity\AvisDerogations;
use App\Entity\Utilisateur;
use Doctrine\Common\Collections\Collection;
use App\Entity\PlatauConsultation;
use DateTime;
use DateTimeInterface;

final class DossierDto
{
    /** @var string|null */
    private $objet;

    /** @var int|null */
    private $typeId;

    /** @var string|null */
    private $typeLibelle;

    /** @var int|null */
    private $natureId;

    /** @var string|null */
    private $natureLibelle;

    /** @var int */
    private $dossierId;


    /** @var string|null */
    private $demandeur;

     /** @var string|null */
    private $lieuReunion;

    /** @var string|null */
    private $serviceInstructeur;

    /** @var string|null */
    private $descriptifAnalyseRisque;

    /** @var string|null */
    private $reglesADeroger;

    /** @var string|null */
    private $justificatifDerogation;

    /** @var string|null */
    private $mesuresCompensatoires;

    /** @var string|null */
    private $mesuresComplementaires;

    /** @var string|null */
    private $etatDossier;

    /** @var bool|null */
    private $horsDelai;
    
    /** @var string|null */
    private $differeAvis;

    /** @var string|null */
    private $nonPrononce;

    /** @var bool|null */
    private $absenceQuorum;
    
    /** @var bool|null */
    private $celluleNonExploitée; 

    /** @var bool|null */
    private $facteurDanger;

    /** @var DateTimeInterface|null */
    private $echeancierTravaux;

    /** @var string|null */
    private $anomalie;

    /** @var bool|null */
    private $operationSDIS; 

    /** @var bool|null */
    private $RCCI; 

    /** @var bool|null */
    private $verrouDossier; 

    /** @var string|null */
    private $REX; 

    /** @var string|null */
    private $chargeSecurite; 

    /** @var string|null */
    private $gravitePrescription; 

    /** @var int|null */
    private $numIntervention; 

    /** @var string|null */
    private $observations;

    /** @var DateTimeInterface|null */
    private $dateCreation;

    /** @var DateTimeInterface|null */
    private $dateReunion;

    /** @var DateTimeInterface|null */
    private $dateMairie;

    /** @var DateTimeInterface|null */
    private $dateSuppression;

    /** @var DateTimeInterface|null */
    private $dateSecretariat;

    /** @var DateTimeInterface|null */
    private $dateEnvoiTransit;

    /** @var DateTimeInterface|null */
    private $dateSDIS;

    /** @var DateTimeInterface|null */
    private $dateVisite;

    /** @var DateTimeInterface|null */
    private $dateRVRAT;

    /** @var string|null */
    private $dateCommission;

    /** @var DateTimeInterface|null */
    private $datePrefecture;

    /** @var DateTimeInterface|null */
    private $dateReponse;

    /** @var DateTimeInterface|null */
    private $dateTransfertCommission;

    /** @var DateTimeInterface|null */
    private $dateReceptionCommission;

    /** @var DateTimeInterface|null */
    private $delaiPrescription;

    /** @var DateTimeInterface|null */
    private $dateSignature;

    /** @var DateTimeInterface|null */
    private $dateIntervention;

    /** @var DateTimeInterface|null */
    private $dureeIntervention;

    /** @var DateTimeInterface|null */
    private $dureeDeplacement;

    /** @var PlatauConsultation|null */
    private $platauConsultation;
    
    /** @var Commission|null */
    private $commission;

    /** @var Utilisateur|null */
    private $createurDossier;

    /** @var Utilisateur|null */
    private $verrouUserDossier;

    /** @var Avis|null */
    private $avisDossier;

    /** @var Avis|null */
    private $avisDossierCommission; 
    
    /** @var Collection<int, AvisDerogations>|null */
    private $avisDerogations = null;

    /** @var Collection<int, Utilisateur>|null */
    private $preventionnisteDossier = null;

    /** @var Collection<int, DossierDocManquant>|null */
    private $dossierDocManquant = null;    

    /** @var Collection<int, DossierDocurba>|null */
    private $numDocurba = null;  

    /** @var Collection<int, DossierAffectation>|null */
    private $dossierAffectation = null; 

    public function __construct(
        ?string $objet, ?int $typeId, ?string $typeLibelle, ?int $natureId, ?string $natureLibelle, int $dossierId,
        ?string $demandeur, ?string $lieuReunion, ?string $serviceInstructeur, ?string $descriptifAnalyseRisque,
        ?string $reglesADeroger, ?string $justificatifDerogation, ?string $mesuresCompensatoires, ?string $mesuresComplementaires,
        ?string $etatDossier, ?bool $horsDelai , ?string $differeAvis, ?string $nonPrononce, ?bool $absenceQuorum,
        ?bool $celluleNonExploitée, ?bool $facteurDanger, ?DateTimeInterface $echeancierTravaux, ?string $anomalie,
        ?bool $operationSDIS, ?bool $RCCI, ?bool $verrouDossier, ?string $REX, ?string $chargeSecurite,
        ?string $gravitePrescription, ?int $numIntervention, ?string $observations, ?DateTimeInterface $dateCreation,
        ?DateTimeInterface $dateReunion, ?DateTimeInterface $dateMairie, ?DateTimeInterface $dateSuppression,
        ?DateTimeInterface $dateSecretariat, ?DateTimeInterface $dateEnvoiTransit, ?DateTimeInterface $dateSDIS,
        ?DateTimeInterface $dateVisite, ?DateTimeInterface $dateRVRAT, ?string $dateCommission, ?DateTimeInterface $datePrefecture,
        ?DateTimeInterface $dateReponse, ?DateTimeInterface $dateTransfertCommission, ?DateTimeInterface $dateReceptionCommission,
        ?DateTimeInterface $delaiPrescription, ?DateTimeInterface $dateSignature, ?DateTimeInterface $dateIntervention,
        ?DateTimeInterface $dureeIntervention, ?DateTimeInterface $dureeDeplacement, ?PlatauConsultation $platauConsultation,
        ?Commission $commission, ?Utilisateur $createurDossier , ?Utilisateur $verrouUserDossier,
        ?Avis $avisDossier, ?Avis $avisDossierCommission, ?Collection $avisDerogations, ?Collection $preventionnisteDossier,
        ?Collection $dossierDocManquant, ?Collection $numDocurba, ?Collection $dossierAffectation
    )
    {
        $this->objet = $objet;
        $this->typeId = $typeId;
        $this->typeLibelle = $typeLibelle;
        $this->natureId = $natureId;
        $this->natureLibelle = $natureLibelle;
        $this->dossierId = $dossierId;
        $this->demandeur = $demandeur;
        $this->lieuReunion = $lieuReunion;
        $this->serviceInstructeur = $serviceInstructeur;   
        $this->descriptifAnalyseRisque = $descriptifAnalyseRisque;
        $this->reglesADeroger = $reglesADeroger;
        $this->justificatifDerogation = $justificatifDerogation;
        $this->mesuresCompensatoires = $mesuresCompensatoires;
        $this->mesuresComplementaires = $mesuresComplementaires;
        $this->etatDossier = $etatDossier;
        $this->horsDelai = $horsDelai;
        $this->differeAvis = $differeAvis;
        $this->nonPrononce = $nonPrononce;
        $this->absenceQuorum = $absenceQuorum;
        $this->celluleNonExploitée = $celluleNonExploitée;
        $this->facteurDanger = $facteurDanger;
        $this->echeancierTravaux = $echeancierTravaux;
        $this->anomalie = $anomalie;
        $this->operationSDIS = $operationSDIS;
        $this->RCCI = $RCCI;
        $this->verrouDossier = $verrouDossier;
        $this->REX = $REX;
        $this->chargeSecurite = $chargeSecurite;
        $this->gravitePrescription = $gravitePrescription;
        $this->numIntervention = $numIntervention;
        $this->observations = $observations;
        $this->dateCreation = $dateCreation;
        $this->dateReunion = $dateReunion;
        $this->dateMairie = $dateMairie;
        $this->dateSuppression = $dateSuppression;
        $this->dateSecretariat = $dateSecretariat;
        $this->dateEnvoiTransit = $dateEnvoiTransit;
        $this->dateSDIS = $dateSDIS;
        $this->dateVisite = $dateVisite;
        $this->dateRVRAT = $dateRVRAT;
        $this->dateCommission = $dateCommission;
        $this->datePrefecture = $datePrefecture;
        $this->dateReponse = $dateReponse;
        $this->dateTransfertCommission = $dateTransfertCommission;
        $this->dateReceptionCommission = $dateReceptionCommission;
        $this->delaiPrescription = $delaiPrescription;
        $this->dateSignature = $dateSignature;
        $this->dateIntervention = $dateIntervention;
        $this->dureeIntervention = $dureeIntervention;
        $this->dureeDeplacement = $dureeDeplacement;
        $this->platauConsultation = $platauConsultation;
        $this->commission = $commission;
        $this->createurDossier = $createurDossier;
        $this->verrouUserDossier = $verrouUserDossier;
        $this->avisDossier = $avisDossier;
        $this->avisDossierCommission = $avisDossierCommission;
        $this->avisDerogations = $avisDerogations;
        $this->preventionnisteDossier = $preventionnisteDossier;
        $this->dossierDocManquant = $dossierDocManquant;
        $this->numDocurba = $numDocurba;
        $this->dossierAffectation = $dossierAffectation;

    }

    public static function fromRequest(Dossier $dossier): self
    {
        return new self(
            $dossier->getObjet(),
            $dossier->getType() ? $dossier->getType()->getId() : null,
            $dossier->getType() ? $dossier->getType()->getLibelle() : null,
            $dossier->getNature() ? $dossier->getNature()->getId() : null,
            $dossier->getNature() ? $dossier->getNature()->getLibelle() : null,
            $dossier->getId(),
            $dossier->getDemandeur(),
            $dossier->getLieuReunion(),
            $dossier->getServiceInstructeur(),
            $dossier->getDescriptifAnalyseRisque(),
            $dossier->getReglesADeroger(),
            $dossier->getJustificatifDerogation(),
            $dossier->getMesuresCompensatoires(),
            $dossier->getMesuresComplementaires(),
            $dossier->getEtatDossier(),
            $dossier->getHorsDelai(),
            $dossier->getDiffereAvis(),
            $dossier->getNonPrononce(),
            $dossier->getAbsenceQuorum(),
            $dossier->getCelluleNonExploitée(),
            $dossier->getFacteurDanger(),
            $dossier->getEcheancierTravaux(),
            $dossier->getAnomalie(),
            $dossier->getOperationSDIS(),
            $dossier->getRCCI(),
            $dossier->getVerrouDossier(),
            $dossier->getRex(),
            $dossier->getChargeSecurite(),
            $dossier->getGravitePrescription(),
            $dossier->getNumIntervention(),
            $dossier->getObservations(),
            $dossier->getDateCreation() ?: null,
            $dossier->getDateReunion() ?: null,
            $dossier->getDateMairie() ?: null,
            $dossier->getDateSuppression() ?: null,
            $dossier->getDateSecretariat() ?: null,
            $dossier->getDateEnvTransit() ?: null,
            $dossier->getDateSDIS() ?: null,
            $dossier->getDateVisite() ?: null,
            $dossier->getDateRVRAT() ?: null,
            $dossier->getDateCommission() ?: null,
            $dossier->getDatePrefecture() ?: null,
            $dossier->getDateReponse() ?: null,
            $dossier->getDateTransfertCommission() ?: null,
            $dossier->getDateReceptionCommission() ?: null,
            $dossier->getDelaiPrescription() ?: null,
            $dossier->getDateSignature() ?: null,
            $dossier->getDateIntervention() ?: null,
            $dossier->getDureeIntervention() ?: null,
            $dossier->getDureeDeplacement() ?: null,
            $dossier->getPlatauConsultation(),
            $dossier->getCommissionDossier(),
            $dossier->getCreateurDossier(),
            $dossier->getVerrouUserDossier(),
            $dossier->getAvisDossier(),
            $dossier->getAvisDossierCommission(),
            $dossier->getAvisDerogations(),
            $dossier->getPreventionnisteDossier(),
            $dossier->getDossierDocManquants(),
            $dossier->getNumDossierDocurba(),
            $dossier->getDossierAffectation()

        );
    }

    public function objet(): ?string
    {
        return $this->objet;
    }

    public function typeId(): ?int
    {
        return $this->typeId;
    }

    public function typeLibelle(): ?string
    {
        return $this->typeLibelle;
    }

    public function natureId(): ?int
    {
        return $this->natureId;
    }

    public function natureLibelle(): ?string
    {
        return $this->natureLibelle;
    }

    public function dossierId(): int
    {
        return $this->dossierId;
    }

    public function demandeur(): ?string
    {
        return $this->demandeur;
    }

    public function lieuReunion(): ?string
    {
        return $this->lieuReunion;
    }

    public function serviceInstructeur(): ?string
    {
        return $this->serviceInstructeur;
    }

    public function descriptifAnalyseRisque(): ?string
    {
        return $this->descriptifAnalyseRisque;
    }

    public function reglesADeroger(): ?string
    {
        return $this->reglesADeroger;
    }

    public function justificatifDerogation(): ?string
    {
        return $this->justificatifDerogation;
    }

    public function mesuresCompensatoires(): ?string
    {
        return $this->mesuresCompensatoires;
    }

    public function mesuresComplementaires(): ?string
    {
        return $this->mesuresComplementaires;
    }

    public function etatDossier(): ?string
    {
        return $this->etatDossier;
    }

    public function horsDelai(): ?bool
    {
        return $this->horsDelai;
    }

    public function differeAvis(): ?string
    {
        return $this->differeAvis;
    }

    public function nonPrononce(): ?string
    {
        return $this->nonPrononce;
    }

    public function absenceQuorum(): ?bool
    {
        return $this->absenceQuorum;
    }

    public function celluleNonExploitée(): ?bool
    {
        return $this->celluleNonExploitée;
    }

    public function facteurDanger(): ?bool
    {
        return $this->facteurDanger;
    }

    public function echeancierTravaux(): ?DateTimeInterface
    {
        return $this->echeancierTravaux;
    }

    public function anomalie(): ?string
    {
        return $this->anomalie;
    }

    public function operationSDIS(): ?bool
    {
        return $this->operationSDIS;
    }

    public function RCCI(): ?bool
    {
        return $this->RCCI;
    }

    public function verrouDossier(): ?bool
    {
        return $this->verrouDossier;
    }

    public function REX(): ?string
    {
        return $this->REX;
    }

    public function chargeSecurite(): ?string
    {
        return $this->chargeSecurite;
    }

    public function gravitePrescription(): ?string
    {
        return $this->gravitePrescription;
    }

    public function numIntervention(): ?int
    {
        return $this->numIntervention;
    }

    public function observations(): ?string
    {
        return $this->observations;
    }

    public function dateCreation(): ?DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function dateReunion(): ?DateTimeInterface
    {
        return $this->dateReunion;
    }

    public function dateMairie(): ?DateTimeInterface
    {
        return $this->dateMairie;
    }

    public function dateSuppression(): ?DateTimeInterface
    {
        return $this->dateSuppression;
    }

    public function dateSecretariat(): ?DateTimeInterface
    {
        return $this->dateSecretariat;
    }

    public function dateEnvoiTransit(): ?DateTimeInterface
    {
        return $this->dateEnvoiTransit;
    }

    public function dateSDIS(): ?DateTimeInterface
    {
        return $this->dateSDIS;
    }

    public function dateVisite(): ?DateTimeInterface
    {
        return $this->dateVisite;
    }

    public function dateRVRAT(): ?DateTimeInterface
    {
        return $this->dateRVRAT;
    }

    public function dateCommission(): ?string
    {
        return $this->dateCommission;
    }

    public function datePrefecture(): ?DateTimeInterface
    {
        return $this->datePrefecture;
    }

    public function dateReponse(): ?DateTimeInterface
    {
        return $this->dateReponse;
    }

    public function dateTransfertCommission(): ?DateTimeInterface
    {
        return $this->dateTransfertCommission;
    }

    public function dateReceptionCommission(): ?DateTimeInterface
    {
        return $this->dateReceptionCommission;
    }

    public function delaiPrescription(): ?DateTimeInterface
    {
        return $this->delaiPrescription;
    }

    public function dateSignature(): ?DateTimeInterface
    {
        return $this->dateSignature;
    }

    public function dateIntervention(): ?DateTimeInterface
    {
        return $this->dateIntervention;
    }

    public function dureeIntervention(): ?DateTimeInterface
    {
        return $this->dureeIntervention;
    }

    public function dureeDeplacement(): ?DateTimeInterface
    {
        return $this->dureeDeplacement;
    }

    public function platauConsultation(): ?PlatauConsultation
    {
        return $this->platauConsultation;
    }

    public function commission(): ?Commission
    {
        return $this->commission;
    }

    public function createurDossier(): ?Utilisateur
    {
        return $this->createurDossier;
    }

    public function verrouUserDossier(): ?Utilisateur
    {
        return $this->verrouUserDossier;
    }

    public function avisDossier(): ?Avis
    {
        return $this->avisDossier;
    }

    public function avisDossierCommission(): ?Avis
    {
        return $this->avisDossierCommission;
    }

    public function avisDerogations(): ?Collection
    {
        return $this->avisDerogations;
    }

    public function preventionnisteDossier(): ?Collection
    {
        return $this->preventionnisteDossier;
    }

    public function dossierDocManquant(): ?Collection
    {
        return $this->dossierDocManquant;
    }

    public function numDocurba(): ?Collection
    {
        return $this->numDocurba;
    }

    public function dossierAffectation(): ?Collection
    {
        return $this->dossierAffectation;
    }



}

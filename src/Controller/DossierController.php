<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\PlatauConsultation;
use App\Enum\PlatauStatutAvis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\DossierRepository;
use App\Service\TempsRestant;
use App\Enum\PlatauStatutPec;



/**
 * @Route(requirements={"id"="\d+"})
 */
class DossierController extends AbstractController
{
    /**
     * @Route("/dossier/{id}", name="dossier_infos")
     */
    public function index(Dossier $dossier, TempsRestant $tempsRestant): Response
    {
        $typeDossier = $dossier->getType();
        $libelleType = $typeDossier->getLibelle();
        $idType = $typeDossier->getId();        
        $natureDossier = $dossier->getNature();
        $nature = $natureDossier->getLibelle();
        $natureId = $natureDossier->getId();
      
        $objetDossier = $dossier->getObjet();
        $demandeurDossier = $dossier->getDemandeur();
        $lieuReunion = $dossier->getLieuReunion();
        $serviceInstructeur = $dossier->getServiceInstructeur();
        $descriptifAnalyseRisque =$dossier->getDescriptifAnalyseRisque();
        $reglesADeroger = $dossier->getReglesADeroger();
        $justificatifDerogation =$dossier->getJustificatifDerogation();
        $mesuresCompensatoires = $dossier->getMesuresCompensatoires();
        $mesuresComplementaires = $dossier->getMesuresComplementaires();
        $etatDossier = $dossier->getEtatDossier();
        $horsDelai = $dossier->getHorsDelai();
        $differeAvis = $dossier->getDiffereAvis();
        $nonPrononce = $dossier->getNonPrononce();
        $dateCreation = $dossier->getDateCreation();
        $dateReunion = $dossier->getDateReunion();
        $dateMairie = $dossier->getDateMairie();
        $dateSupprime = $dossier->getDateSuppression();
        $dateSecretariat = $dossier->getDateSecretariat();
        $dateEnvoiTransit = $dossier->getDateEnvTransit();
        $dateSDIS = $dossier->getDateSDIS();
        $dateVisite = $dossier->getDateVisite();
        $dateRVRAT = $dossier->getDateRVRAT();
        $dateCommission = $dossier->getDateCommission();
        $datePrefecture = $dossier->getDatePrefecture();
        $dateReponse = $dossier->getDateReponse();
        $dateTransfertCommission = $dossier->getDateTransfertCommission();
        $dateReceptionCommission = $dossier->getDateReceptionCommission();
        $delaiPrescription = $dossier->getDelaiPrescription();
        $dateSignature = $dossier->getDateSignature();
        $dateIntervention = $dossier->getDateIntervention();
        if($dateIntervention !== null){
            $heureIntervention = $dateIntervention->format('H:i');
        }
        $DureeIntervention = $dossier->getDureeIntervention();
        $dureeDeplacement = $dossier->getDureeDeplacement();
        $absenceQuorum = $dossier->getAbsenceQuorum();
        $celluleNonExploitée = $dossier->getCelluleNonExploitée();
        $facteurDanger = $dossier->getFacteurDanger();
        $echeancierTravaux = $dossier->getEcheancierTravaux();
        $anomalie = $dossier->getAnomalie();
        $operationSDIS = $dossier->getOperationSDIS();
        $RCCI = $dossier->getRCCI();
        $REX = $dossier->getREX();
        $chargeSecurite = $dossier->getChargeSecurite();
        $gravitePrescription = $dossier->getGravitePrescription();
        $numIntervention = $dossier->getNumIntervention();
        $numDocurba = $dossier->getNumDossierDocurba();
        $commissionDossier = $dossier->getCommissionDossier();
        if($commissionDossier !== null && $commissionDossier->getId() != 0 ){
            $dateCommissionDossier = $commissionDossier->getDateCommissions()->last()->getId();
            $typeCommissionDossier = $commissionDossier->getType();

        }
        $avisDossier = $dossier->getAvisDossier();
        $avisDossierCommission = $dossier->getAvisDossierCommission();
        $affichageAvis = $absenceQuorum || $horsDelai || $etatDossier || $nonPrononce || $differeAvis; 
        $preventionnisteDossier = $dossier->getPreventionnisteDossier();
        $dossierDocManquant = $dossier->getDossierDocManquants();
        $observations = $dossier->getObservations();
        $createurDossier = $dossier->getCreateurDossier();
        $verrouDossier = $dossier->getVerrouDossier();
        $verrouUserDossier = $dossier->getVerrouUserDossier();
        $thirdPartyPlugins = explode(';', getenv('PREVARISC_THIRDPARTY_PLUGINS'));
        $isSignalement = in_array('Plugin_FacteurCriticite', $thirdPartyPlugins, true);
       

        //Partie concernant Information Plat'AU 
        $platauConsultation = $dossier->getPlatauConsultation();
        if($platauConsultation != null ){
            $tempsRestantReponse = $tempsRestant->calculate($platauConsultation->getDateReponseAttendue());
            $couleurTempsRestant = $tempsRestant->getCouleurTempsRestant($platauConsultation->getDateReponseAttendue());
            $statutPec = $platauConsultation->getStatutPec();
            $statutAvis = $platauConsultation->getStatutAvis();
        }
        $enumStatutPec = new PlatauStatutPec;
        $pecInconnu = $enumStatutPec::INCONNU;
        $pecEnAttente = $enumStatutPec::EN_ATTENTE;
        $pecEnErreur = $enumStatutPec::EN_ERREUR;
        $pecARenvoyer = $enumStatutPec::A_RENVOYER;
        $pecEnPriseEnCompte = $enumStatutPec::PRISE_EN_COMPTE;
        $classPec = $enumStatutPec::getCssClass($statutPec);  
        $statutLabelPec = $enumStatutPec::getLabel($statutPec);

        $enumStatutAvis = new PlatauStatutAvis;
        $avisInconnu = $enumStatutAvis::INCONNU;
        $avisenCours = $enumStatutAvis::EN_COURS;
        $avisEnErreur = $enumStatutAvis::EN_ERREUR;
        $avisARenvoyer = $enumStatutAvis::A_RENVOYER;
        $avisTraite = $enumStatutAvis::TRAITE;
        $statutLabelAvis = $enumStatutAvis::getLabel($statutAvis);
        $classAvis = $enumStatutAvis::getCssClass($statutAvis); 

        return $this->render('dossier/index.html.twig', [
            'libelleType' => $libelleType,
            'idType' => $idType,
            'nature' => $nature,
            'objet' => $objetDossier,
            'demandeur' => $demandeurDossier,
            'lieuReunion' => $lieuReunion,
            'serviceInstructeur' =>$serviceInstructeur,
            'descriptifAnalyseRisque' =>$descriptifAnalyseRisque,
            'reglesADeroger' => $reglesADeroger,
            'justificatifDerogation' => $justificatifDerogation,
            'mesuresCompensatoires' => $mesuresCompensatoires,
            'mesuresComplementaires' =>$mesuresComplementaires,
            'horsDelai' =>$horsDelai,
            'etatDossier' => $etatDossier,
            'nonPrononce' => $nonPrononce,
            'differeAvis' => $differeAvis,
            'dateCreation' => $dateCreation,
            'dateReunion' => $dateReunion,
            'dateMairie' => $dateMairie,
            'dateSecretariat' => $dateSecretariat,
            'dateSupprime' => $dateSupprime,
            'dateEnvoiTransit' => $dateEnvoiTransit,
            'dateSDIS' => $dateSDIS,
            'dateVisite' => $dateVisite,
            'dateRVRAT' => $dateRVRAT,
            'dateCommission' => $dateCommission,
            'datePrefecture' => $datePrefecture,
            'dateReponse' => $dateReponse,
            'dateTransfertCommission' => $dateTransfertCommission,
            'dateReceptionCommission' => $dateReceptionCommission,
            'delaiPrescription' => $delaiPrescription,
            'dateSignature' => $dateSignature,
            'dateIntervention' => $dateIntervention,
            'heureIntervention' => $heureIntervention,
            'dureeIntervention' => $DureeIntervention,
            'dureeDeplacement' => $dureeDeplacement,
            'absenceQuorum' => $absenceQuorum,
            'celluleNonExploitée' => $celluleNonExploitée,
            'facteurDanger' => $facteurDanger,
            'echeancierTravaux' => $echeancierTravaux,
            'anomalie' => $anomalie,
            'operationSDIS' => $operationSDIS,
            'RCCI' => $RCCI,
            'REX' => $REX,
            'chargeSecurite' => $chargeSecurite,
            'gravitePrescription' => $gravitePrescription,
            'numIntervention' => $numIntervention,
            'numDocurba' => $numDocurba,
            'commissionDossier' => $commissionDossier,
            'dateCommissionDossier' => $dateCommissionDossier,
            'typeCommissionDossier' => $typeCommissionDossier,
            'avisDossier' => $avisDossier,
            'avisDossierCommission' => $avisDossierCommission,
            'affichageAvis' => $affichageAvis,
            'preventionnisteDossier' => $preventionnisteDossier,
            'natureId' => $natureId,
            'dossierDocManquant' => $dossierDocManquant,
            'isSignalement' => $isSignalement,
            'observations' => $observations,
            'createurDossier' => $createurDossier,
            'verrouDossier' => $verrouDossier,
            'verrouUserDossier' => $verrouUserDossier,
            'platauConsultation' => $platauConsultation,
            'tempsRestant' => $tempsRestantReponse,
            'couleurTempsRestant' => $couleurTempsRestant,
            'statutPec' => $statutPec,
            'enumStatutPec' => $enumStatutPec,
            'pecEnErreur' => $pecEnErreur,
            'pecEnAttente' => $pecEnAttente,
            'pecARenvoyer' => $pecARenvoyer,
            'pecInconnu' => $pecInconnu,
            'pecEnPriseEncompte' => $pecEnPriseEnCompte,
            'classPec' => $classPec,
            'statutLabelPec' => $statutLabelPec,
            'statutAvis' => $statutAvis,
            'enumstatutAvis' => $enumStatutAvis,
            'avisInconnu' => $avisInconnu,
            'avisEnErreur' => $avisEnErreur,
            'avisTraite' => $avisTraite,
            'avisARenvoyer' => $avisARenvoyer,
            'avisEnCours' => $avisenCours,
            'classAvis' => $classAvis,
            'statutLabelAvis' => $statutLabelAvis  
        ]);
    }

}
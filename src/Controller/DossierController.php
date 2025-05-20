<?php
namespace App\Controller;

use App\Entity\Dossier;
use App\Enum\PlatauStatutAvis;
use App\Repository\UtilisateurRepository;
use App\Repository\CapsuleRubriqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\DossierRepository;
use App\Service\TempsRestant;
use App\Enum\PlatauStatutPec;
use App\Service\DossierService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Doctrine\ORM\EntityManagerInterface;


/**
 * @Route(requirements={"id"="\d+"})
 */
class DossierController extends AbstractController
{
    /**
     * @Route("/dossier/{id}", name="dossier_index")
     */
    public function index(Dossier $dossier, TempsRestant $tempsRestant, CapsuleRubriqueRepository $capsuleRubriqueRepository,
    DossierService $dossierService): Response
    {
        //Type et nature de dossier 
        $idDossier = $dossier->getId();
        $typeDossier = $dossier->getType();
        $libelleType = $typeDossier->getLibelle();
        $idType = $typeDossier->getId(); 
        $natureDossier = $dossier->getNature();
        if($natureDossier !== null){
            $nature = $natureDossier->getLibelle();
            $natureId = $natureDossier->getId();
        }

        //Infos de dossier
        $user = $this->getUser()->getId();
        $dossierAffectation= $dossier->getDossierAffectation();
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
        if($commissionDossier !== null){
            $dateCommissionDossier = $commissionDossier->getDateCommissions()->last()->getId();
            $typeCommissionDossier = $commissionDossier->getType();
        }

        $preventionnisteDossier = $dossier->getPreventionnisteDossier();
        $dossierDocManquant = $dossier->getDossierDocManquants();
        $observations = $dossier->getObservations();
        $affichageAvis = $absenceQuorum || $horsDelai || $etatDossier || $nonPrononce || $differeAvis;

        //Createur et verrouUser
        $createurDossier = $dossier->getCreateurDossier();
        if($createurDossier != null){
            $idCreateurDossier = $createurDossier->getInformations()->getId();
        }
        $verrouDossier = $dossier->getVerrouDossier();
        $verrouUserDossier = $dossier->getVerrouUserDossier();
        if (!$verrouUserDossier || $verrouUserDossier->getId() === 0) {
            $verrouUserDossier = null;
        }

        //Avis
        $avisDossier = $dossier->getAvisDossier();
        $avisDossierCommission = $dossier->getAvisDossierCommission();

        //Dates
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
       
        //Verification technique , effectifsDegagement, AvisDerogation
        $verificationsRubrique = $capsuleRubriqueRepository->findOneBy(['nomInterne' => 'descriptifVerificationsTechniques']);
        $effectifsRubrique = $capsuleRubriqueRepository->findOneBy(['nomInterne' => 'effectifsDegagementsDossier']);
        $nomOngletVerificationsTechniques = $verificationsRubrique ? $verificationsRubrique->getNom() : null;
        $nomOngletEffectifsDegagements = $effectifsRubrique ? $effectifsRubrique->getNom() : null;
        $avisDerogation =$dossier->getAvisDerogations();
        if($avisDerogation !=null ){
            $hasAvisDerogation =true;
        };

        $etabData = $dossierService->getEtabInfos($dossier, null);
        $thirdPartyPlugins = explode(';', getenv('PREVARISC_THIRDPARTY_PLUGINS'));
        $isSignalement = in_array('Plugin_FacteurCriticite', $thirdPartyPlugins, true);

        return $this->render('dossier/index.html.twig', [
            'idUser' =>$user,
            'idDossier' => $idDossier,
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
            'etabData' =>$etabData,
            'dossierAffectation' =>$dossierAffectation,
            'idCreateurDossier' =>$idCreateurDossier,
            'statutLabelAvis' => $statutLabelAvis,
            'nomOngletVerificationsTechniques' => $nomOngletVerificationsTechniques,
            'nomOngletEffectifsDegagements' => $nomOngletEffectifsDegagements,
            'hasAvisDerogation' => $hasAvisDerogation,
 
        ]);
    }

    /**
     * @Route("/dossier/{id}/delete", name="dossier_delete", methods={"GET"})
     */
    public function delete(int $id, DossierRepository $dossierRepository, EntityManagerInterface $em): RedirectResponse{
        try {
            $dossier = $dossierRepository->find($id);
            if (!$dossier) {
                throw $this->createNotFoundException('Dossier introuvable');
            }
            // Vérifie combien d'établissements sont liés à ce dossier
            $etablissements = $dossier->getEtablissements();
            $deleteDossier = true;
            if ($deleteDossier) {
                $dateSuppression = new \DateTime();
                $dossier->setDateSuppression($dateSuppression);
                $user = $this->getUser();
                if ($user) {
                    $dossier->setDeletedBy($user);
                }
                foreach ($dossier->getDossierAffectation() as $affect) {
                    $em->remove($affect);
                }
                $em->flush();
                // Mise à jour du dernier dossier donnant avis pour chaque établissement
                foreach ($etablissements as $etab) {
                    $dernierDossier = $dossierRepository->findDernierDossierDonnantAvisParEtablissement($etab->getId());
                    if ($dernierDossier) {
                        $etab->setIdDossierDonnantAvis($dernierDossier);
                        $em->persist($etab);
                        $em->flush();
                    }
                }
                $this->addFlash('success', 'Le dossier a bien été supprimé.');
            } else {
                $this->addFlash('warning', 'Le dossier est lié à plusieurs établissements et n\'a pas été supprimé.');
            }

        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du dossier : ' . $e->getMessage());
        }
        return $this->redirect('/search/dossier?objet=&page=1');
    }

    /**
     * @Route("/dossier/{id}/retablir", name="retablir_dossier", methods={"GET"})
     */
    public function retablir(int $id, DossierRepository $dossierRepository, EntityManagerInterface $em): RedirectResponse {
        try {
            $dossier = $dossierRepository->find($id);
            if (!$dossier) {
                throw $this->createNotFoundException('Dossier introuvable');
            }
            $dossier->setDateSuppression(null);
            $dossier->setDeletedBy(null);
            $em->flush();

            $this->addFlash('success', 'Le dossier a bien été rétabli.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors du rétablissement du dossier : ' . $e->getMessage());
        }
        return $this->redirectToRoute('dossier_index', ['id' => $id]);
    }
}
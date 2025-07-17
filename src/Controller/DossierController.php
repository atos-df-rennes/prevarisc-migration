<?php
namespace App\Controller;

use App\Entity\Dossier;
use App\Enum\PlatauStatutAvis;
use App\Repository\DossierTypeRepository;
use App\Repository\AvisRepository;
use App\Repository\DossierNatureListeRepository;
use App\Repository\DossierDocManquantRepository;
use App\Repository\CapsuleRubriqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\DossierRepository;
use App\Service\TempsRestant;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Enum\PlatauStatutPec;
use App\Repository\CommissionTypeRepository;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierAffectationRepository;
use App\Repository\GroupementTypeRepository;
use App\Service\DossierService;
use App\Service\UtilsDate;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Dto\DossierDto;
use Symfony\Component\Security\Core\Security;

/**
 * @Route(requirements={"id"="\d+"})
 */
class DossierController extends AbstractController
{
    /**
     * @Route("/dossier/{id}", name="dossier_index")
     */
    public function index(Dossier $dossier, Security $security, TempsRestant $tempsRestant, CapsuleRubriqueRepository $capsuleRubriqueRepository,
    DossierService $dossierService)
    {
        $dto = DossierDto::fromRequest($dossier);
        //Partie concernant Information Plat'AU 
        $platauConsultation = $dto->PlatauConsultation();
        $user = $this->getUser()->getId();
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
        $affichageAvis = $dto->absenceQuorum() || $dto->horsDelai() || $dto->etatDossier() || $dto->nonPrononce() || $dto->differeAvis();

        $etabData = $dossierService->getEtabInfos($dossier, null);
        $thirdPartyPlugins = explode(';', getenv('PREVARISC_THIRDPARTY_PLUGINS'));
        $isSignalement = in_array('Plugin_FacteurCriticite', $thirdPartyPlugins, true);

        return $this->render('dossier/index.html.twig', [
            'idUser' => $user,
            'idDossier' => $dto->dossierId(),
            'libelleType' => $dto->typeLibelle(),
            'idType' => $dto->typeId(),
            'nature' => $dto->natureLibelle(),
            'natureId' => $dto->natureId(),
            'objet' => $dto->objet(),
            'demandeur' => $dto->demandeur(),
            'lieuReunion' => $dto->lieuReunion(),
            'serviceInstructeur' =>$dto->serviceInstructeur(),
            'descriptifAnalyseRisque' =>$dto->descriptifAnalyseRisque(),
            'reglesADeroger' => $dto->reglesADeroger(),
            'justificatifDerogation' => $dto->justificatifDerogation(),
            'mesuresCompensatoires' => $dto->mesuresCompensatoires(),
            'mesuresComplementaires' =>$dto->mesuresComplementaires(),
            'horsDelai' =>$dto->horsDelai(),
            'etatDossier' => $dto->etatDossier(),
            'absenceQuorum' => $dto->absenceQuorum(),
            'celluleNonExploitée' => $dto->celluleNonExploitée(),
            'facteurDanger' => $dto->facteurDanger(),
            'nonPrononce' => $dto->nonPrononce(),
            'differeAvis' => $dto->differeAvis(),
            'dateCreation' => $dto->dateCreation(),
            'dateReunion' => $dto->dateReunion(),
            'dateMairie' => $dto->dateMairie(),
            'dateSecretariat' => $dto->dateSecretariat(),
            'dateSupprime' => $dto->dateSuppression(),
            'dateEnvoiTransit' => $dto->dateEnvoiTransit(),
            'dateSDIS' => $dto->dateSDIS(),
            'dateVisite' => $dto->dateVisite(),
            'dateRVRAT' => $dto->dateRVRAT(),
            'dateCommission' => $dto->dateCommission(),
            'datePrefecture' => $dto->datePrefecture(),
            'dateReponse' => $dto->dateReponse(),
            'dateTransfertCommission' => $dto->dateTransfertCommission(),
            'dateReceptionCommission' => $dto->dateReceptionCommission(),
            'delaiPrescription' => $dto->delaiPrescription(),
            'dateSignature' => $dto->dateSignature(),
            'dateIntervention' => $dto->dateIntervention(),           
            'heureIntervention' => $dto->dateIntervention()? $dto->dateIntervention()->format('H:i'):null,
            'dureeIntervention' => $dto->dureeIntervention(),
            'dureeDeplacement' => $dto->dureeDeplacement(),
            'echeancierTravaux' => $dto->echeancierTravaux(),
            'anomalie' => $dto->anomalie(),
            'operationSDIS' => $dto->operationSDIS(),
            'RCCI' => $dto->RCCI(),
            'REX' => $dto->REX(),
            'chargeSecurite' => $dto->chargeSecurite(),
            'gravitePrescription' => $dto->gravitePrescription(),
            'numIntervention' => $dto->numIntervention(),
            'numDocurba' => $dto->numDocurba(),
            'commissionDossier' => $dto->commission(),
            'dateCommissionDossier' => $dto->commission()? $dto->commission()->getDateCommissions()->last()->getId(): null,
            'typeCommissionDossier' => $dto->commission()? $dto->commission()->getType(): null,
            'avisDossier' => $dto->avisDossier(),
            'avisDossierCommission' => $dto->avisDossierCommission(),
            'preventionnisteDossier' => $dto->preventionnisteDossier(),
            'dossierDocManquant' => $dto->dossierDocManquant(),
            'observations' => $dto->observations(),
            'createurDossier' => $dto->createurDossier(),
            'idCreateurDossier' =>$dto->createurDossier()? $dto->createurDossier()->getInformations()->getId():null,
            'verrouDossier' => $dto->verrouDossier(),
            'verrouUserDossier' => $dto->verrouUserDossier(),
            'platauConsultation' => $dto->platauConsultation(),
            'dossierAffectation' =>$dto->dossierAffectation(),
            'hasAvisDerogation' => $dto->avisDerogations() ? true : false,
            
            'tempsRestant' => $tempsRestantReponse,
            'couleurTempsRestant' => $couleurTempsRestant,

            //concernant Statut PEC
            'statutPec' => $statutPec,
            'enumStatutPec' => $enumStatutPec,
            'pecEnErreur' => $pecEnErreur,
            'pecEnAttente' => $pecEnAttente,
            'pecARenvoyer' => $pecARenvoyer,
            'pecInconnu' => $pecInconnu,
            'pecEnPriseEncompte' => $pecEnPriseEnCompte,
            'classPec' => $classPec,
            'statutLabelPec' => $statutLabelPec,

            //concernant Statut Avis
            'statutAvis' => $statutAvis,
            'enumstatutAvis' => $enumStatutAvis,
            'avisInconnu' => $avisInconnu,
            'avisEnErreur' => $avisEnErreur,
            'avisTraite' => $avisTraite,
            'avisARenvoyer' => $avisARenvoyer,
            'avisEnCours' => $avisenCours,
            'classAvis' => $classAvis,

            'etabData' =>$etabData,
            'affichageAvis' => $affichageAvis,
            'isSignalement' => $isSignalement,
            'statutLabelAvis' => $statutLabelAvis,
            'nomOngletVerificationsTechniques' => $nomOngletVerificationsTechniques,
            'nomOngletEffectifsDegagements' => $nomOngletEffectifsDegagements,
 
        ]);
    }
    /**
     * @Route("/dossier/save", name="dossier_save", methods={"POST"})
     */
     public function save(Request $request)
    {
        $id = $request->request->get('id');

        return new JsonResponse(['id' => $id]);
    }
    /**
     * @Route("/dossier/formdocmanquant", name="form_doc_manquant", methods={"POST"})
     */
    public function formDocManquant(Request $request, DossierDocManquantRepository $docManquantRepo): Response
    {
        $numDoc = $request->get('numDoc');
        $dateDay = (new \DateTime())->format('d/m/Y');

        // Ce que tu affichais avec getDocManquant()
        $listeDoc = $docManquantRepo->findAll(); // ou méthode custom si besoin

        return $this->render('dossier/_form_doc_manquant.html.twig', [
            'numDocManquant' => $numDoc,
            'dateDay' => $dateDay,
            'listeDoc' => $listeDoc
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

    /**
     * @Route("/dossier/edit/{id}", name="dossier_edit")
     */
    public function edit(Dossier $dossier,TempsRestant $tempsRestant,Security $security, CapsuleRubriqueRepository $capsuleRubriqueRepository, 
        DossierService $dossierService, DossierTypeRepository $dossierTypeRepository,
        DossierNatureListeRepository $dossierNatureListe, DateCommissionRepository $dateCommissionRepository, 
        DossierAffectationRepository $dossierAffectationRepository,
        UtilsDate $utilsDate, CommissionTypeRepository $commissionTypeRepository, GroupementTypeRepository $groupementTypeRepository,
        AvisRepository $avisRepository ): Response {

        $dto = DossierDto::fromRequest($dossier);
        $user = $this->getUser()->getId();
        $idDossier = $dto->dossierId();
        $dossierType = $dossier->getType();
        $dossierNature = $dossier->getNature();
        $listeAvis = $avisRepository->findAll();
        $typesDossier = $dossierTypeRepository->findAll();
        $dossierNatureListe = $dossierNatureListe->findByTypeOrdered($dossierType);
        $typeServiceInstructeur = $dossier->getTypeServiceInstructeur();
        $etabData = $dossierService->getEtabInfos($dossier, null);
        if (!empty($etabData) && isset($etabData[0]['etablissement']) && $etabData[0]['etablissement'] !== null) {
            $informations = $etabData[0]['etablissement']->getInformations();
            if ($informations && count($informations->toArray()) > 0) {
                $genre = $informations->toArray()[0]->getIdGenre()->getId();
            }
        }
        $groupementTypes = $groupementTypeRepository->findAllGroupementTypes();

        $thirdPartyPlugins = explode(';', getenv('PREVARISC_THIRDPARTY_PLUGINS'));
        $isSignalement = in_array('Plugin_FacteurCriticite', $thirdPartyPlugins, true);

        $verificationsRubrique = $capsuleRubriqueRepository->findOneBy(['nomInterne' => 'descriptifVerificationsTechniques']);
        $effectifsRubrique = $capsuleRubriqueRepository->findOneBy(['nomInterne' => 'effectifsDegagementsDossier']);

        $nomOngletVerificationsTechniques = $verificationsRubrique ? $verificationsRubrique->getNom() : null;
        $nomOngletEffectifsDegagements = $effectifsRubrique ? $effectifsRubrique->getNom() : null;
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
        $affichageAvis = $dto->absenceQuorum() || $dto->horsDelai() || $dto->etatDossier() || $dto->nonPrononce() || $dto->differeAvis();

        $dateVisite = $dossier->getDateVisite();
        
        $platauConsultation = $dossier->getPlatauConsultation();
        $dateAvis = $platauConsultation ? $platauConsultation->getDateAvis() : null;

        
        // Gestion des dates liées à la visite
        $listeDateValue = '';
        $listeDateInput = '';
        $dateVisiteInfos = [];
        if ($dateVisite) {
            $dateVisiteInfos = $dateCommissionRepository->findInfosVisite($idDossier);
            $dateLiee = $dateCommissionRepository->getDateLieesV2($dateVisiteInfos["ID_DATECOMMISSION"]);

            $nbDates = count($dateLiee);
            foreach ($dateLiee as $ue) {
                if ($ue->getDateCommission()) {
                    $listeDateValue .= $utilsDate->formatDateWithDayName($ue->getDateCommission());
                    $listeDateInput .= $utilsDate->convertFromMySQL($ue->getDateCommission());
                }
                if (--$nbDates > 0) {
                    $listeDateValue .= ', ';
                    $listeDateInput .= ', ';
                }
            }
        }

        // Recherche des types de commissions
        $commissionTypes = $commissionTypeRepository->findAll();
        $arrayCommissions = [];
        foreach ($commissionTypes as $type) {
            $arrayCommissions[] = [
                'LIBELLE' => $type->getLibelle(),
                'ARRAY' => $type->getCommissions(),
            ];
        }
    

        // Recherche de la dernière commission affectée
        $listeDateAffectDossier = $dossierAffectationRepository->findByDossierOrderByDateCommission($idDossier);
        $dateCommValue = null;
        $dateCommInput = null;
        $idDateCommissionAffect = null;

        foreach ($listeDateAffectDossier as $ue) {
            $commission = $ue->getIdDateCommissionAffect();
            if ($commission && $commission->getDateCommission()) {
                $formatter = new \IntlDateFormatter(
                    'fr_FR',
                    \IntlDateFormatter::FULL,
                    \IntlDateFormatter::NONE,
                    'Europe/Paris',
                    \IntlDateFormatter::GREGORIAN,
                    'EEEE dd MMM yyyy'
                );
                $date = $commission->getDateCommission();
                $dateCommValue = $formatter->format($date);
                $dateCommInput = $date->format('d/m/Y');
                $idDateCommissionAffect = $commission->getId();
                break;
            }

        }

        return $this->render('dossier/edit.html.twig', [
            'idUser' => $user,
            'idDossier' => $dto->dossierId(),
            'libelleType' => $dto->typeLibelle(),
            'idType' => $dto->typeId(),
            'nature' => $dto->natureLibelle(),
            'natureId' => $dto->natureId(),
            'objet' => $dto->objet(),
            'demandeur' => $dto->demandeur(),
            'lieuReunion' => $dto->lieuReunion(),
            'serviceInstructeur' =>$dto->serviceInstructeur(),
            'descriptifAnalyseRisque' =>$dto->descriptifAnalyseRisque(),
            'reglesADeroger' => $dto->reglesADeroger(),
            'justificatifDerogation' => $dto->justificatifDerogation(),
            'mesuresCompensatoires' => $dto->mesuresCompensatoires(),
            'mesuresComplementaires' =>$dto->mesuresComplementaires(),
            'horsDelai' =>$dto->horsDelai(),
            'etatDossier' => $dto->etatDossier(),
            'absenceQuorum' => $dto->absenceQuorum(),
            'celluleNonExploitée' => $dto->celluleNonExploitée(),
            'facteurDanger' => $dto->facteurDanger(),
            'nonPrononce' => $dto->nonPrononce(),
            'differeAvis' => $dto->differeAvis(),
            'dateCreation' => $dto->dateCreation(),
            'dateReunion' => $dto->dateReunion(),
            'dateMairie' => $dto->dateMairie(),
            'dateSecretariat' => $dto->dateSecretariat(),
            'dateSupprime' => $dto->dateSuppression(),
            'dateEnvoiTransit' => $dto->dateEnvoiTransit(),
            'dateSDIS' => $dto->dateSDIS(),
            'dateVisite' => $dto->dateVisite(),
            'dateRVRAT' => $dto->dateRVRAT(),
            'dateCommission' => $dto->dateCommission(),
            'datePrefecture' => $dto->datePrefecture(),
            'dateReponse' => $dto->dateReponse(),
            'dateTransfertCommission' => $dto->dateTransfertCommission(),
            'dateReceptionCommission' => $dto->dateReceptionCommission(),
            'delaiPrescription' => $dto->delaiPrescription(),
            'dateSignature' => $dto->dateSignature(),
            'dateIntervention' => $dto->dateIntervention(),           
            'heureIntervention' => $dto->dateIntervention()? $dto->dateIntervention()->format('H:i'):null,
            'dureeIntervention' => $dto->dureeIntervention(),
            'dureeDeplacement' => $dto->dureeDeplacement(),
            'echeancierTravaux' => $dto->echeancierTravaux(),
            'anomalie' => $dto->anomalie(),
            'operationSDIS' => $dto->operationSDIS(),
            'RCCI' => $dto->RCCI(),
            'REX' => $dto->REX(),
            'chargeSecurite' => $dto->chargeSecurite(),
            'gravitePrescription' => $dto->gravitePrescription(),
            'numIntervention' => $dto->numIntervention(),
            'numDocurba' => $dto->numDocurba(),
            'commissionInfos' => $dto->commission(),
            'dateCommissionDossier' => $dto->commission()? $dto->commission()->getDateCommissions()->last()->getId(): null,
            'typeCommissionDossier' => $dto->commission()? $dto->commission()->getType(): null,
            'avisDossier' => $dto->avisDossier(),
            'avisDossierCommission' => $dto->avisDossierCommission(),
            'preventionnisteDossier' => $dto->preventionnisteDossier(),
            'dossierDocManquant' => $dto->dossierDocManquant(),
            'observations' => $dto->observations(),
            'verrouDossier' => $dto->verrouDossier(),
            'platauConsultation' => $dto->platauConsultation(),
            'dossierAffectation' =>$dto->dossierAffectation(),
            'hasAvisDerogation' => $dto->avisDerogations() ? true : false,

            'dossier' => $dossier,
            'dossierType' => $dossierType,
            'typesDossier' => $typesDossier,
            'dossierNature' => $dossierNature,
            'dossierNatureListe' => $dossierNatureListe,
            
            'typeServiceInstructeur' => $typeServiceInstructeur,
            'etabData' => $etabData,
            'idDateVisiteAffect' => $dateVisiteInfos["ID_DATECOMMISSION_AFFECT"],

            'dateVisiteInfos' => $dateVisiteInfos,
            'dateVisiteValue' => $listeDateValue,
            'dateVisiteInput' => $listeDateInput,
            'arrayCommissions' => $arrayCommissions,
            'dateCommValue' => $dateCommValue,
            'dateCommInput' => $dateCommInput,
            'idDateCommissionAffect' => $idDateCommissionAffect,
            'tempsRestant' => $tempsRestantReponse,
            'couleurTempsRestant' => $couleurTempsRestant,
            'statutPec' => $statutPec,
            'enumStatutPec' => $enumStatutPec,
            'pecEnErreur' => $pecEnErreur,
            'pecEnAttente' => $pecEnAttente,
            'pecARenvoyer' => $pecARenvoyer,
            'pecInconnu' => $pecInconnu,
            'pecEnPriseEncompte' => $pecEnPriseEnCompte,
            'genre' => $genre,
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
            'statutLabelAvis' => $statutLabelAvis,
            'groupementTypes' => $groupementTypes,
            'listeAvis' => $listeAvis,
            'dateAvis' => $dateAvis,
            'platauConsultation' => $platauConsultation,
            'moisList' => ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
            'moisActuel' => (int) date('m'),
            'annéeActuelle' => (int) date('Y'),
            'nomOngletVerificationsTechniques' => $nomOngletVerificationsTechniques,
            'nomOngletEffectifsDegagements' => $nomOngletEffectifsDegagements,
            'dateSupprime' => $dossier->getDateSuppression(),
            'isSignalement' => $isSignalement,
            'affichageAvis' => $affichageAvis,

        ]);
    }

    /**
     * @Route("/dossier/shownature", name="dossier_show_nature", methods={"POST"})
     */
    public function showNature(Request $request, DossierNatureListeRepository $dossierNatureListeRepository): Response
    {
        $idType = (int) $request->request->get('idType');

        $dossierNatureListe = $dossierNatureListeRepository->findBy(
            ['type' => $idType],
            ['ordre' => 'ASC']
        );

        return $this->render('dossier/_nature_select.html.twig', [
            'dossierNatureListe' => $dossierNatureListe,
        ]);
    }

}
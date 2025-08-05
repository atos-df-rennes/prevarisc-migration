<?php
namespace App\Controller;

use App\Entity\Dossier;
use App\Enum\PlatauStatutAvis;
use App\Repository\DossierTypeRepository;
use App\Repository\AvisRepository;
use App\Repository\DossierNatureListeRepository;
use App\Entity\DossierNatureListe;
use App\Entity\Commission;
use App\Entity\DossierDocManquant;
use App\Entity\Avis;
use App\Entity\DossierType;
use App\Entity\Utilisateur;
use App\Repository\DocManquantRepository;
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
use App\Entity\DossierDocurba;
use Symfony\Component\Security\Core\Security;

/**
 * @Route(requirements={"id"="\d+"})
 */
class DossierController extends AbstractController
{

    private const FIELD_MAPPING = [
        'DATEMAIRIE_DOSSIER' => 'setDateMairie',
        'DATESECRETARIAT_DOSSIER' => 'setDateSecretariat',
        'DATEVISITE_DOSSIER' => 'setDateVisite',
        'DATECOMM_DOSSIER' => 'setDateCommission',
        'DATESDIS_DOSSIER' => 'setDateSdis',
        'DATEPREF_DOSSIER' => 'setDatePrefecture',
        'DATEREP_DOSSIER' => 'setDateReponse',
        'DATEREUN_DOSSIER' => 'setDateReunion',
        'DATEINSERT_DOSSIER' => 'setDateInsert', 
        'DATEENVTRANSIT_DOSSIER' => 'setDateEnvTransit',
        'DATETRANSFERTCOMM_DOSSIER' => 'setDateTransfertCommission',
        'DATERECEPTIONCOMM_DOSSIER' => 'setDateReceptionCommission',
        'DATERVRAT_DOSSIER' => 'setDateRVRAT',
        'DELAIPRESC_DOSSIER' => 'setDelaiPrescription',
        'DATESIGN_DOSSIER' => 'setDateSignature',
        'DATEINTERV_DOSSIER' => 'setDateIntervention',
    ];

    /**
     * @Route("/dossier/{id}", name="dossier_index")
     */
    public function index(Dossier $dossier, Security $security, TempsRestant $tempsRestant, CapsuleRubriqueRepository $capsuleRubriqueRepository,
    DossierService $dossierService, GroupementTypeRepository $groupementTypeRepository)
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

        $groupementTypes = $groupementTypeRepository->findAllGroupementTypes();
        $libellesGroupements = [];
        foreach ($groupementTypes as $type) {
            foreach ($type->getGroupements() as $groupement) {
                $libellesGroupements[$groupement->getId()] = $groupement->getLibelle();
            }
        }

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
            'libellesGroupements' => $libellesGroupements,
            'serviceInstructeur' =>$dto->serviceInstructeur(),
            'typeServiceInstructeur' => $dossier->getTypeServiceInstructeur(),
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
    public function saveEdition(Request $request, EntityManagerInterface $em, GroupementTypeRepository $groupementTypeRepository): JsonResponse
    {

        $idDossier = $request->request->get('idDossier');
        $dossier = $em->getRepository(Dossier::class)->find($idDossier);
        if (!$dossier) {
            return new JsonResponse(['error' => 'Dossier introuvable.'], 404);
        }
        $newNatureId = (int) $request->request->get('selectNature');
        $newTypeId = (int) $request->request->get('TYPE_DOSSIER');
        $commissionId = $request->request->get('COMMISSION_DOSSIER');
        $avisCommissionId = $request->request->get('AVIS_DOSSIER_COMMISSION');
        $avisDossierId = $request->request->get('AVIS_DOSSIER');
        $servInst = $request->request->get('servInst');
        $servInstGrp = $request->request->get('servInstGrp');
        $operSdis = $request->request->get('OPERSDIS_DOSSIER');
        $preventionnisteIds = $request->request->get('preventionniste');
        $servInstVille = $request->request->get('servInstVille');
        $dureeIntervStr = $request->request->get('DUREEINTERV_DOSSIER');
        $numInterv = $request->request->get('NUMINTERV_DOSSIER');
        $docManquantArray = $request->request->get('docManquant', []);
        $dateDocManquantArray = $request->request->get('dateDocManquant', []);
        $dateReceptionDocArray = $request->request->get('dateReceptionDocManquant', []);
        $incomplet = $request->request->get('INCOMPLET_DOSSIER', 0);
       
        $newNature = $em->getRepository(DossierNatureListe::class)->find($newNatureId);
        $newType = $em->getRepository(DossierType::class)->find($newTypeId);
        if (!empty($commissionId) && (int)$commissionId !== 0) {
            $newCommission = $em->getRepository(Commission::class)->find($commissionId);
        }else{
            $newCommission =null;
        }
        
        if (!empty($avisCommissionId) && (int)$avisCommissionId !== 0) {
            $newAvisCommission = $em->getRepository(Avis::class)->find($avisCommissionId);
        } else {
            $newAvisCommission = null;
        }
        
        if (!empty($avisDossierId) && (int)$avisDossierId !== 0) {
            $newAvisDossier = $em->getRepository(Avis::class)->find($avisDossierId);
        } else {
            $newAvisDossier = null;
        }

        $excludes = [
            'DATEVISITE_PERIODIQUE',
            'selectNature',
            'NUM_DOCURBA',
            'natureId',
            'docUrba',
            'idDossier',
            'HEUREINTERV_DOSSIER',
            'ID_AFFECTATION_DOSSIER_VISITE',
            'ID_AFFECTATION_DOSSIER_COMMISSION',
            'preventionniste',
            'commissionSelect',
            'ID_CREATEUR',
            'HORSDELAI_DOSSIER',
            'docManquant',
            'dateReceptionDocManquant',
            'dateDocManquant',
            'ABSQUORUM_DOSSIER',
            'servInst',
            'servInstVille',
            'servInstGrp',
            'repercuterAvis',
            'INCOMPLET_DOSSIER',
            'COMMISSION_DOSSIER',
            'AVIS_DOSSIER_COMMISSION',
            'AVIS_DOSSIER',
            'TYPE_DOSSIER',
        ];

        $includes = [
            'DATEMAIRIE_DOSSIER',
            'DATESECRETARIAT_DOSSIER',
            'DATEVISITE_DOSSIER',
            'DATECOMM_DOSSIER',
            'DATESDIS_DOSSIER',
            'DATEPREF_DOSSIER',
            'DATEREP_DOSSIER',
            'DATEREUN_DOSSIER',
            'DATEINTERV_DOSSIER',
            'DATESIGN_DOSSIER',
            'DATEINSERT_DOSSIER',
            'DATEENVTRANSIT_DOSSIER',
            'ECHEANCIERTRAV_DOSSIER',
            'DATETRANSFERTCOMM_DOSSIER',
            'DATERECEPTIONCOMM_DOSSIER',
            'DATERVRAT_DOSSIER',
            'DELAIPRESC_DOSSIER',
        ];

        $formData = $request->request->all();
        //Traitement des dates + les autres champs pas dans excludes
        foreach ($formData as $field => $value) {
            if (!in_array($field, $excludes)) {
                // Traitement des dates
                if (in_array($field, $includes) ) {
                    $this->setDateField($dossier, $field, $value, $formData['HEUREINTERV_DOSSIER'] ?? null);
                }
                else{
                    $this->setEntityField($dossier, $field, $value);
                }

            }
        }
        $dossier->setNature($newNature);
        $dossier->setType($newType);
        $dossier->setEtatDossier((int)$incomplet === 1);
        $dossier->setCommissionDossier($newCommission);
        $dossier->setAvisDossierCommission($newAvisCommission);
        $dossier->setAvisDossier($newAvisDossier);
        $dossier->setHorsdelai($request->request->get('HORSDELAI_DOSSIER') ? 1 : 0);
        $dossier->setAbsenceQuorum($request->request->get('ABSQUORUM_DOSSIER') ? 1 : 0);
        $dossier->setNonPrononce($request->request->get('NPSP_DOSSIER') ? 1 : 0);
        $dossier->setDiffereAvis($request->request->get('DIFFEREAVIS_DOSSIER') ? 1 : 0);
        $dossier->setCelluleNonExploitée($request->request->get('CNE_DOSSIER') ? 1 : 0);
        if($operSdis !== null){
            $dossier->setOperationSDIS($operSdis);  
        }
         //partie concernant numInterv et dureeInterv
        if (!empty($dureeIntervStr)) {
            try {
                $dureeInterv = \DateTime::createFromFormat('H:i', $dureeIntervStr);
                if ($dureeInterv !== false) {
                    $dossier->setDureeIntervention($dureeInterv);
                }
            } catch (\Exception $e) {
            }
        } else {
            $dossier->setDureeIntervention(null);
        }
        if ($numInterv !== null && $numInterv !== '') {
            $dossier->setNumIntervention((int)$numInterv);
        } else {
            $dossier->setNumIntervention(null);
        }

        //NumDocurba:
        // Supprimer les anciens numéros
        foreach ($dossier->getNumDossierDocurba() as $doc) {
            $em->remove($doc);
        }
        $dossier->getNumDossierDocurba()->clear();

        // Ajouter les nouveaux
        $docUrbaList = $request->request->get('docUrba');
        if (!empty($docUrbaList)) {
            foreach ($docUrbaList as $numDoc) {
                if (trim($numDoc) !== '') {
                    $doc = new DossierDocurba();
                    $doc->setNumDocurba($numDoc);
                    $doc->setDossier($dossier); 
                    $dossier->addNumDossierDocurba($doc);
                    $em->persist($doc);
                }
            }
        }

        //Préventionnistes:
        if (!empty($preventionnisteIds)) {
            // Nettoyer les anciens préventionnistes :
            $dossier->getPreventionnisteDossier()->clear();

            foreach ($preventionnisteIds as $userId) {
                $user = $em->getRepository(Utilisateur::class)->find((int)$userId);
                if ($user) {
                    $dossier->addPreventionnisteDossier($user);
                }
            }
        }

        //Partie concernant service instructeur
        $groupementTypes = $groupementTypeRepository->findAllGroupementTypes();
        $libellesGroupements = [];
        foreach ($groupementTypes as $type) {
            foreach ($type->getGroupements() as $groupement) {
                $libellesGroupements[$groupement->getId()] = $groupement->getLibelle();
            }
        }
        
        if (null !== $servInst) {
            if ($servInst === 'servInstGrp') {
                $dossier->setTypeServiceInstructeur($servInst);
                $dossier->setServiceInstructeur($libellesGroupements[$servInstGrp]);
            } elseif ($servInst === 'servInstCommune') {
                $dossier->setTypeServiceInstructeur($servInst);
                $dossier->setServiceInstructeur($servInstVille);
            }
        }
        // Partie pour DocManquant
        foreach ($dossier->getDossierDocManquants() as $doc) {
            $em->remove($doc);
        }
        $dossier->getDossierDocManquants()->clear();

        foreach ($docManquantArray as $index => $text) {
            if (trim($text) === '') {
                continue;
            }

            $docManquant = new DossierDocManquant();
            $docManquant->setIdDossier($dossier);
            $docManquant->setNumDocsManquant((string)$index); 
            $docManquant->setDocManquant($text);

            // Date du document manquant
            if (!empty($dateDocManquantArray[$index])) {
                $date = \DateTime::createFromFormat('d/m/Y', $dateDocManquantArray[$index]);
                if ($date !== false) {
                    $docManquant->setDateDocsManquant($date);
                }
            }
            // Date de réception du document
            if (!empty($dateReceptionDocArray[$index])) {
                $dateRecep = \DateTime::createFromFormat('d/m/Y', $dateReceptionDocArray[$index]);
                if ($dateRecep !== false) {
                    $docManquant->setDateReceptionDoc($dateRecep);
                }
            }
            $em->persist($docManquant);
            $dossier->addDossierDocManquant($docManquant); 
        }

        $em->persist($dossier);
        $em->flush();

        return new JsonResponse([
            'id' => $idDossier
        ]);

                
    }

    private function formatDate(?string $value, ?string $heure = null): ?\DateTime
    {
        if (empty($value)) {
            return null;
        }
        try {
            $date = \DateTime::createFromFormat('d/m/Y', $value);

            if (!$date) {
                return null; 
            }
            if ($heure) {
                $parts = explode(':', $heure);
                if (count($parts) >= 2) {
                    $date->setTime((int)$parts[0], (int)$parts[1]);
                }
            }
            return $date;
        } catch (\Exception $e) {
            return null;  
        }
    }

    private function setDateField($entity, $field, ?string $value, ?string $heure = null): void
    {
        if($field === 'ECHEANCIERTRAV_DOSSIER'){
            $method = 'setEcheancierTravaux';
            $entity->$method($this->formatDate($value, $heure));
            return; 
        }
        $method = self::FIELD_MAPPING[$field] ?? null;
        if ($method === null || !method_exists($entity, $method)) {
            error_log('Setter manquant : ' . $field);
            return;
        }
        if ($field === 'DATECOMM_DOSSIER') {
            $entity->$method($value); 
            return;
        }
        $dateValue = $this->formatDate($value, $heure);
        $entity->$method($dateValue);
    }

    private function setEntityField($entity, $field, $value): void
    {
        $method = 'set' . str_replace('_Dossier', '', ucwords(strtolower($field), '_'));
        if($field === 'OBSERVATION_DOSSIER'){
             $method = 'setObservations';
        }
        if($field === 'FACTDANGE_DOSSIER'){
            $method = 'setFacteurDanger';
        }
        if (method_exists($entity, $method)) {
            $entity->$method($value);
        }
    }

    /**
     * @Route("/dossier/formdocmanquant", name="form_doc_manquant", methods={"POST"})
     */
    public function formDocManquant(Request $request, DocManquantRepository $docManquantRepo): Response
    {
        $numDoc = $request->get('numDoc');
        $dateDay = (new \DateTime())->format('d/m/Y');

        $listeDoc = $docManquantRepo->findAll(); 
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
        DossierNatureListeRepository $dossierNatureListeRepository, DateCommissionRepository $dateCommissionRepository, 
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
        $dossierNatureListe = $dossierNatureListeRepository->findByTypeOrdered($dossierType);
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
        $platauConsultation = $dossier->getPlatauConsultation();
        $dateAvis = $platauConsultation ? $platauConsultation->getDateAvis() : null;

        $listeDateValue = '';
        $listeDateInput = '';
        $dateCommValue = null;
        $dateCommInput = null;
        $idDateCommissionAffect = null;

        $listeDateAffectDossier = $dossierAffectationRepository->findByDossierOrderByDateCommission($idDossier);
        foreach ($listeDateAffectDossier as $affectation) {
            $commission = $affectation->getIdDateCommissionAffect();

            if (!$commission || !$commission->getDateCommission()) {
                continue;
            }

            $date = $commission->getDateCommission();
            $typeEvenement = $commission->getCommissionTypeEvenement()->getId();

            if ($dossierType === 1) {
                // Étude
                $formatter = new \IntlDateFormatter(
                    'fr_FR',
                    \IntlDateFormatter::FULL,
                    \IntlDateFormatter::NONE,
                    'Europe/Paris',
                    \IntlDateFormatter::GREGORIAN,
                    'EEEE dd MMM yyyy'
                );
                $dateCommValue = $formatter->format($date);
                $dateCommInput = $date->format('d/m/Y');
                $idDateCommissionAffect = $commission->getId();
                break;

            } elseif (in_array($dossierType, [2, 3])) {

                if ($typeEvenement == 1) {
                    // Commission en salle
                    $formatter = new \IntlDateFormatter(
                        'fr_FR',
                        \IntlDateFormatter::FULL,
                        \IntlDateFormatter::NONE,
                        'Europe/Paris',
                        \IntlDateFormatter::GREGORIAN,
                        'EEEE dd MMM yyyy'
                    );
                    $dateCommValue = $formatter->format($date);
                    $dateCommInput = $date->format('d/m/Y');
                    $idDateCommissionAffect = $commission->getId();
                    break;

                } else {
                    // Visite (avec plusieurs dates liées)
                    $dateVisiteInfos = $dateCommissionRepository->findInfosVisite($idDossier);
                    $dateLiees = $dateCommissionRepository->getDateLieesV2($dateVisiteInfos['ID_DATECOMMISSION']);

                    $nbDates = count($dateLiees);

                    foreach ($dateLiees as $liee) {
                        $dateL = $liee->getDateCommission();
                        if ($dateL) {
                            $listeDateValue .= $utilsDate->formatDateWithDayName($dateL);
                            $listeDateInput .= $utilsDate->convertFromMySQL($dateL);

                            if (--$nbDates > 0) {
                                $listeDateValue .= ', ';
                                $listeDateInput .= ', ';
                            }
                        }
                    }

                    $idDateCommissionAffect = $dateVisiteInfos['ID_DATECOMMISSION'];
                    break;
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
            'idDateVisiteAffect' => $idDateCommissionAffect,

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
     * @Route("/dossier/edit/{id}/nature/{natureId}", name="dossier_edit_nature_partial")
     */
    public function editNaturePartial(Dossier $dossier,TempsRestant $tempsRestant, CapsuleRubriqueRepository $capsuleRubriqueRepository, 
        DossierService $dossierService, DossierTypeRepository $dossierTypeRepository,
        DossierNatureListeRepository $dossierNatureListeRepository, DateCommissionRepository $dateCommissionRepository, 
        DossierAffectationRepository $dossierAffectationRepository,
        UtilsDate $utilsDate, CommissionTypeRepository $commissionTypeRepository, GroupementTypeRepository $groupementTypeRepository,
        AvisRepository $avisRepository, int $natureId ): Response {

        $dto = DossierDto::fromRequest($dossier);
        $user = $this->getUser()->getId();
        $idDossier = $dto->dossierId();
        $dossierNature = $dossierNatureListeRepository->find($natureId);
        $dossierType = $dossierNature ? $dossierNature->getType() : $dossier->getType(); 
        $listeAvis = $avisRepository->findAll();
        $typesDossier = $dossierTypeRepository->findAll();
        $dossierNatureListe = $dossierNatureListeRepository->findByTypeOrdered($dossierType);
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
        $platauConsultation = $dossier->getPlatauConsultation();
        $dateAvis = $platauConsultation ? $platauConsultation->getDateAvis() : null;

        // Gestion des dates liées à la visite
        $listeDateValue = '';
        $listeDateInput = '';
        $dateCommValue = null;
        $dateCommInput = null;
        $idDateCommissionAffect = null;

        $listeDateAffectDossier = $dossierAffectationRepository->findByDossierOrderByDateCommission($idDossier);
        foreach ($listeDateAffectDossier as $affectation) {
            $commission = $affectation->getIdDateCommissionAffect();

            if (!$commission || !$commission->getDateCommission()) {
                continue;
            }

            $date = $commission->getDateCommission();
            $typeEvenement = $commission->getCommissionTypeEvenement()->getId();

            if ($dossierType === 1) {
                // Étude
                $formatter = new \IntlDateFormatter(
                    'fr_FR',
                    \IntlDateFormatter::FULL,
                    \IntlDateFormatter::NONE,
                    'Europe/Paris',
                    \IntlDateFormatter::GREGORIAN,
                    'EEEE dd MMM yyyy'
                );
                $dateCommValue = $formatter->format($date);
                $dateCommInput = $date->format('d/m/Y');
                $idDateCommissionAffect = $commission->getId();
                break;

            } elseif (in_array($dossierType, [2, 3])) {

                if ($typeEvenement == 1) {
                    // Commission en salle
                    $formatter = new \IntlDateFormatter(
                        'fr_FR',
                        \IntlDateFormatter::FULL,
                        \IntlDateFormatter::NONE,
                        'Europe/Paris',
                        \IntlDateFormatter::GREGORIAN,
                        'EEEE dd MMM yyyy'
                    );
                    $dateCommValue = $formatter->format($date);
                    $dateCommInput = $date->format('d/m/Y');
                    $idDateCommissionAffect = $commission->getId();
                    break;

                } else {
                    // Visite (avec plusieurs dates liées)
                    $dateVisiteInfos = $dateCommissionRepository->findInfosVisite($idDossier);
                    $dateLiees = $dateCommissionRepository->getDateLieesV2($dateVisiteInfos['ID_DATECOMMISSION']);

                    $nbDates = count($dateLiees);

                    foreach ($dateLiees as $liee) {
                        $dateL = $liee->getDateCommission();
                        if ($dateL) {
                            $listeDateValue .= $utilsDate->formatDateWithDayName($dateL);
                            $listeDateInput .= $utilsDate->convertFromMySQL($dateL);

                            if (--$nbDates > 0) {
                                $listeDateValue .= ', ';
                                $listeDateInput .= ', ';
                            }
                        }
                    }

                    $idDateCommissionAffect = $dateVisiteInfos['ID_DATECOMMISSION'];
                    break;
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
        return $this->render('dossier/edit.html.twig', [
            'idUser' => $user,
            'idDossier' => $dto->dossierId(),
            'libelleType' => $dto->typeLibelle(),
            'idType' => $dossierType ? $dossierType->getId() : null, 
            'nature' => $dto->natureLibelle(),
            'natureId' => $natureId,
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
            'idDateVisiteAffect' => $idDateCommissionAffect,

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
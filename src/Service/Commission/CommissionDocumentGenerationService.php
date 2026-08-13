<?php

namespace App\Service\Commission;

use App\Constant\AvisCommission;
use App\Constant\CommissionTypeEvenement;
use App\Constant\NomInterneCapsule;
use App\Constant\TypePrescription;
use App\Entity\CapsuleRubrique;
use App\Entity\DateCommission;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\PieceJointe;
use App\Entity\Rubrique;
use App\Repository\DateCommissionRepository;
use App\Service\DataStore\DataStore;
use App\Service\Descriptif;
use App\Service\DossierPrescriptionService;
use App\Service\Odt\OdtWriter;
use App\Service\Rapport\PrescriptionArrayHelper;
use App\Utils\DateFormatterFr;
use App\Utils\PathNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Segment;

/**
 * Service de génération des documents ODT de commission (convocations, ODJ, PV, compte-rendu).
 *
 * Port direct des actions generationconvocAction, generationodjAction, generationpvAction,
 * generationcompterenduAction du CalendrierDesCommissionsController legacy et de leurs templates
 * phtml associés.
 */
class CommissionDocumentGenerationService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var DataStore */
    private $dataStore;

    /** @var LoggerInterface */
    private $logger;

    /** @var CommissionMembreEligibilityChecker */
    private $eligibilityChecker;

    /** @var string */
    private $courriersMembresRealDataPath;

    /** @var string */
    private $documentsCommissionRealDataPath;

    /** @var Descriptif */
    private $descriptif;

    /** @var DateCommissionRepository */
    private $dateCommissionRepo;

    /** @var OdtWriter */
    private $odtWriter;

    /** @var DossierPrescriptionService */
    private $prescriptionService;

    /** @var CommissionDataProvider */
    private $dataProvider;

    public function __construct(
        EntityManagerInterface $em,
        DataStore $dataStore,
        LoggerInterface $logger,
        CommissionMembreEligibilityChecker $eligibilityChecker,
        Descriptif $descriptif,
        DateCommissionRepository $dateCommissionRepo,
        OdtWriter $odtWriter,
        DossierPrescriptionService $prescriptionService,
        CommissionDataProvider $dataProvider,
        string $courriersMembresRealDataPath,
        string $documentsCommissionRealDataPath
    ) {
        $this->em = $em;
        $this->dataStore = $dataStore;
        $this->logger = $logger;
        $this->eligibilityChecker = $eligibilityChecker;
        $this->descriptif = $descriptif;
        $this->dateCommissionRepo = $dateCommissionRepo;
        $this->odtWriter = $odtWriter;
        $this->prescriptionService = $prescriptionService;
        $this->dataProvider = $dataProvider;
        $this->courriersMembresRealDataPath = $courriersMembresRealDataPath;
        $this->documentsCommissionRealDataPath = $documentsCommissionRealDataPath;
    }

    /**
     * Génère les convocations pour une date de commission.
     *
     * @param int $dateCommId ID de la dateCommission
     *
     * @return array<int, array{memberId: int, memberLabel: string, status: string, message: string, pieceJointeId: int|null}>
     */
    public function generateConvocations(int $dateCommId): array
    {
        $results = [];

        $commissionInfo = $this->dataProvider->getDateCommissionInfo($dateCommId);
        if (null === $commissionInfo) {
            return $results;
        }

        /** @var int|string $typeCommissionRaw */
        $typeCommissionRaw = $commissionInfo['ID_COMMISSIONTYPEEVENEMENT'] ?? 0;
        $typeCommission = (int) $typeCommissionRaw;
        /** @var int|string $gestionHeuresRaw */
        $gestionHeuresRaw = $commissionInfo['GESTION_HEURES'] ?? 0;
        $listeDossiers = $this->dateCommissionRepo->findDossiersForGeneration($dateCommId, (int) $gestionHeuresRaw);
        $listeDossiers = $this->enrichDossiers($listeDossiers, false);

        /** @var int|string $commissionConcerneRaw */
        $commissionConcerneRaw = $commissionInfo['COMMISSION_CONCERNE'] ?? 0;
        $listeMembres = $this->dataProvider->loadMembresWithCompetences((int) $commissionConcerneRaw);
        $commissionInfos = $this->dataProvider->getCommissionInfo((int) $commissionConcerneRaw);
        if (null === $commissionInfos) {
            return $results;
        }

        $listeCommunes = $this->buildListeCommunes($listeDossiers);

        /** @var string|null $dateCommRaw */
        $dateCommRaw = $commissionInfo['DATE_COMMISSION'] ?? '';
        $dateComm = (string) ($dateCommRaw ?? '');
        /** @var string|null $heureDebRaw */
        $heureDebRaw = $commissionInfo['HEUREDEB_COMMISSION'] ?? '';
        $heureDeb = (string) ($heureDebRaw ?? '');

        foreach ($listeMembres as $membre) {
            /** @var array<string, mixed> $membreFile */
            $membreFile = $this->getMembreFirstFile($membre);
            /** @var string|null $membreLabelRaw */
            $membreLabelRaw = $membreFile['LIBELLE_COMMISSIONMEMBRE'] ?? '';
            $membreLabel = (string) ($membreLabelRaw ?? '');
            /** @var int|string $membreIdRaw */
            $membreIdRaw = $membreFile['ID_COMMISSIONMEMBRE'] ?? 0;
            $membreId = (int) $membreIdRaw;

            // Déterminer le nom du fichier template
            $erreurFile = false;
            $nomFichier = '';

            if (CommissionTypeEvenement::TYPE_COMMISSION_SALLE === $typeCommission) {
                if (empty($membreFile['COURRIER_CONVOCATIONSALLE'])) {
                    $erreurFile = true;
                } else {
                    /** @var int|string $membreIdConvRaw */
                    $membreIdConvRaw = $membreFile['ID_COMMISSIONMEMBRE'] ?? '';
                    /** @var int|string $courrierSalleRaw */
                    $courrierSalleRaw = $membreFile['COURRIER_CONVOCATIONSALLE'];
                    $nomFichier = $membreIdConvRaw.'CONVOCATIONSALLE_'.$courrierSalleRaw;
                }
            } elseif (CommissionTypeEvenement::TYPE_VISITE === $typeCommission || CommissionTypeEvenement::TYPE_GROUPE_VISITE === $typeCommission) {
                if (empty($membreFile['COURRIER_CONVOCATIONVISITE'])) {
                    $erreurFile = true;
                } else {
                    /** @var int|string $membreIdConvRaw */
                    $membreIdConvRaw = $membreFile['ID_COMMISSIONMEMBRE'] ?? '';
                    /** @var int|string $courrierVisiteRaw */
                    $courrierVisiteRaw = $membreFile['COURRIER_CONVOCATIONVISITE'];
                    $nomFichier = $membreIdConvRaw.'CONVOCATIONVISITE_'.$courrierVisiteRaw;
                }
            }

            // Vérifier si au moins un dossier concerne le membre
            $cptConcerne = 0;
            foreach ($listeCommunes as $commune) {
                foreach ($listeDossiers as $dossierInfos) {
                    /** @var array<string, mixed> $dInfosEtabConv */
                    $dInfosEtabConv = $dossierInfos['infosEtab'] ?? [];
                    /** @var array<int, array<string, mixed>> $adresses */
                    $adresses = $dInfosEtabConv['adresses'] ?? [];
                    if (
                        count($adresses) > 0
                        && !empty($commune[0])
                        && ($adresses[0]['LIBELLE_COMMUNE'] ?? '') == $commune[0]
                        && $this->eligibilityChecker->isDossierConcerneMembre($membre, $dossierInfos)
                    ) {
                        ++$cptConcerne;
                    }
                }
            }

            if ($erreurFile || 0 === $cptConcerne) {
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le membre '.$membreLabel." n'a aucun document renseigné ou n'a pas les droits pour ces dossiers",
                    'pieceJointeId' => null,
                ];

                continue;
            }

            $templatePath = PathNormalizer::join($this->courriersMembresRealDataPath, $nomFichier);

            if (!file_exists($templatePath)) {
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le membre '.$membreLabel." n'a pas de fichier template renseigné",
                    'pieceJointeId' => null,
                ];

                continue;
            }

            try {
                if (empty($membreFile['ID_GROUPEMENT'])) {
                    // Génération par commune (ex. : maires)
                    $pjId = $this->generateConvocationParCommune(
                        $templatePath,
                        $listeDossiers,
                        $membre,
                        $listeCommunes,
                        $commissionInfos,
                        $dateComm,
                        $heureDeb,
                        $dateCommId
                    );
                } else {
                    // Génération par groupement
                    $pjId = $this->generateConvocationParGroupement(
                        $templatePath,
                        $listeDossiers,
                        $membre,
                        $listeCommunes,
                        $commissionInfos,
                        $dateComm,
                        $heureDeb,
                        $dateCommId
                    );
                }

                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'success',
                    'message' => 'Le document a bien été généré pour le membre '.$membreLabel,
                    'pieceJointeId' => $pjId,
                ];
            } catch (\Exception $e) {
                $this->logger->error('Erreur génération convocation membre '.$membreLabel, ['exception' => $e]);
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le membre '.$membreLabel.' : erreur lors de la génération du document',
                    'pieceJointeId' => null,
                ];
            }
        }

        return $results;
    }

    /**
     * Génère les ordres du jour pour une date de commission.
     *
     * @param int $dateCommId ID de la dateCommission
     *
     * @return array<int, array{memberId: int, memberLabel: string, status: string, message: string, pieceJointeId: int|null}>
     */
    public function generateOrdreDuJour(int $dateCommId): array
    {
        $results = [];

        $commissionInfo = $this->dataProvider->getDateCommissionInfo($dateCommId);
        if (null === $commissionInfo) {
            return $results;
        }

        /** @var int|string $gestionHeuresRawOdj */
        $gestionHeuresRawOdj = $commissionInfo['GESTION_HEURES'] ?? 0;
        $listeDossiers = $this->dateCommissionRepo->findDossiersForGeneration($dateCommId, (int) $gestionHeuresRawOdj);
        $listeDossiers = $this->enrichDossiers($listeDossiers, false);

        /** @var int|string $commissionConcerneRawOdj */
        $commissionConcerneRawOdj = $commissionInfo['COMMISSION_CONCERNE'] ?? 0;
        $listeMembres = $this->dataProvider->loadMembresWithCompetences((int) $commissionConcerneRawOdj);
        $commissionInfos = $this->dataProvider->getCommissionInfo((int) $commissionConcerneRawOdj);
        if (null === $commissionInfos) {
            return $results;
        }

        $listeCommunes = $this->buildListeCommunes($listeDossiers);

        /** @var string|null $dateCommRawOdj */
        $dateCommRawOdj = $commissionInfo['DATE_COMMISSION'] ?? '';
        $dateComm = (string) ($dateCommRawOdj ?? '');
        /** @var string|null $heureDebRawOdj */
        $heureDebRawOdj = $commissionInfo['HEUREDEB_COMMISSION'] ?? '';
        $heureDeb = (string) ($heureDebRawOdj ?? '');

        foreach ($listeMembres as $membre) {
            /** @var array<string, mixed> $membreFile */
            $membreFile = $this->getMembreFirstFile($membre);
            /** @var string|null $membreLabelRawOdj */
            $membreLabelRawOdj = $membreFile['LIBELLE_COMMISSIONMEMBRE'] ?? '';
            $membreLabel = (string) ($membreLabelRawOdj ?? '');
            /** @var int|string $membreIdRawOdj */
            $membreIdRawOdj = $membreFile['ID_COMMISSIONMEMBRE'] ?? 0;
            $membreId = (int) $membreIdRawOdj;
            /** @var int|string $membreIdOdjRaw */
            $membreIdOdjRaw = $membreFile['ID_COMMISSIONMEMBRE'] ?? '';
            /** @var string|null $courrierOdjRaw */
            $courrierOdjRaw = $membreFile['COURRIER_ODJ'] ?? '';
            $nomFichier = $membreIdOdjRaw.'ODJ_'.($courrierOdjRaw ?? '');
            $templatePath = PathNormalizer::join($this->courriersMembresRealDataPath, $nomFichier);

            if (!file_exists($templatePath)) {
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le membre '.$membreLabel." n'a pas de fichier renseigné",
                    'pieceJointeId' => null,
                ];

                continue;
            }

            try {
                if (empty($membreFile['ID_GROUPEMENT'])) {
                    $odjResult = $this->generateOdjParCommune(
                        $templatePath,
                        $listeDossiers,
                        $membre,
                        $listeCommunes,
                        $commissionInfos,
                        $dateComm,
                        $heureDeb,
                        $dateCommId
                    );
                    $pjId = $odjResult['pjId'];
                    /** @var string[] $communesWithoutContact */
                    $communesWithoutContact = $odjResult['communesWithoutContact'];
                } else {
                    $pjId = $this->generateOdjParGroupement(
                        $templatePath,
                        $listeDossiers,
                        $membre,
                        $commissionInfos,
                        $dateComm,
                        $heureDeb,
                        $dateCommId
                    );
                    $communesWithoutContact = [];
                }

                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'success',
                    'message' => 'Le document a bien été généré pour le membre '.$membreLabel,
                    'pieceJointeId' => $pjId,
                ];

                foreach ($communesWithoutContact as $communeName) {
                    $results[] = [
                        'memberId' => $membreId,
                        'memberLabel' => $communeName,
                        'status' => 'warning',
                        'message' => 'Le contact de la commune '.$communeName." n'a pas été renseigné",
                        'pieceJointeId' => null,
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->error('Erreur génération ODJ membre '.$membreLabel, ['exception' => $e]);
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le membre '.$membreLabel.' : erreur lors de la génération du document',
                    'pieceJointeId' => null,
                ];
            }
        }

        return $results;
    }

    /**
     * Génère les procès-verbaux pour une date de commission.
     *
     * @param int $dateCommId ID de la dateCommission
     *
     * @return array<int, array{memberId: int, memberLabel: string, status: string, message: string, pieceJointeId: int|null}>
     */
    public function generateProcesVerbal(int $dateCommId): array
    {
        $results = [];

        $commissionInfo = $this->dataProvider->getDateCommissionInfo($dateCommId);
        if (null === $commissionInfo) {
            return $results;
        }

        /** @var int|string $gestionHeuresGen */
        $gestionHeuresGen = $commissionInfo['GESTION_HEURES'] ?? 0;
        $listeDossiers = $this->dateCommissionRepo->findDossiersForGeneration($dateCommId, (int) $gestionHeuresGen, true);
        $listeDossiers = $this->enrichDossiers($listeDossiers, true);

        /** @var int|string $commissionConcernePvRaw */
        $commissionConcernePvRaw = $commissionInfo['COMMISSION_CONCERNE'] ?? 0;
        $commissionInfos = $this->dataProvider->getCommissionInfo((int) $commissionConcernePvRaw);
        if (null === $commissionInfos) {
            return $results;
        }

        /** @var string|null $dateCommPvRaw */
        $dateCommPvRaw = $commissionInfo['DATE_COMMISSION'] ?? '';
        $dateComm = (string) ($dateCommPvRaw ?? '');

        // Pour le PV, on boucle sur les membres SANS groupement
        $membresFiles = $this->dataProvider->loadMembresFilesForPv((int) $commissionConcernePvRaw);

        foreach ($membresFiles as $membre) {
            /** @var string|null $membreLabelPvRaw */
            $membreLabelPvRaw = $membre['LIBELLE_COMMISSIONMEMBRE'] ?? '';
            $membreLabel = (string) ($membreLabelPvRaw ?? '');
            /** @var int|string $membreIdPvRaw */
            $membreIdPvRaw = $membre['ID_COMMISSIONMEMBRE'] ?? 0;
            $membreId = (int) $membreIdPvRaw;

            if (!empty($membre['ID_GROUPEMENT'])) {
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le membre '.$membreLabel." n'a pas de fichier renseigné",
                    'pieceJointeId' => null,
                ];

                continue;
            }

            /** @var int|string $membreIdPvRaw2 */
            $membreIdPvRaw2 = $membre['ID_COMMISSIONMEMBRE'] ?? '';
            /** @var string|null $courrierPvRaw */
            $courrierPvRaw = $membre['COURRIER_PV'] ?? '';
            $nomFichier = $membreIdPvRaw2.'PV_'.($courrierPvRaw ?? '');
            $templatePath = PathNormalizer::join($this->courriersMembresRealDataPath, $nomFichier);

            if (!file_exists($templatePath)) {
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le membre '.$membreLabel." n'a pas de fichier renseigné",
                    'pieceJointeId' => null,
                ];

                continue;
            }

            try {
                $pjId = $this->generatePvForMembre(
                    $templatePath,
                    $listeDossiers,
                    $commissionInfos,
                    $dateComm,
                    $dateCommId
                );

                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'success',
                    'message' => 'Le document a bien été généré pour le membre '.$membreLabel,
                    'pieceJointeId' => $pjId,
                ];
            } catch (\Exception $e) {
                $this->logger->error('Erreur génération PV membre '.$membreLabel, ['exception' => $e]);
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le membre '.$membreLabel.' : erreur lors de la génération du document',
                    'pieceJointeId' => null,
                ];
            }
        }

        return $results;
    }

    /**
     * Génère le compte-rendu pour une date de commission.
     *
     * @param int $dateCommId ID de la dateCommission
     *
     * @return array<int, array{memberId: int, memberLabel: string, status: string, message: string, pieceJointeId: int|null}>
     */
    public function generateCompteRendu(int $dateCommId): array
    {
        $results = [];

        $commissionInfo = $this->dataProvider->getDateCommissionInfo($dateCommId);
        if (null === $commissionInfo) {
            return $results;
        }

        /** @var int|string $gestionHeuresGen */
        $gestionHeuresGen = $commissionInfo['GESTION_HEURES'] ?? 0;
        $listeDossiers = $this->dateCommissionRepo->findDossiersForGeneration($dateCommId, (int) $gestionHeuresGen, true);
        $listeDossiers = $this->enrichDossiers($listeDossiers, false);

        /** @var int|string $commissionConcerneCrRaw */
        $commissionConcerneCrRaw = $commissionInfo['COMMISSION_CONCERNE'] ?? 0;
        $commissionInfos = $this->dataProvider->getCommissionInfo((int) $commissionConcerneCrRaw);
        if (null === $commissionInfos) {
            return $results;
        }

        /** @var string|null $dateCommCrRaw */
        $dateCommCrRaw = $commissionInfo['DATE_COMMISSION'] ?? '';
        $dateComm = (string) ($dateCommCrRaw ?? '');

        /** @var string|null $docCrRaw */
        $docCrRaw = $commissionInfos['DOCUMENT_CR'] ?? '';
        $nomFichier = (string) $docCrRaw;
        if ('' === $nomFichier) {
            $results[] = [
                'memberId' => 0,
                'memberLabel' => '',
                'status' => 'error',
                'message' => "Le fichier de compte rendu pour la commission n'existe pas",
                'pieceJointeId' => null,
            ];

            return $results;
        }

        $templatePath = PathNormalizer::join($this->documentsCommissionRealDataPath, $nomFichier);

        if (!file_exists($templatePath)) {
            $results[] = [
                'memberId' => 0,
                'memberLabel' => '',
                'status' => 'error',
                'message' => "Le fichier de compte rendu pour la commission n'existe pas",
                'pieceJointeId' => null,
            ];

            return $results;
        }

        try {
            $pjId = $this->generateCompteRenduDocument(
                $templatePath,
                $listeDossiers,
                $commissionInfos,
                $dateComm,
                $dateCommId
            );

            $results[] = [
                'memberId' => 0,
                'memberLabel' => '',
                'status' => 'success',
                'message' => 'Le document a bien été généré',
                'pieceJointeId' => $pjId,
            ];
        } catch (\Exception $exception) {
            $this->logger->error('Erreur génération compte-rendu', ['exception' => $exception]);
            $results[] = [
                'memberId' => 0,
                'memberLabel' => '',
                'status' => 'error',
                'message' => 'Erreur lors de la génération du compte-rendu',
                'pieceJointeId' => null,
            ];
        }

        return $results;
    }

    // ========================================================================
    // PRIVATE — Enrichissement des données
    // ========================================================================

    /**
     * Enrichit les dossiers avec établissement, avis dérogations, prescriptions, formulaires.
     *
     * Utilise un chargement en deux passes pour éviter les problèmes N+1 :
     * 1. Collecte des IDs et pré-chargement batch des données DBAL
     * 2. Assemblage par dossier
     *
     * @param array<int, array<string, mixed>> $listeDossiers
     *
     * @return array<int, array<string, mixed>>
     */
    private function enrichDossiers(array $listeDossiers, bool $includePrescriptions): array
    {
        if (empty($listeDossiers)) {
            return [];
        }

        // ── Passe 1 : Collecter IDs et charger entités ──────────────────────────
        $dossierIds = [];
        foreach ($listeDossiers as $dossier) {
            /** @var int|string $rawDossierId */
            $rawDossierId = $dossier['ID_DOSSIER'] ?? 0;
            $dossierIds[] = (int) $rawDossierId;
        }

        // Pré-charger toutes les entités Dossier en une seule requête (popule l'identity map)
        $this->em->createQueryBuilder()
            ->select('d')
            ->from(Dossier::class, 'd')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $dossierIds)
            ->getQuery()
            ->getResult();

        // Premier passage : collecter les IDs d'établissements
        /** @var array<int, array{dossierKey: int, dossierId: int, dossierEntity: Dossier, etabId: int, etabEntity: Etablissement}> $validDossiers */
        $validDossiers = [];
        $allEtabIds = [];

        foreach ($listeDossiers as $val => $dossier) {
            /** @var int|string $rawDossierIdLoop */
            $rawDossierIdLoop = $dossier['ID_DOSSIER'] ?? 0;
            $dossierId = (int) $rawDossierIdLoop;
            $dossierEntity = $this->em->find(Dossier::class, $dossierId);
            if (null === $dossierEntity) {
                unset($listeDossiers[$val]);

                continue;
            }

            $etablissements = $dossierEntity->getEtablissements();
            if ($etablissements->isEmpty()) {
                unset($listeDossiers[$val]);

                continue;
            }

            /** @var Etablissement $etabEntity */
            $etabEntity = $etablissements->first();
            $etabId = (int) $etabEntity->getId();
            $allEtabIds[] = $etabId;

            $validDossiers[] = [
                'dossierKey' => $val,
                'dossierId' => $dossierId,
                'dossierEntity' => $dossierEntity,
                'etabId' => $etabId,
                'etabEntity' => $etabEntity,
            ];
        }

        if (empty($validDossiers)) {
            return [];
        }

        // ── Passe 2 : Chargement batch des données DBAL ─────────────────────────
        $validDossierIds = array_column($validDossiers, 'dossierId');

        // Batch : avis dérogations par dossier
        $allAvisDerogations = $this->dataProvider->batchGetAvisDerogationsFromDossiers($validDossierIds);

        // Batch : documents d'urbanisme par dossier
        $allDocUrba = $this->dataProvider->batchGetDocUrba($validDossierIds);

        // Batch : informations établissements
        $allEtabInfos = $this->dataProvider->batchGetEtablissementInfos($allEtabIds);

        // ── Passe 3 : Assemblage par dossier ─────────────────────────────────────
        foreach ($validDossiers as $entry) {
            $val = $entry['dossierKey'];
            $dossierId = $entry['dossierId'];
            $dossierEntity = $entry['dossierEntity'];
            $etabId = $entry['etabId'];
            $etabEntity = $entry['etabEntity'];

            /** @var array<string, mixed> $currentDossier */
            $currentDossier = $listeDossiers[$val];

            // Préventionnistes via relation Doctrine
            $currentDossier['preventionnistes'] = $this->getPreventionnistesFromEntity($dossierEntity);

            // Établissement infos (depuis le batch)
            $etablissementInfos = $allEtabInfos[$etabId] ?? [];
            $currentDossier['infosEtab'] = $etablissementInfos;

            // Avis dérogations du dossier (depuis le batch)
            $currentDossier['AVIS_DEROGATIONS'] = $allAvisDerogations[$dossierId] ?? [];

            // Avis dérogations établissement (parent + enfants)
            $idsEtablissements = [$etabId];
            /** @var array<int, array<string, mixed>> $etablissementLies */
            $etablissementLies = $etablissementInfos['etablissement_lies'] ?? [];
            if (!empty($etablissementLies)) {
                /** @var array<int, int|string> $etabLiesColumn */
                $etabLiesColumn = array_column($etablissementLies, 'ID_ETABLISSEMENT');
                $etabLiesIds = array_map('intval', $etabLiesColumn);
                $idsEtablissements = array_merge($idsEtablissements, $etabLiesIds);
            }

            $currentDossier['AVIS_DEROGATIONS_ETABLISSEMENT'] = $this->dataProvider->getAvisDerogationsEtablissements($idsEtablissements);

            // Documents d'urbanisme (depuis le batch)
            $currentDossier['listeDocUrba'] = $allDocUrba[$dossierId] ?? [];

            // Prescriptions (PV uniquement)
            if ($includePrescriptions) {
                $currentDossier = $this->enrichWithPrescriptions($currentDossier, $dossierEntity);
            }

            // Formulaires personnalisés via Descriptif service
            $currentDossier['FORMULAIRES'] = $this->loadFormulaires($dossierEntity, $etabEntity);

            $listeDossiers[$val] = $currentDossier;
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = array_values($listeDossiers);

        return $result;
    }

    /**
     * Enrichit un dossier avec les prescriptions par type.
     *
     * @param array<string, mixed> $dossier
     *
     * @return array<string, mixed>
     */
    private function enrichWithPrescriptions(array $dossier, Dossier $dossierEntity): array
    {
        $regles = PrescriptionArrayHelper::buildNestedArray(
            $this->prescriptionService->getPrescriptionsParType($dossierEntity, TypePrescription::REGLEMENTAIRE)
        );
        $dossier['prescriptionReglDossier'] = PrescriptionArrayHelper::withoutLevees($regles);
        $dossier['prescriptionReglDossierReprises'] = PrescriptionArrayHelper::withoutActuals($dossier['prescriptionReglDossier']);
        $dossier['prescriptionReglDossierActuelles'] = PrescriptionArrayHelper::withoutPrevious($dossier['prescriptionReglDossier']);

        $exploitation = PrescriptionArrayHelper::buildNestedArray(
            $this->prescriptionService->getPrescriptionsParType($dossierEntity, TypePrescription::EXPLOITATION)
        );
        $dossier['prescriptionExploitation'] = PrescriptionArrayHelper::withoutLevees($exploitation);
        $dossier['prescriptionExploitationReprises'] = PrescriptionArrayHelper::withoutActuals($dossier['prescriptionExploitation']);
        $dossier['prescriptionExploitationActuelles'] = PrescriptionArrayHelper::withoutPrevious($dossier['prescriptionExploitation']);

        $amelioration = PrescriptionArrayHelper::buildNestedArray(
            $this->prescriptionService->getPrescriptionsParType($dossierEntity, TypePrescription::AMELIORATION)
        );
        $dossier['prescriptionAmelioration'] = PrescriptionArrayHelper::withoutLevees($amelioration);
        $dossier['prescriptionAmeliorationReprises'] = PrescriptionArrayHelper::withoutActuals($dossier['prescriptionAmelioration']);
        $dossier['prescriptionAmeliorationActuelles'] = PrescriptionArrayHelper::withoutPrevious($dossier['prescriptionAmelioration']);

        return $dossier;
    }

    /**
     * Charge les formulaires personnalisés via le service Descriptif.
     *
     * Remplace les 4 méthodes DBAL (getFormulaires, getRubriquesForCapsule,
     * getChampsForRubrique, getChampsFils) en réutilisant le service partagé
     * avec DossierRapportOdtService.
     *
     * @return array<string, array{capsule: CapsuleRubrique|null, rubriques: list<array{rubrique: Rubrique, display: bool, champs: list<array<string, mixed>>}>}>
     */
    private function loadFormulaires(Dossier $dossier, Etablissement $etablissement): array
    {
        return [
            NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES => $this->descriptif->getForDossier($dossier, NomInterneCapsule::DESCRIPTIF_VERIFICATIONS_TECHNIQUES),
            NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT => $this->descriptif->getForEtablissement($etablissement, NomInterneCapsule::DESCRIPTIF_ETABLISSEMENT),
            NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER => $this->descriptif->getForDossier($dossier, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_DOSSIER),
            NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT => $this->descriptif->getForEtablissement($etablissement, NomInterneCapsule::EFFECTIFS_DEGAGEMENTS_ETABLISSEMENT),
        ];
    }

    /**
     * Récupère les préventionnistes via les relations Doctrine (plus fiable que SQL brut).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPreventionnistesFromEntity(Dossier $dossier): array
    {
        $result = [];
        foreach ($dossier->getPreventionnistes() as $utilisateur) {
            $infos = $utilisateur->getInformations();
            if (null === $infos) {
                continue;
            }

            $result[] = [
                'GRADE_UTILISATEURINFORMATIONS' => $infos->getGrade(),
                'NOM_UTILISATEURINFORMATIONS' => $infos->getNom(),
                'PRENOM_UTILISATEURINFORMATIONS' => $infos->getPrenom(),
                'TELFIXE_UTILISATEURINFORMATIONS' => $infos->getTelephoneFixe(),
                'MAIL_UTILISATEURINFORMATIONS' => $infos->getMail(),
            ];
        }

        return $result;
    }

    /**
     * Construit la liste des communes à partir des dossiers.
     *
     * @param array<int, array<string, mixed>> $listeDossiers
     *
     * @return array<int, array{0: string, 1: array<int, array<string, mixed>>}>
     */
    private function buildListeCommunes(array $listeDossiers): array
    {
        $tabCommune = [];

        foreach ($listeDossiers as $ue) {
            /** @var array<string, mixed> $infosEtab */
            $infosEtab = $ue['infosEtab'] ?? [];
            /** @var array<int, array<string, mixed>> $adresses */
            $adresses = $infosEtab['adresses'] ?? [];
            if (!isset($adresses[0])) {
                continue;
            }

            /** @var array<string, string|null> $adresse */
            $adresse = $adresses[0];
            /** @var string $libelleCommune */
            $libelleCommune = $adresse['LIBELLE_COMMUNE'] ?? '';
            if ('' === $libelleCommune) {
                continue;
            }

            /** @var string $numInsee */
            $numInsee = $adresse['NUMINSEE_COMMUNE'] ?? '';

            $existe = false;
            foreach ($tabCommune as $value) {
                if ($value[0] === $libelleCommune) {
                    $existe = true;

                    break;
                }
            }

            if (!$existe) {
                $tabCommune[] = [$libelleCommune, $this->dataProvider->getCommuneContactInfo($numInsee)];
            }
        }

        return $tabCommune;
    }

    // ========================================================================
    // PRIVATE — Génération convocation
    // ========================================================================

    /**
     * @param array<int, array<string, mixed>>                                  $listeDossiers
     * @param array<string, mixed>                                              $membre
     * @param array<int, array{0: string, 1: array<int, array<string, mixed>>}> $listeCommunes
     * @param array<string, mixed>                                              $commissionInfos
     */
    private function generateConvocationParCommune(
        string $templatePath,
        array $listeDossiers,
        array $membre,
        array $listeCommunes,
        array $commissionInfos,
        string $dateComm,
        string $heureDeb,
        int $dateCommId
    ): int {
        $odf = new \Odf($templatePath);
        $villeODF = $odf->setSegment('villeDoss');

        foreach ($listeCommunes as $commune) {
            // Infos mairie
            /** @var array<int, array<string, mixed>> $communeContacts */
            $communeContacts = $commune[1];
            if (isset($communeContacts[0])) {
                /** @var array<string, mixed> $communeContact0 */
                $communeContact0 = $communeContacts[0];
                $this->addChamp($villeODF, 'numMairie', $communeContact0['NUMEROADRESSE_UTILISATEURINFORMATIONS'] ?? '');
                $this->addChamp($villeODF, 'rueMairie', $communeContact0['RUEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
                $this->addChamp($villeODF, 'cpMairie', $communeContact0['CPADRESSE_UTILISATEURINFORMATIONS'] ?? '');
            }

            $this->addChamp($villeODF, 'communeMairie', (string) $commune[0]);
            $this->addChamp($villeODF, 'commissionNom', $commissionInfos['LIBELLE_COMMISSION'] ?? '');

            $dateDuJour = new \DateTime();
            $this->addChamp($villeODF, 'date', $dateDuJour->format('d/m/Y'));

            $dateCommObj = $this->parseDateSql($dateComm);
            $this->addChamp($villeODF, 'dateCommission', null !== $dateCommObj ? $dateCommObj->format('d/m/Y') : '');

            $this->addChamp($villeODF, 'heureDebutComm', $this->formatHeure($heureDeb));

            // Heure début 1er dossier concerné
            $heureDebut = '';
            foreach ($listeDossiers as $dossierInfos) {
                /** @var array<string, mixed> $dInfosEtab */
                $dInfosEtab = $dossierInfos['infosEtab'] ?? [];
                /** @var array<int, array<string, mixed>> $dAdresses */
                $dAdresses = $dInfosEtab['adresses'] ?? [];
                /** @var string|null $dLibelleCommune */
                $dLibelleCommune = $dAdresses[0]['LIBELLE_COMMUNE'] ?? null;
                if (
                    count($dAdresses) > 0
                    && $dLibelleCommune == $commune[0]
                    && $this->eligibilityChecker->isDossierConcerneMembre($membre, $dossierInfos)
                    && isset($dossierInfos['HEURE_DEB_AFFECT'])
                ) {
                    /** @var string $heureDebAffect */
                    $heureDebAffect = $dossierInfos['HEURE_DEB_AFFECT'];
                    $heureDebut = $this->formatHeure($heureDebAffect);

                    break;
                }
            }

            $this->addChamp($villeODF, 'heureDeb1erDossier', $heureDebut);

            // Segment infosDoss
            $infosDossName = 'infosDoss';
            /** @var \Segment $infosDoss */
            // @phpstan-ignore-next-line
            $infosDoss = $villeODF->$infosDossName;
            foreach ($listeDossiers as $dossierInfos) {
                /** @var array<string, mixed> $dInfosEtab */
                $dInfosEtab = $dossierInfos['infosEtab'] ?? [];
                /** @var array<int, array<string, mixed>> $dAdresses */
                $dAdresses = $dInfosEtab['adresses'] ?? [];
                /** @var string|null $dLibelleCommune */
                $dLibelleCommune = $dAdresses[0]['LIBELLE_COMMUNE'] ?? null;
                if (
                    count($dAdresses) > 0
                    && $dLibelleCommune == $commune[0]
                    && $this->eligibilityChecker->isDossierConcerneMembre($membre, $dossierInfos)
                ) {
                    $this->fillConvocationDossierInfos($infosDoss, $dossierInfos);
                    $infosDoss->merge();
                }
            }

            $villeODF->merge();
        }

        $odf->mergeSegment($villeODF);

        /** @var array<int, array<string, mixed>> $infosFiles */
        $infosFiles = $membre['infosFiles'] ?? [];
        /** @var string|null $membreLabelPj */
        $membreLabelPj = $infosFiles[0]['LIBELLE_COMMISSIONMEMBRE'] ?? '';
        $pj = $this->savePieceJointe(
            $dateCommId,
            'Convocation',
            'Convocations du membre '.($membreLabelPj ?? '')
        );

        $path = $this->dataStore->getFilePath($pj, 'dateCommission', $dateCommId, true);
        $odf->saveToDisk($path);

        return (int) $pj->getId();
    }

    /**
     * @param array<int, array<string, mixed>>                                  $listeDossiers
     * @param array<string, mixed>                                              $membre
     * @param array<int, array{0: string, 1: array<int, array<string, mixed>>}> $listeCommunes
     * @param array<string, mixed>                                              $commissionInfos
     */
    private function generateConvocationParGroupement(
        string $templatePath,
        array $listeDossiers,
        array $membre,
        array $listeCommunes,
        array $commissionInfos,
        string $dateComm,
        string $heureDeb,
        int $dateCommId
    ): int {
        /** @var array<int, array<string, mixed>> $infosFilesGrp */
        $infosFilesGrp = $membre['infosFiles'] ?? [];
        /** @var array<string, mixed> $infosFile0 */
        $infosFile0 = $infosFilesGrp[0] ?? [];
        /** @var int|string $groupementIdRaw */
        $groupementIdRaw = $infosFile0['ID_GROUPEMENT'] ?? 0;
        $groupementId = (int) $groupementIdRaw;

        // Récupérer les infos du groupement avec les informations utilisateur (adresse, etc.)
        // Port direct du legacy Model_DbTable_Groupement::get() qui fait un joinLeft sur utilisateurinformations
        /** @var array<string, string|null>|false $infosGroupement */
        $infosGroupement = $this->em->getConnection()->fetchAssociative(
            'SELECT groupement.*, utilisateurinformations.*
             FROM groupement
             LEFT JOIN utilisateurinformations ON utilisateurinformations.ID_UTILISATEURINFORMATIONS = groupement.ID_UTILISATEURINFORMATIONS
             WHERE groupement.ID_GROUPEMENT = :id',
            ['id' => $groupementId]
        );
        if (false === $infosGroupement) {
            $infosGroupement = [];
        }

        // Récupérer les communes du groupement
        $listeVillesGroupement = $this->em->getConnection()->fetchAllAssociative(
            'SELECT ac.NUMINSEE_COMMUNE
             FROM groupementcommune gc
             JOIN adressecommune ac ON gc.NUMINSEE_COMMUNE = ac.NUMINSEE_COMMUNE
             WHERE gc.ID_GROUPEMENT = :id',
            ['id' => $groupementId]
        );

        $odf = new \Odf($templatePath);

        $this->fillGroupementCommissionInfo($odf, $commissionInfos, $heureDeb, $dateComm, $infosGroupement, $listeCommunes);

        $grpLoop = $odf->setSegment('grpLoop');
        // Port direct du legacy (generationconvoc.phtml, lignes 170 et 175) : retrieveInfoGroupementCommission est appelée
        // deux fois — une fois sur l'ODF (balises hors segment) et une fois sur le segment grpLoop (balises à l'intérieur).
        $this->fillGroupementCommissionInfo($grpLoop, $commissionInfos, $heureDeb, $dateComm, $infosGroupement, $listeCommunes);

        foreach ($listeDossiers as $dossierInfos) {
            $dossierExported = false;

            /** @var array<string, mixed> $dInfosEtab */
            $dInfosEtab = $dossierInfos['infosEtab'] ?? [];
            /** @var array<int, array<string, mixed>> $dAdresses */
            $dAdresses = $dInfosEtab['adresses'] ?? [];
            /** @var array<string, mixed> $dInformations */
            $dInformations = $dInfosEtab['informations'] ?? [];
            /** @var array<string, mixed> $dGeneral */
            $dGeneral = $dInfosEtab['general'] ?? [];

            foreach ($listeVillesGroupement as $villeGrp) {
                /** @var string|null $dNumInsee */
                $dNumInsee = $dAdresses[0]['NUMINSEE_COMMUNE'] ?? null;
                if (
                    count($dAdresses) > 0
                    && $dNumInsee == $villeGrp['NUMINSEE_COMMUNE']
                    && $this->eligibilityChecker->isDossierConcerneMembre($membre, $dossierInfos)
                ) {
                    $this->fillConvocationDossierInfos($grpLoop, $dossierInfos);
                    $this->addChamp($odf, 'nomEtab', $dInformations['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
                    $this->addChamp($odf, 'numWinPrev', $dGeneral['NUMEROID_ETABLISSEMENT'] ?? '');
                    $dossierExported = true;

                    break;
                }
            }

            if ($dossierExported) {
                $grpLoop->merge();
            }
        }

        $odf->mergeSegment($grpLoop);

        /** @var array<int, array<string, mixed>> $infosFilesGrp2 */
        $infosFilesGrp2 = $membre['infosFiles'] ?? [];
        /** @var string|null $membreLabelGrp */
        $membreLabelGrp = $infosFilesGrp2[0]['LIBELLE_COMMISSIONMEMBRE'] ?? '';
        $pj = $this->savePieceJointe(
            $dateCommId,
            'Convocation',
            'Convocations du membre '.($membreLabelGrp ?? '')
        );

        $path = $this->dataStore->getFilePath($pj, 'dateCommission', $dateCommId, true);
        $odf->saveToDisk($path);

        return (int) $pj->getId();
    }

    /**
     * Remplit les champs d'info dossier pour une convocation.
     *
     * Port direct de retrieveInfoDossier() (generationconvoc.phtml).
     *
     * @param array<string, mixed> $dossierInfos
     */
    private function fillConvocationDossierInfos(\Segment $segment, array $dossierInfos): void
    {
        /** @var array<string, mixed> $infosEtab */
        $infosEtab = $dossierInfos['infosEtab'] ?? [];
        /** @var array<string, string|null> $informations */
        $informations = $infosEtab['informations'] ?? [];
        /** @var array<int, array<string, string|null>> $adresses */
        $adresses = $infosEtab['adresses'] ?? [];
        /** @var array<int, array<string, string|null>> $parents */
        $parents = $infosEtab['parents'] ?? [];
        /** @var array<string, string|null> $general */
        $general = $infosEtab['general'] ?? [];
        /** @var array<int, array<string, mixed>> $listeDocUrbaData */
        $listeDocUrbaData = $dossierInfos['listeDocUrba'] ?? [];
        /** @var array<int, array<string, mixed>> $preventionnistes */
        $preventionnistes = $dossierInfos['preventionnistes'] ?? [];
        /** @var int|string|null $avisDossierCommission */
        $avisDossierCommission = $dossierInfos['AVIS_DOSSIER_COMMISSION'] ?? null;
        /** @var int|string|null $avisDossier */
        $avisDossier = $dossierInfos['AVIS_DOSSIER'] ?? null;

        /** @var array<string, string|null> $adresseCourante */
        $adresseCourante = $adresses[0] ?? [];

        $this->addChamp($segment, 'nomEtab', $informations['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');

        $nomPere = '';
        if (isset($parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
            $nomPere = $parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS'];
        }

        $this->addChamp($segment, 'nomPereEtab', $nomPere);
        $this->addChamp($segment, 'idDossier', $dossierInfos['ID_DOSSIER'] ?? '');
        $this->addChamp($segment, 'numWinPrev', $general['NUMEROID_ETABLISSEMENT'] ?? '');
        $this->addChamp($segment, 'mailEtab', $general['COURRIEL_ETABLISSEMENT'] ?? '');
        $this->addChamp($segment, 'communeEtab', $adresseCourante['LIBELLE_COMMUNE'] ?? '');

        $adresse = ($adresseCourante['NUMERO_ADRESSE'] ?? '').' ';
        $adresse .= ($adresseCourante['LIBELLE_RUE'] ?? '').' ';
        $adresse .= ($adresseCourante['CODEPOSTAL_COMMUNE'] ?? '').' ';
        $adresse .= ($adresseCourante['LIBELLE_COMMUNE'] ?? '');
        $this->addChamp($segment, 'etablissementAdresse', $adresse);

        $this->addChamp($segment, 'categorieEtab', $informations['LIBELLE_CATEGORIE'] ?? '');
        $this->addChamp($segment, 'typeLettreP', $informations['LIBELLE_TYPE_PRINCIPAL'] ?? '');
        $this->addChamp($segment, 'natureDossier', $dossierInfos['LIBELLE_DOSSIERNATURE'] ?? '');
        $this->addChamp($segment, 'objetDossier', $dossierInfos['OBJET_DOSSIER'] ?? '');
        $this->addChamp($segment, 'avisDossierCommission', AvisCommission::getLibelle($avisDossierCommission));
        $this->addChamp($segment, 'avisDossier', AvisCommission::getLibelle($avisDossier));

        $heureDebut = '';
        /** @var string $heureDebAffect */
        $heureDebAffect = $dossierInfos['HEURE_DEB_AFFECT'] ?? '';
        if ('' !== $heureDebAffect) {
            $heureDebutTab = explode(':', $heureDebAffect);
            if (count($heureDebutTab) >= 2) {
                $heureDebut = $heureDebutTab[0].':'.$heureDebutTab[1];
            }
        }

        $this->addChamp($segment, 'heureDebDossier', $heureDebut);

        $heureFin = '';
        /** @var string $heureFinAffect */
        $heureFinAffect = $dossierInfos['HEURE_FIN_AFFECT'] ?? '';
        if ('' !== $heureFinAffect) {
            $heureFinTab = explode(':', $heureFinAffect);
            if (count($heureFinTab) >= 2) {
                $heureFin = $heureFinTab[0].':'.$heureFinTab[1];
            }
        }

        $this->addChamp($segment, 'heureFinDossier', $heureFin);

        $listeDocUrba = '';
        foreach ($listeDocUrbaData as $docurb) {
            /** @var array<string, string|null> $docurb */
            $listeDocUrba .= ($docurb['NUM_DOCURBA'] ?? '').'    ';
        }

        $this->addChamp($segment, 'numDocUrba', $listeDocUrba);

        $listePreventioniste = [];
        $listeTelPreventioniste = [];
        $listeMailPreventionniste = [];
        foreach ($preventionnistes as $preventioniste) {
            /** @var array<string, string|null> $preventioniste */
            $listePreventioniste[] = ($preventioniste['GRADE_UTILISATEURINFORMATIONS'] ?? '').' '.($preventioniste['NOM_UTILISATEURINFORMATIONS'] ?? '').' '.($preventioniste['PRENOM_UTILISATEURINFORMATIONS'] ?? '');
            $listeTelPreventioniste[] = ($preventioniste['TELFIXE_UTILISATEURINFORMATIONS'] ?? '');
            $listeMailPreventionniste[] = ($preventioniste['MAIL_UTILISATEURINFORMATIONS'] ?? '');
        }

        $this->addChamp($segment, 'preventionnistes', implode(', ', $listePreventioniste));
        $this->addChamp($segment, 'listeTelPreventioniste', implode(', ', $listeTelPreventioniste));
        $this->addChamp($segment, 'listeMailPreventioniste', implode(', ', $listeMailPreventionniste));

        $this->fillAvisDerogationsSegment($segment, $dossierInfos);
        $this->fillFormulairesSegment($segment, $dossierInfos);
    }

    /**
     * @param \Odf|\Segment                                                     $odf
     * @param array<string, mixed>                                              $commissionInfos
     * @param array<string, string|null>                                        $infosGroupement
     * @param array<int, array{0: string, 1: array<int, array<string, mixed>>}> $listeCommunes
     */
    private function fillGroupementCommissionInfo($odf, array $commissionInfos, string $heureDeb, string $dateComm, array $infosGroupement, array $listeCommunes): void
    {
        $this->addChamp($odf, 'commissionNom', $commissionInfos['LIBELLE_COMMISSION'] ?? '');

        $dateDuJour = new \DateTime();
        $this->addChamp($odf, 'date', $dateDuJour->format('d/m/Y'));

        $this->addChamp($odf, 'communeGrp', $infosGroupement['VILLEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
        $this->addChamp($odf, 'numGrp', $infosGroupement['NUMEROADRESSE_UTILISATEURINFORMATIONS'] ?? '');
        $this->addChamp($odf, 'rueGrp', $infosGroupement['RUEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
        $this->addChamp($odf, 'cpGrp', $infosGroupement['CPADRESSE_UTILISATEURINFORMATIONS'] ?? '');

        $dateCommObj = $this->parseDateSql($dateComm);
        $this->addChamp($odf, 'dateCommission', null !== $dateCommObj ? $dateCommObj->format('d/m/Y') : '');
        $this->addChamp($odf, 'heureDebutComm', $heureDeb);

        $maires = [];
        foreach ($listeCommunes as $commune) {
            /** @var array<int, array<string, mixed>> $communeContacts */
            $communeContacts = $commune[1];
            if (!isset($communeContacts[0])) {
                continue;
            }

            /** @var array<string, mixed> $communeContact */
            $communeContact = $communeContacts[0];
            /** @var string $nom */
            $nom = $communeContact['NOM_UTILISATEURINFORMATIONS'] ?? '';
            if ('' !== $nom) {
                /** @var string $prenom */
                $prenom = $communeContact['PRENOM_UTILISATEURINFORMATIONS'] ?? '';
                $maires[] = $nom.'  '.$prenom;
            }
        }

        $this->addChamp($odf, 'maires', implode(', ', $maires));
    }

    // ========================================================================
    // PRIVATE — Génération ODJ
    // ========================================================================

    /**
     * @param array<int, array<string, mixed>>                                  $listeDossiers
     * @param array<string, mixed>                                              $membre
     * @param array<int, array{0: string, 1: array<int, array<string, mixed>>}> $listeCommunes
     * @param array<string, mixed>                                              $commissionInfos
     *
     * @return array{pjId: int, communesWithoutContact: string[]}
     */
    private function generateOdjParCommune(
        string $templatePath,
        array $listeDossiers,
        array $membre,
        array $listeCommunes,
        array $commissionInfos,
        string $dateComm,
        string $heureDeb,
        int $dateCommId
    ): array {
        $vowels = ['a', 'e', 'i', 'o', 'u', 'y', 'A', 'E', 'I', 'O', 'U', 'Y'];
        $odf = new \Odf($templatePath);
        $membreLoop = $odf->setSegment('membresComm');
        $communesWithoutContact = [];

        foreach ($listeCommunes as $commune) {
            if ('' !== $commune[0]) {
                /** @var array<int, array<string, mixed>> $communeContactsOdj */
                $communeContactsOdj = $commune[1];
                if (1 === count($communeContactsOdj)) {
                    foreach ($communeContactsOdj as $info) {
                        $this->addChamp($membreLoop, 'numMairie', $info['NUMEROADRESSE_UTILISATEURINFORMATIONS'] ?? '');
                        $this->addChamp($membreLoop, 'rueMairie', $info['RUEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
                        $this->addChamp($membreLoop, 'cpMairie', $info['CPADRESSE_UTILISATEURINFORMATIONS'] ?? '');
                        $this->addChamp($membreLoop, 'communeMairie', $info['VILLEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
                    }
                } else {
                    $this->addChamp($membreLoop, 'numMairie', '');
                    $this->addChamp($membreLoop, 'rueMairie', '');
                    $this->addChamp($membreLoop, 'cpMairie', '');
                    $this->addChamp($membreLoop, 'communeMairie', '');
                    $communesWithoutContact[] = (string) $commune[0];
                }
            }

            $this->addChamp($membreLoop, 'commissionNom', $commissionInfos['LIBELLE_COMMISSION'] ?? '');

            $dateDuJour = new \DateTime();
            $this->addChamp($membreLoop, 'date', $dateDuJour->format('d/m/Y'));

            $dateCommObj = $this->parseDateSql($dateComm);
            $this->addChamp($membreLoop, 'dateCommission', null !== $dateCommObj ? $dateCommObj->format('d/m/Y') : '');

            $this->addChamp($membreLoop, 'heureDebutComm', $this->formatHeure($heureDeb));

            // Dossiers de la commune filtrés par compétence
            $infosDossOdjName = 'infosDoss';
            foreach ($listeDossiers as $dossierInfos) {
                /** @var array<string, mixed> $dInfosEtabOdj */
                $dInfosEtabOdj = $dossierInfos['infosEtab'] ?? [];
                /** @var array<int, array<string, mixed>> $dAdressesOdj */
                $dAdressesOdj = $dInfosEtabOdj['adresses'] ?? [];
                /** @var string|null $dLibelleCommuneOdj */
                $dLibelleCommuneOdj = $dAdressesOdj[0]['LIBELLE_COMMUNE'] ?? null;
                if (
                    count($dAdressesOdj) > 0
                    && $dLibelleCommuneOdj == $commune[0]
                    && $this->eligibilityChecker->isDossierConcerneMembre($membre, $dossierInfos)
                ) {
                    $this->fillOdjDossierInfos($membreLoop, $dossierInfos);
                    /** @var \Segment $infosDossOdj */
                    // @phpstan-ignore-next-line
                    $infosDossOdj = $membreLoop->$infosDossOdjName;
                    $infosDossOdj->merge();
                }
            }

            $membreLoop->merge();
        }

        $odf->mergeSegment($membreLoop);

        /** @var array<int, array<string, mixed>> $infosFilesOdj */
        $infosFilesOdj = $membre['infosFiles'] ?? [];
        /** @var string|null $membreLabelOdj */
        $membreLabelOdj = $infosFilesOdj[0]['LIBELLE_COMMISSIONMEMBRE'] ?? '';
        $pj = $this->savePieceJointe(
            $dateCommId,
            'Ordre du jour',
            'Ordre du jour du membre '.($membreLabelOdj ?? '')
        );

        $path = $this->dataStore->getFilePath($pj, 'dateCommission', $dateCommId, true);
        $odf->saveToDisk($path);

        return ['pjId' => (int) $pj->getId(), 'communesWithoutContact' => $communesWithoutContact];
    }

    /**
     * @param array<int, array<string, mixed>> $listeDossiers
     * @param array<string, mixed>             $membre
     * @param array<string, mixed>             $commissionInfos
     */
    private function generateOdjParGroupement(
        string $templatePath,
        array $listeDossiers,
        array $membre,
        array $commissionInfos,
        string $dateComm,
        string $heureDeb,
        int $dateCommId
    ): int {
        /** @var array<int, array<string, mixed>> $infosFilesGrpOdj */
        $infosFilesGrpOdj = $membre['infosFiles'] ?? [];
        /** @var array<string, mixed> $infosFile0Odj */
        $infosFile0Odj = $infosFilesGrpOdj[0] ?? [];
        /** @var int|string $groupementIdRaw */
        $groupementIdRaw = $infosFile0Odj['ID_GROUPEMENT'] ?? 0;
        $groupementId = (int) $groupementIdRaw;

        // Port direct du legacy Model_DbTable_Groupement::get() qui fait un joinLeft sur utilisateurinformations
        /** @var array<string, string|null>|false $infosGroupement */
        $infosGroupement = $this->em->getConnection()->fetchAssociative(
            'SELECT groupement.*, utilisateurinformations.*
             FROM groupement
             LEFT JOIN utilisateurinformations ON utilisateurinformations.ID_UTILISATEURINFORMATIONS = groupement.ID_UTILISATEURINFORMATIONS
             WHERE groupement.ID_GROUPEMENT = :id',
            ['id' => $groupementId]
        );
        if (false === $infosGroupement) {
            $infosGroupement = [];
        }

        $odf = new \Odf($templatePath);
        $grpLoop = $odf->setSegment('grpLoop');

        $this->addChamp($grpLoop, 'commissionNom', $commissionInfos['LIBELLE_COMMISSION'] ?? '');

        $dateDuJour = new \DateTime();
        $this->addChamp($grpLoop, 'date', $dateDuJour->format('d/m/Y'));
        $this->addChamp($grpLoop, 'communeGrp', $infosGroupement['VILLEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
        $this->addChamp($grpLoop, 'numGrp', $infosGroupement['NUMEROADRESSE_UTILISATEURINFORMATIONS'] ?? '');
        $this->addChamp($grpLoop, 'rueGrp', $infosGroupement['RUEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
        $this->addChamp($grpLoop, 'cpGrp', $infosGroupement['CPADRESSE_UTILISATEURINFORMATIONS'] ?? '');

        $dateCommObj = $this->parseDateSql($dateComm);
        $this->addChamp($grpLoop, 'dateCommission', null !== $dateCommObj ? $dateCommObj->format('d/m/Y') : '');
        $this->addChamp($grpLoop, 'heureDebutComm', $heureDeb);

        // Tous les dossiers (pas de filtrage par groupement dans le legacy ODJ groupement)
        $infosDossGrpName = 'infosDoss';
        foreach ($listeDossiers as $dossierInfos) {
            $this->fillOdjDossierInfos($grpLoop, $dossierInfos);
            /** @var \Segment $infosDossGrp */
            // @phpstan-ignore-next-line
            $infosDossGrp = $grpLoop->$infosDossGrpName;
            $infosDossGrp->merge();
        }

        $grpLoop->merge();
        $odf->mergeSegment($grpLoop);

        /** @var string|null $membreLabelGrpOdj */
        $membreLabelGrpOdj = $infosFilesGrpOdj[0]['LIBELLE_COMMISSIONMEMBRE'] ?? '';
        $pj = $this->savePieceJointe(
            $dateCommId,
            'Ordre du jour',
            'Ordre du jour du membre '.($membreLabelGrpOdj ?? '')
        );

        $path = $this->dataStore->getFilePath($pj, 'dateCommission', $dateCommId, true);
        $odf->saveToDisk($path);

        return (int) $pj->getId();
    }

    /**
     * Port direct de retrieveInfoDoss() (generationodj.phtml).
     *
     * @param array<string, mixed> $dossierInfos
     */
    private function fillOdjDossierInfos(\Segment $segment, array $dossierInfos): void
    {
        $infosDossOdjFillName = 'infosDoss';
        /** @var \Segment $infosDoss */
        // @phpstan-ignore-next-line
        $infosDoss = $segment->$infosDossOdjFillName;
        /** @var array<string, mixed> $infosEtab */
        $infosEtab = $dossierInfos['infosEtab'] ?? [];
        /** @var array<string, string|null> $informations */
        $informations = $infosEtab['informations'] ?? [];
        /** @var array<int, array<string, string|null>> $adresses */
        $adresses = $infosEtab['adresses'] ?? [];
        /** @var array<int, array<string, string|null>> $parents */
        $parents = $infosEtab['parents'] ?? [];
        /** @var array<string, string|null> $general */
        $general = $infosEtab['general'] ?? [];
        /** @var array<int, array<string, mixed>> $listeDocUrbaData */
        $listeDocUrbaData = $dossierInfos['listeDocUrba'] ?? [];
        /** @var array<int, array<string, string|null>> $preventionnistes */
        $preventionnistes = $dossierInfos['preventionnistes'] ?? [];
        /** @var int|string $typeDossier */
        $typeDossier = $dossierInfos['TYPE_DOSSIER'] ?? 0;
        $typeDossierId = (int) $typeDossier;
        /** @var int|string|null $avisDossier */
        $avisDossier = $dossierInfos['AVIS_DOSSIER'] ?? null;
        /** @var array<string, string|null> $adresseCourante */
        $adresseCourante = $adresses[0] ?? [];

        $typeDossierCourt = 'A';
        switch ($typeDossierId) {
            case 1:
                $typeDossierCourt = 'E';

                break;
            case 2:
            case 3:
                $typeDossierCourt = 'V';

                break;
        }

        $this->addChamp($infosDoss, 'idDossier', $dossierInfos['ID_DOSSIER'] ?? '');
        $this->addChamp($infosDoss, 'avisDossier', AvisCommission::getLibelle($avisDossier));
        $this->addChamp($infosDoss, 'nomEtab', $informations['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
        $this->addChamp($infosDoss, 'numWinPrev', $general['NUMEROID_ETABLISSEMENT'] ?? '');

        $nomPere = '';
        if (isset($parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
            $nomPere = $parents[0]['LIBELLE_ETABLISSEMENTINFORMATIONS'];
        }

        $this->addChamp($infosDoss, 'nomPereEtab', $nomPere);

        $dateVisite = '';
        /** @var string|null $dateVisiteStr */
        $dateVisiteStr = $dossierInfos['DATEVISITE_DOSSIER'] ?? null;
        if (null !== $dateVisiteStr && '' !== $dateVisiteStr) {
            $dtVisite = $this->parseDateSql($dateVisiteStr);
            if (null !== $dtVisite) {
                $dateVisite = $dtVisite->format('d/m/Y');
            }
        }

        $this->addChamp($infosDoss, 'dateVisite', $dateVisite);
        $this->addChamp($infosDoss, 'categorieEtab', $informations['LIBELLE_CATEGORIE'] ?? '');
        $this->addChamp($infosDoss, 'typeLettreP', $informations['LIBELLE_TYPE_PRINCIPAL'] ?? '');
        $this->addChamp($infosDoss, 'natureDossier', $dossierInfos['LIBELLE_DOSSIERNATURE'] ?? '');
        $this->addChamp($infosDoss, 'objetDossier', $dossierInfos['OBJET_DOSSIER'] ?? '');
        $this->addChamp($infosDoss, 'communeConcerne', $adresseCourante['LIBELLE_COMMUNE'] ?? '');

        $adresse = '';
        if (!empty($adresses)) {
            $adresse = ($adresseCourante['NUMERO_ADRESSE'] ?? '').' ';
            $adresse .= ($adresseCourante['LIBELLE_RUE'] ?? '').' ';
            $adresse .= ($adresseCourante['CODEPOSTAL_COMMUNE'] ?? '').' ';
            $adresse .= ($adresseCourante['LIBELLE_COMMUNE'] ?? '');
        }

        $this->addChamp($infosDoss, 'etablissementAdresse', $adresse);

        $heureDebut = '';
        /** @var string $heureDebAffect */
        $heureDebAffect = $dossierInfos['HEURE_DEB_AFFECT'] ?? '';
        if ('' !== $heureDebAffect) {
            $heureDebutTab = explode(':', $heureDebAffect);
            if (count($heureDebutTab) >= 2) {
                $heureDebut = $heureDebutTab[0].':'.$heureDebutTab[1];
            }
        }

        $this->addChamp($infosDoss, 'heureDebDossier', $heureDebut);

        $heureFin = '';
        /** @var string $heureFinAffect */
        $heureFinAffect = $dossierInfos['HEURE_FIN_AFFECT'] ?? '';
        if ('' !== $heureFinAffect) {
            $heureFinTab = explode(':', $heureFinAffect);
            if (count($heureFinTab) >= 2) {
                $heureFin = $heureFinTab[0].':'.$heureFinTab[1];
            }
        }

        $this->addChamp($infosDoss, 'heureFinDossier', $heureFin);

        $listeDocUrba = '';
        foreach ($listeDocUrbaData as $docurb) {
            /** @var array<string, string|null> $docurb */
            $listeDocUrba .= ($docurb['NUM_DOCURBA'] ?? '').'    ';
        }

        $this->addChamp($infosDoss, 'numDocUrba', $listeDocUrba);
        $this->addChamp($infosDoss, 'typeDossierCourt', $typeDossierCourt);

        $listePreventioniste = [];
        $listeTelPreventioniste = [];
        $listeMailPreventionniste = [];
        foreach ($preventionnistes as $preventioniste) {
            /** @var array<string, string|null> $preventioniste */
            $listePreventioniste[] = ($preventioniste['GRADE_UTILISATEURINFORMATIONS'] ?? '').' '.($preventioniste['NOM_UTILISATEURINFORMATIONS'] ?? '').' '.($preventioniste['PRENOM_UTILISATEURINFORMATIONS'] ?? '');
            $listeTelPreventioniste[] = ($preventioniste['TELFIXE_UTILISATEURINFORMATIONS'] ?? '');
            $listeMailPreventionniste[] = ($preventioniste['MAIL_UTILISATEURINFORMATIONS'] ?? '');
        }

        $this->addChamp($infosDoss, 'preventionnistes', implode(', ', $listePreventioniste));
        $this->addChamp($infosDoss, 'listeTelPreventioniste', implode(', ', $listeTelPreventioniste));
        $this->addChamp($infosDoss, 'listeMailPreventioniste', implode(', ', $listeMailPreventionniste));

        $this->fillAvisDerogationsSegment($infosDoss, $dossierInfos);
        $this->fillFormulairesSegment($infosDoss, $dossierInfos);
    }

    // ========================================================================
    // PRIVATE — Génération PV
    // ========================================================================

    /**
     * @param array<int, array<string, mixed>> $listeDossiers
     * @param array<string, mixed>             $commissionInfos
     */
    private function generatePvForMembre(
        string $templatePath,
        array $listeDossiers,
        array $commissionInfos,
        string $dateComm,
        int $dateCommId
    ): int {
        $odf = new \Odf($templatePath);
        $dossInfos = $odf->setSegment('dossierInfos');

        foreach ($listeDossiers as $dossierInfos) {
            /** @var array<string, mixed> $pvInfosEtab */
            $pvInfosEtab = $dossierInfos['infosEtab'] ?? [];
            /** @var array<int, array<string, string|null>> $pvAdresses */
            $pvAdresses = $pvInfosEtab['adresses'] ?? [];
            /** @var array<string, string|null> $pvInformations */
            $pvInformations = $pvInfosEtab['informations'] ?? [];
            /** @var array<string, string|null> $pvGeneral */
            $pvGeneral = $pvInfosEtab['general'] ?? [];
            /** @var array<int, array<string, string|null>> $pvParents */
            $pvParents = $pvInfosEtab['parents'] ?? [];

            $nbAdresse = count($pvAdresses);
            if (0 === $nbAdresse) {
                continue;
            }

            foreach ($pvAdresses as $commune) {
                $this->addChamp($dossInfos, 'commissionNom', $commissionInfos['LIBELLE_COMMISSION'] ?? '');

                if (isset($commune['LIBELLE_COMMUNE'])) {
                    $this->addChamp($dossInfos, 'communeDossier', $commune['LIBELLE_COMMUNE']);
                }

                $this->addChamp($dossInfos, 'objetDossier', $dossierInfos['OBJET_DOSSIER'] ?? '');
                $this->addChamp($dossInfos, 'natureDossier', $dossierInfos['LIBELLE_DOSSIERNATURE'] ?? '');

                // Avis dérogations
                $this->fillAvisDerogationsSegment($dossInfos, $dossierInfos);

                // Formulaires personnalisés
                $this->fillFormulairesSegment($dossInfos, $dossierInfos);

                // Date commission
                $dateCommObj = $this->parseDateSql($dateComm);
                $this->addChamp($dossInfos, 'dateCommission', null !== $dateCommObj ? DateFormatterFr::formatLong($dateCommObj) : '');

                // Date visite
                $dateVisite = null;
                /** @var string|null $dateVisitePvRaw */
                $dateVisitePvRaw = $dossierInfos['DATEVISITE_DOSSIER'] ?? null;
                if (null !== $dateVisitePvRaw) {
                    $dateVisite = $this->parseDateSql($dateVisitePvRaw);
                } elseif (isset($pvInfosEtab['last_visite'])) {
                    /** @var string $pvLastVisite */
                    $pvLastVisite = $pvInfosEtab['last_visite'];
                    $dateVisite = $this->parseDateSql($pvLastVisite);
                }

                $this->addChamp($dossInfos, 'dateVisite', null !== $dateVisite ? DateFormatterFr::formatLong($dateVisite) : '');

                // Adresse
                $pvAdresse0 = $pvAdresses[0] ?? [];
                $this->addChamp($dossInfos, 'libelleRue', $pvAdresse0['LIBELLE_RUE'] ?? '');
                $this->addChamp($dossInfos, 'codePostal', $pvAdresse0['CODEPOSTAL_COMMUNE'] ?? '');
                $this->addChamp($dossInfos, 'libelleCommune', $pvAdresse0['LIBELLE_COMMUNE'] ?? '');

                // Effectif total
                /** @var int|string $effectifPublicRaw */
                $effectifPublicRaw = $pvInformations['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0;
                /** @var int|string $effectifPersonnelRaw */
                $effectifPersonnelRaw = $pvInformations['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0;
                $effectifPublic = (int) $effectifPublicRaw;
                $effectifPersonnel = (int) $effectifPersonnelRaw;
                $effectifTotal = $effectifPublic + $effectifPersonnel;
                $this->addChamp($dossInfos, 'effectifTotal', 0 === $effectifTotal ? 'non renseigné' : (string) $effectifTotal);

                // Type dossier
                $type = '';
                /** @var int|string $typeDossierPvRaw */
                $typeDossierPvRaw = $dossierInfos['TYPE_DOSSIER'] ?? 0;
                switch ((int) $typeDossierPvRaw) {
                    case 1:
                        $type = 'Étude';

                        break;
                    case 2:
                        $type = 'Visite de commission';

                        break;
                    case 3:
                        $type = 'Groupe de visite';

                        break;
                }

                $this->addChamp($dossInfos, 'typeDossier', $type);

                // Nom parent établissement
                $pvParent0 = $pvParents[0] ?? [];
                if (isset($pvParent0['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
                    $this->addChamp($dossInfos, 'nomPereEtab', $pvParent0['LIBELLE_ETABLISSEMENTINFORMATIONS']);
                } else {
                    $this->addChamp($dossInfos, 'nomPereEtab', '');
                }

                $this->addChamp($dossInfos, 'nomEtab', $pvInformations['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
                $this->addChamp($dossInfos, 'categorieEtab', $pvInformations['LIBELLE_CATEGORIE'] ?? '');
                $this->addChamp($dossInfos, 'typeEtab', $pvInformations['LIBELLE_TYPE_PRINCIPAL'] ?? '');

                // Avis
                /** @var int|string|null $avisDossierPvRaw */
                $avisDossierPvRaw = $dossierInfos['AVIS_DOSSIER'] ?? null;
                /** @var int|string|null $avisDossierCommPvRaw */
                $avisDossierCommPvRaw = $dossierInfos['AVIS_DOSSIER_COMMISSION'] ?? null;
                $this->addChamp($dossInfos, 'avisDossier', AvisCommission::getLibelle($avisDossierPvRaw));
                $this->addChamp($dossInfos, 'avisDossierCommission', AvisCommission::getLibelle($avisDossierCommPvRaw));

                // Doc urba
                $listeDocUrba = '';
                /** @var array<int, array<string, mixed>> $listeDocUrbaPv */
                $listeDocUrbaPv = $dossierInfos['listeDocUrba'] ?? [];
                if (!empty($listeDocUrbaPv)) {
                    foreach ($listeDocUrbaPv as $docurb) {
                        /** @var array<string, string|null> $docurb */
                        $listeDocUrba .= ($docurb['NUM_DOCURBA'] ?? '').'    ';
                    }
                }

                $this->addChamp($dossInfos, 'numDocUrba', $listeDocUrba);

                // Prescriptions
                $this->fillPrescriptionsSegment($dossInfos, $dossierInfos);

                $dossInfos->merge();
            }
        }

        $odf->mergeSegment($dossInfos);

        $pj = $this->savePieceJointe(
            $dateCommId,
            'P.V.',
            'PV : Le pv a bien été généré'
        );

        $path = $this->dataStore->getFilePath($pj, 'dateCommission', $dateCommId, true);
        $odf->saveToDisk($path);

        return (int) $pj->getId();
    }

    // ========================================================================
    // PRIVATE — Génération Compte-Rendu
    // ========================================================================

    /**
     * @param array<int, array<string, mixed>> $listeDossiers
     * @param array<string, mixed>             $commissionInfos
     */
    private function generateCompteRenduDocument(
        string $templatePath,
        array $listeDossiers,
        array $commissionInfos,
        string $dateComm,
        int $dateCommId
    ): int {
        $odf = new \Odf($templatePath);

        $dateDuJour = new \DateTime();
        $this->addChamp($odf, 'dateGeneration', DateFormatterFr::formatLong($dateDuJour));

        $dossInfos = $odf->setSegment('dossierInfos');

        foreach ($listeDossiers as $dossierInfos) {
            /** @var array<string, mixed> $crInfosEtab */
            $crInfosEtab = $dossierInfos['infosEtab'] ?? [];
            /** @var array<int, array<string, string|null>> $crAdresses */
            $crAdresses = $crInfosEtab['adresses'] ?? [];
            /** @var array<string, string|null> $crInformations */
            $crInformations = $crInfosEtab['informations'] ?? [];
            /** @var array<string, string|null> $crGeneral */
            $crGeneral = $crInfosEtab['general'] ?? [];
            /** @var array<int, array<string, string|null>> $crParents */
            $crParents = $crInfosEtab['parents'] ?? [];

            $nbAdresse = count($crAdresses);
            if (0 === $nbAdresse) {
                continue;
            }

            foreach ($crAdresses as $commune) {
                $this->addChamp($dossInfos, 'commissionNom', $commissionInfos['LIBELLE_COMMISSION'] ?? '');

                if (isset($commune['LIBELLE_COMMUNE'])) {
                    $this->addChamp($dossInfos, 'communeDossier', $commune['LIBELLE_COMMUNE']);
                }

                $this->addChamp($dossInfos, 'objetDossier', $dossierInfos['OBJET_DOSSIER'] ?? '');
                $this->addChamp($dossInfos, 'natureDossier', $dossierInfos['LIBELLE_DOSSIERNATURE'] ?? '');
                $this->addChamp($dossInfos, 'observationDossier', $dossierInfos['OBSERVATION_DOSSIER'] ?? '');

                // Date commission
                $dateCommObj = $this->parseDateSql($dateComm);
                $this->addChamp($dossInfos, 'dateCommission', null !== $dateCommObj ? DateFormatterFr::formatLong($dateCommObj) : '');

                // Date visite
                $dateVisite = null;
                /** @var string|null $dateVisiteCrRaw */
                $dateVisiteCrRaw = $dossierInfos['DATEVISITE_DOSSIER'] ?? null;
                if (null !== $dateVisiteCrRaw) {
                    $dateVisite = $this->parseDateSql($dateVisiteCrRaw);
                } elseif (isset($crInfosEtab['last_visite'])) {
                    /** @var string $crLastVisite */
                    $crLastVisite = $crInfosEtab['last_visite'];
                    $dateVisite = $this->parseDateSql($crLastVisite);
                }

                $this->addChamp($dossInfos, 'dateVisite', null !== $dateVisite ? DateFormatterFr::formatLong($dateVisite) : '');

                // Adresse
                $crAdresse0 = $crAdresses[0] ?? [];
                $this->addChamp($dossInfos, 'libelleRue', $crAdresse0['LIBELLE_RUE'] ?? '');
                $this->addChamp($dossInfos, 'codePostal', $crAdresse0['CODEPOSTAL_COMMUNE'] ?? '');

                // numWinPrev
                try {
                    $dossInfos->setVars('numWinPrev', $crGeneral['NUMEROID_ETABLISSEMENT'] ?? '', true, 'UTF-8');
                } catch (\Exception $e) {
                    // Silencieux
                }

                $this->addChamp($dossInfos, 'libelleCommune', $crAdresse0['LIBELLE_COMMUNE'] ?? '');

                // Effectif total
                /** @var int|string $crEffPublicRaw */
                $crEffPublicRaw = $crInformations['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0;
                /** @var int|string $crEffPersonnelRaw */
                $crEffPersonnelRaw = $crInformations['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0;
                $effectifPublic = (int) $crEffPublicRaw;
                $effectifPersonnel = (int) $crEffPersonnelRaw;
                $effectifTotal = $effectifPublic + $effectifPersonnel;
                $this->addChamp($dossInfos, 'effectifTotal', 0 === $effectifTotal ? 'non renseigné' : (string) $effectifTotal);

                // Type dossier
                $type = '';
                /** @var int|string $typeDossierCrRaw */
                $typeDossierCrRaw = $dossierInfos['TYPE_DOSSIER'] ?? 0;
                switch ((int) $typeDossierCrRaw) {
                    case 1:
                        $type = 'Étude';

                        break;
                    case 2:
                        $type = 'Visite de commission';

                        break;
                    case 3:
                        $type = 'Groupe de visite';

                        break;
                }

                $this->addChamp($dossInfos, 'typeDossier', $type);

                // Nom parent établissement
                $crParent0 = $crParents[0] ?? [];
                if (isset($crParent0['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
                    $this->addChamp($dossInfos, 'nomPereEtab', $crParent0['LIBELLE_ETABLISSEMENTINFORMATIONS']);
                } else {
                    $this->addChamp($dossInfos, 'nomPereEtab', '');
                }

                $this->addChamp($dossInfos, 'nomEtab', $crInformations['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
                $this->addChamp($dossInfos, 'categorieEtab', $crInformations['LIBELLE_CATEGORIE'] ?? '');
                $this->addChamp($dossInfos, 'typeEtab', $crInformations['LIBELLE_TYPE_PRINCIPAL'] ?? '');

                // Avis
                /** @var int|string|null $avisDossierCrRaw */
                $avisDossierCrRaw = $dossierInfos['AVIS_DOSSIER'] ?? null;
                /** @var int|string|null $avisDossierCommCrRaw */
                $avisDossierCommCrRaw = $dossierInfos['AVIS_DOSSIER_COMMISSION'] ?? null;
                $this->addChamp($dossInfos, 'avisDossier', AvisCommission::getLibelle($avisDossierCrRaw));
                $this->addChamp($dossInfos, 'avisDossierCommission', AvisCommission::getLibelle($avisDossierCommCrRaw));

                // Doc urba
                $listeDocUrba = '';
                /** @var array<int, array<string, mixed>> $listeDocUrbaCr */
                $listeDocUrbaCr = $dossierInfos['listeDocUrba'] ?? [];
                if (!empty($listeDocUrbaCr)) {
                    foreach ($listeDocUrbaCr as $docurb) {
                        /** @var array<string, string|null> $docurb */
                        $listeDocUrba .= ($docurb['NUM_DOCURBA'] ?? '').'    ';
                    }
                }

                $this->addChamp($dossInfos, 'numDocUrba', $listeDocUrba);

                // Avis dérogations
                $this->fillAvisDerogationsSegment($dossInfos, $dossierInfos);

                // Formulaires personnalisés
                $this->fillFormulairesSegment($dossInfos, $dossierInfos);

                $dossInfos->merge();
            }
        }

        $odf->mergeSegment($dossInfos);

        $pj = $this->savePieceJointe(
            $dateCommId,
            'Compte rendu',
            'Compte rendu du '
        );

        $path = $this->dataStore->getFilePath($pj, 'dateCommission', $dateCommId, true);
        $odf->saveToDisk($path);

        return (int) $pj->getId();
    }

    // ========================================================================
    // PRIVATE — Segments communs (dérogations, prescriptions, formulaires)
    // ========================================================================

    /**
     * @param array<string, mixed> $dossierInfos
     */
    private function fillAvisDerogationsSegment(\Segment $segment, array $dossierInfos): void
    {
        /** @var string $avisDerogationsPropName */
        $avisDerogationsPropName = 'avisDerogations';
        /** @var \Segment|null $avisDerogations */
        $avisDerogations = null;
        try {
            $avisDerogationsRaw = $segment->$avisDerogationsPropName;
            if ($avisDerogationsRaw instanceof \Segment) {
                $avisDerogations = $avisDerogationsRaw;
            }

            // @phpstan-ignore-next-line
        } catch (\Exception $exception) {
        }

        if (null !== $avisDerogations) {
            /** @var array<int, array<string, string|null>> $derogations */
            $derogations = $dossierInfos['AVIS_DEROGATIONS'] ?? [];
            $this->fillDerogationSegmentData($avisDerogations, $derogations);
        }

        /** @var string $avisDerogationsEtabPropName */
        $avisDerogationsEtabPropName = 'avisDerogationsEtablissement';
        /** @var \Segment|null $avisDerogationsEtablissement */
        $avisDerogationsEtablissement = null;
        try {
            $avisDerogationsEtabRaw = $segment->$avisDerogationsEtabPropName;
            if ($avisDerogationsEtabRaw instanceof \Segment) {
                $avisDerogationsEtablissement = $avisDerogationsEtabRaw;
            }

            // @phpstan-ignore-next-line
        } catch (\Exception $exception) {
        }

        if (null !== $avisDerogationsEtablissement) {
            /** @var array<int, array<string, string|null>> $derogationsEtab */
            $derogationsEtab = $dossierInfos['AVIS_DEROGATIONS_ETABLISSEMENT'] ?? [];
            $this->fillDerogationSegmentData($avisDerogationsEtablissement, $derogationsEtab);
        }
    }

    /**
     * @param array<int, array<string, string|null>> $derogations
     */
    private function fillDerogationSegmentData(\Segment $derogationSegment, array $derogations): void
    {
        if (!empty($derogations)) {
            foreach ($derogations as $avisDerogation) {
                $this->addChamp($derogationSegment, 'type', $avisDerogation['TYPE'] ?? '');
                $this->addChamp($derogationSegment, 'titre', $avisDerogation['TITRE'] ?? '');
                $this->addChamp($derogationSegment, 'objet', $avisDerogation['OBJET'] ?? '');
                $this->addChamp($derogationSegment, 'justification', $avisDerogation['JUSTIFICATION'] ?? '');
                $this->addChamp($derogationSegment, 'articles_concernes', $avisDerogation['ARTICLES_CONCERNES'] ?? '');
                $this->addChamp($derogationSegment, 'mesures_compensatoires_proposees', $avisDerogation['MESURES_COMPENSATOIRES_PROPOSEES'] ?? '');
                $this->addChamp($derogationSegment, 'analyse_de_risque', $avisDerogation['ANALYSE_DE_RISQUE'] ?? '');
                $this->addChamp($derogationSegment, 'avis', $avisDerogation['LIBELLE_AVIS'] ?? '');
                $this->addChamp($derogationSegment, 'identifiantdossierleve', $avisDerogation['ID_DOSSIER_LIE'] ?? '');

                $date = null;
                /** @var string|null $dateCommDossier */
                $dateCommDossier = $avisDerogation['DATECOMM_DOSSIER'] ?? null;
                /** @var string|null $dateVisiteDossier */
                $dateVisiteDossier = $avisDerogation['DATEVISITE_DOSSIER'] ?? null;
                if (null !== $dateCommDossier && '' !== $dateCommDossier) {
                    $date = DateFormatterFr::formatShort($dateCommDossier);
                } elseif (null !== $dateVisiteDossier && '' !== $dateVisiteDossier) {
                    $date = DateFormatterFr::formatShort($dateVisiteDossier);
                }

                $this->addChamp($derogationSegment, 'date_avis_derogation', $date ?? '');

                $derogationSegment->merge();
            }
        } else {
            $this->addChamp($derogationSegment, 'type', '');
            $this->addChamp($derogationSegment, 'titre', '');
            $this->addChamp($derogationSegment, 'objet', '');
            $this->addChamp($derogationSegment, 'justification', '');
            $this->addChamp($derogationSegment, 'articles_concernes', '');
            $this->addChamp($derogationSegment, 'mesures_compensatoires_proposees', '');
            $this->addChamp($derogationSegment, 'analyse_de_risque', '');
            $this->addChamp($derogationSegment, 'avis', '');
            $this->addChamp($derogationSegment, 'identifiantdossierleve', '');
            $this->addChamp($derogationSegment, 'date_avis_derogation', '');

            $derogationSegment->merge();
        }
    }

    /**
     * Remplit les prescriptions dans les segments ODT (PV uniquement).
     *
     * Port direct de addPrescription() (generationpv.phtml).
     *
     * @param array<string, mixed> $dossierInfos
     */
    private function fillPrescriptionsSegment(\Segment $odfSegment, array $dossierInfos): void
    {
        $prescriptionTypes = [
            ['key' => 'prescriptionReglDossier', 'type' => TypePrescription::REGLEMENTAIRE, 'contenu' => 'default'],
            ['key' => 'prescriptionReglDossierReprises', 'type' => TypePrescription::REGLEMENTAIRE, 'contenu' => 'reprises'],
            ['key' => 'prescriptionReglDossierActuelles', 'type' => TypePrescription::REGLEMENTAIRE, 'contenu' => 'actuelles'],
            ['key' => 'prescriptionExploitation', 'type' => TypePrescription::EXPLOITATION, 'contenu' => 'default'],
            ['key' => 'prescriptionExploitationReprises', 'type' => TypePrescription::EXPLOITATION, 'contenu' => 'reprises'],
            ['key' => 'prescriptionExploitationActuelles', 'type' => TypePrescription::EXPLOITATION, 'contenu' => 'actuelles'],
            ['key' => 'prescriptionAmelioration', 'type' => TypePrescription::AMELIORATION, 'contenu' => 'default'],
            ['key' => 'prescriptionAmeliorationReprises', 'type' => TypePrescription::AMELIORATION, 'contenu' => 'reprises'],
            ['key' => 'prescriptionAmeliorationActuelles', 'type' => TypePrescription::AMELIORATION, 'contenu' => 'actuelles'],
        ];

        foreach ($prescriptionTypes as $pType) {
            /** @var string $key */
            $key = $pType['key'];
            /** @var int $prescriptionType */
            $prescriptionType = $pType['type'];
            /** @var string $contenu */
            $contenu = $pType['contenu'];
            /** @var list<list<array<string, string|null>>> $listePrescription */
            $listePrescription = $dossierInfos[$key] ?? [];
            $this->addPrescription($listePrescription, $prescriptionType, $odfSegment, $contenu);
        }
    }

    /**
     * Port direct de addPrescription() (generationpv.phtml).
     *
     * @param array<int, array<int, array<string, string|null>>> $listePrescription Tableau issu de withoutLevees/withoutActuals/withoutPrevious (clés non nécessairement séquentielles)
     * @param string                                             $typeContenu       'default', 'reprises', 'actuelles'
     */
    private function addPrescription(array $listePrescription, int $type, \Segment $odf, string $typeContenu): void
    {
        $segmentName = $this->getPrescriptionSegmentNameLegacy($type, $typeContenu);
        /** @var \Segment|null $prescription */
        $prescription = null;

        try {
            $prescriptionRaw = $odf->$segmentName;
            if ($prescriptionRaw instanceof \Segment) {
                $prescription = $prescriptionRaw;
            }

            // @phpstan-ignore-next-line
        } catch (\Exception $exception) {
        }

        if (null === $prescription) {
            return;
        }

        if (!empty($listePrescription)) {
            foreach ($listePrescription as $prescRow) {
                if (empty($prescRow)) {
                    continue;
                }

                // Port direct : num et libelle viennent du premier élément du groupe
                /** @var array<string, string|null> $firstAssoc */
                $firstAssoc = reset($prescRow) ?: [];
                $this->addChamp($prescription, 'num', (string) ($firstAssoc['NUM_PRESCRIPTION_DOSSIER'] ?? ''));
                if ('' === (string) ($firstAssoc['LIBELLE_PRESCRIPTION_DOSSIER'] ?? '')) {
                    $this->addChamp($prescription, 'libelle', (string) ($firstAssoc['PRESCRIPTIONTYPE_LIBELLE'] ?? ''));
                } else {
                    $this->addChamp($prescription, 'libelle', (string) ($firstAssoc['LIBELLE_PRESCRIPTION_DOSSIER'] ?? ''));
                }

                $nbCpt = count($prescRow);
                $textearticle = '';
                foreach ($prescRow as $assoc) {
                    $articleT = (string) ($assoc['LIBELLE_ARTICLE'] ?? '');
                    $texteT = (string) ($assoc['LIBELLE_TEXTE'] ?? '');

                    $textearticle .= '' !== $texteT ? $texteT.' - ' : ' - ';
                    if ($nbCpt > 1) {
                        $textearticle .= '' !== $articleT ? $articleT.', ' : ', ';
                    } else {
                        $textearticle .= '' !== $articleT ? $articleT.' ' : ' ';
                    }

                    --$nbCpt;
                }

                $this->addChamp($prescription, 'textearticle', $textearticle);
                $prescription->merge();
            }
        } else {
            $this->addChamp($prescription, 'num', '');
            $this->addChamp($prescription, 'libelle', '');
            $this->addChamp($prescription, 'textearticle', '');
            $prescription->merge();
        }
    }

    /**
     * Retourne le nom du segment prescription legacy.
     */
    private function getPrescriptionSegmentNameLegacy(int $type, string $typeContenu): string
    {
        $segNames = [
            TypePrescription::REGLEMENTAIRE => [
                'default' => 'listeReg',
                'reprises' => 'listeRegReprises',
                'actuelles' => 'listeRegActuelles',
            ],
            TypePrescription::EXPLOITATION => [
                'default' => 'listeExploitation',
                'reprises' => 'listeExploitationReprises',
                'actuelles' => 'listeExploitationActuelles',
            ],
            TypePrescription::AMELIORATION => [
                'default' => 'listeAmelioration',
                'reprises' => 'listeAmeliorationReprises',
                'actuelles' => 'listeAmeliorationActuelles',
            ],
        ];

        return $segNames[$type][$typeContenu] ?? '';
    }

    /**
     * @param array<string, mixed> $dossierInfos
     */
    private function fillFormulairesSegment(\Segment $segment, array $dossierInfos): void
    {
        /** @var array<string, array{capsule: mixed, rubriques: array<int, array{rubrique: Rubrique, champs: array<int, array<string, mixed>>}>}> $formulaires */
        $formulaires = $dossierInfos['FORMULAIRES'] ?? [];
        $this->odtWriter->applyFormulairesToSegment($segment, $formulaires);
    }

    // ========================================================================
    // PRIVATE — Utilitaires
    // ========================================================================

    /**
     * @param \Odf|\Segment $odf
     * @param mixed         $variable
     */
    private function addChamp($odf, string $nomOdf, $variable): void
    {
        if (null === $variable) {
            $this->odtWriter->setVariable($odf, $nomOdf, null);

            return;
        }

        $this->odtWriter->setVariable($odf, $nomOdf, is_scalar($variable) ? (string) $variable : '');
    }

    /**
     * Extrait le premier fichier (infosFiles[0]) d'un membre.
     *
     * @param array<string, mixed> $membre
     *
     * @return array<string, mixed>
     */
    private function getMembreFirstFile(array $membre): array
    {
        /** @var array<int, array<string, mixed>> $infosFiles */
        $infosFiles = $membre['infosFiles'] ?? [];
        if (empty($infosFiles)) {
            return [];
        }

        return $infosFiles[0];
    }

    /**
     * Crée et persiste une PieceJointe liée à la dateCommission.
     */
    private function savePieceJointe(int $dateCommId, string $nom, string $descriptionPrefix): PieceJointe
    {
        $now = new \DateTime();

        $pj = new PieceJointe();
        $pj->setNom($nom);
        $pj->setExtension('.odt');
        $pj->setDescription($descriptionPrefix.' générée le '.$now->format('d/m/Y').' à '.$now->format('H:i'));
        $pj->setDate($now);

        $this->em->persist($pj);

        // Lier à la dateCommission
        /** @var DateCommission|null $dateCommission */
        $dateCommission = $this->em->find(DateCommission::class, $dateCommId);
        if (null !== $dateCommission) {
            $dateCommission->addPiecesJointe($pj);
        }

        $this->em->flush();

        return $pj;
    }

    /**
     * Parse une date SQL (Y-m-d ou Y-m-d H:i:s).
     */
    private function parseDateSql(?string $dateStr): ?\DateTime
    {
        if (null === $dateStr || '' === $dateStr) {
            return null;
        }

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
        if (!$dt instanceof \DateTime) {
            $dt = \DateTime::createFromFormat('Y-m-d', substr($dateStr, 0, 10));
        }

        return $dt instanceof \DateTime ? $dt : null;
    }

    /**
     * Formate une heure brute (HH:MM ou HH:MM:SS) en HH:MM.
     *
     * Retourne une chaîne vide si l'entrée est nulle, vide ou mal formée.
     */
    private function formatHeure(?string $time): string
    {
        if (null === $time || '' === $time) {
            return '';
        }

        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return '';
        }

        return $parts[0].':'.$parts[1];
    }
}

<?php

namespace App\Service\Commission;

use App\Constant\AvisCommission;
use App\Constant\TypePrescription;
use App\Entity\DateCommission;
use App\Entity\PieceJointe;
use App\Service\DataStore\DataStore;
use App\Service\Formulaires\Fusion\ChampNamer;
use App\Service\Rapport\PrescriptionArrayHelper;
use App\Utils\DateFormatterFr;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Odf;
use Psr\Log\LoggerInterface;

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

    /** @var Connection */
    private $conn;

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

    public function __construct(
        EntityManagerInterface $em,
        Connection $conn,
        DataStore $dataStore,
        LoggerInterface $logger,
        CommissionMembreEligibilityChecker $eligibilityChecker,
        string $courriersMembresRealDataPath,
        string $documentsCommissionRealDataPath
    ) {
        $this->em = $em;
        $this->conn = $conn;
        $this->dataStore = $dataStore;
        $this->logger = $logger;
        $this->eligibilityChecker = $eligibilityChecker;
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

        $commissionInfo = $this->getDateCommissionInfo($dateCommId);
        if (null === $commissionInfo) {
            return $results;
        }

        $typeCommission = (int) ($commissionInfo['ID_COMMISSIONTYPEEVENEMENT'] ?? 0);
        $listeDossiers = $this->loadDossiers($dateCommId, (int) ($commissionInfo['GESTION_HEURES'] ?? 0));
        $listeDossiers = $this->enrichDossiers($listeDossiers, false);

        $listeMembres = $this->loadMembresWithCompetences((int) ($commissionInfo['COMMISSION_CONCERNE'] ?? 0));
        $commissionInfos = $this->getCommissionInfo((int) ($commissionInfo['COMMISSION_CONCERNE'] ?? 0));
        $listeCommunes = $this->buildListeCommunes($listeDossiers);

        $dateComm = isset($listeDossiers[0]) ? (string) ($listeDossiers[0]['DATE_COMMISSION'] ?? '') : '';
        $heureDeb = isset($listeDossiers[0]) ? (string) ($listeDossiers[0]['HEUREDEB_COMMISSION'] ?? '') : '';

        foreach ($listeMembres as $membre) {
            /** @var array<string, mixed> $membreFile */
            $membreFile = $this->getMembreFirstFile($membre);
            $membreLabel = (string) ($membreFile['LIBELLE_COMMISSIONMEMBRE'] ?? '');
            $membreId = (int) ($membreFile['ID_COMMISSIONMEMBRE'] ?? 0);

            // Déterminer le nom du fichier template
            $erreurFile = false;
            $nomFichier = '';

            if (1 === $typeCommission) {
                if (empty($membreFile['COURRIER_CONVOCATIONSALLE'])) {
                    $erreurFile = true;
                } else {
                    $nomFichier = $membreFile['ID_COMMISSIONMEMBRE'].'CONVOCATIONSALLE_'.$membreFile['COURRIER_CONVOCATIONSALLE'];
                }
            } elseif (2 === $typeCommission || 3 === $typeCommission) {
                if (empty($membreFile['COURRIER_CONVOCATIONVISITE'])) {
                    $erreurFile = true;
                } else {
                    $nomFichier = $membreFile['ID_COMMISSIONMEMBRE'].'CONVOCATIONVISITE_'.$membreFile['COURRIER_CONVOCATIONVISITE'];
                }
            }

            // Vérifier si au moins un dossier concerne le membre
            $cptConcerne = 0;
            foreach ($listeCommunes as $commune) {
                foreach ($listeDossiers as $dossierInfos) {
                    /** @var array<int, array<string, mixed>> $adresses */
                    $adresses = $dossierInfos['infosEtab']['adresses'] ?? [];
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
                    'message' => 'Le membre : '.$membreLabel." n'a aucun document renseigné ou il n'a pas les droits pour ces dossiers",
                    'pieceJointeId' => null,
                ];

                continue;
            }

            $templatePath = $this->courriersMembresRealDataPath.DIRECTORY_SEPARATOR.$nomFichier;

            if (!file_exists($templatePath)) {
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le fichier template pour le membre : '.$membreLabel." n'existe pas",
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
                    'message' => 'La convocation pour le membre : '.$membreLabel.' a bien été générée',
                    'pieceJointeId' => $pjId,
                ];
            } catch (\Exception $e) {
                $this->logger->error('Erreur génération convocation membre '.$membreLabel, ['exception' => $e]);
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Erreur lors de la génération pour le membre : '.$membreLabel.' - '.$e->getMessage(),
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

        $commissionInfo = $this->getDateCommissionInfo($dateCommId);
        if (null === $commissionInfo) {
            return $results;
        }

        $listeDossiers = $this->loadDossiers($dateCommId, (int) ($commissionInfo['GESTION_HEURES'] ?? 0));
        $listeDossiers = $this->enrichDossiers($listeDossiers, false);

        $listeMembres = $this->loadMembresWithCompetences((int) ($commissionInfo['COMMISSION_CONCERNE'] ?? 0));
        $commissionInfos = $this->getCommissionInfo((int) ($commissionInfo['COMMISSION_CONCERNE'] ?? 0));
        $listeCommunes = $this->buildListeCommunes($listeDossiers);

        $dateComm = isset($listeDossiers[0]) ? (string) ($listeDossiers[0]['DATE_COMMISSION'] ?? '') : '';
        $heureDeb = isset($listeDossiers[0]) ? (string) ($listeDossiers[0]['HEUREDEB_COMMISSION'] ?? '') : '';

        foreach ($listeMembres as $membre) {
            /** @var array<string, mixed> $membreFile */
            $membreFile = $this->getMembreFirstFile($membre);
            $membreLabel = (string) ($membreFile['LIBELLE_COMMISSIONMEMBRE'] ?? '');
            $membreId = (int) ($membreFile['ID_COMMISSIONMEMBRE'] ?? 0);
            $nomFichier = $membreFile['ID_COMMISSIONMEMBRE'].'ODJ_'.$membreFile['COURRIER_ODJ'];
            $templatePath = $this->courriersMembresRealDataPath.DIRECTORY_SEPARATOR.$nomFichier;

            if (!file_exists($templatePath)) {
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Le fichier pour le membre : '.$membreLabel." n'a pas été renseigné",
                    'pieceJointeId' => null,
                ];

                continue;
            }

            try {
                if (empty($membreFile['ID_GROUPEMENT'])) {
                    $pjId = $this->generateOdjParCommune(
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
                    $pjId = $this->generateOdjParGroupement(
                        $templatePath,
                        $listeDossiers,
                        $membre,
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
                    'message' => "L'ordre du jour pour le membre : ".$membreLabel.' a bien été généré',
                    'pieceJointeId' => $pjId,
                ];
            } catch (\Exception $e) {
                $this->logger->error('Erreur génération ODJ membre '.$membreLabel, ['exception' => $e]);
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => 'Erreur lors de la génération pour le membre : '.$membreLabel.' - '.$e->getMessage(),
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

        $commissionInfo = $this->getDateCommissionInfo($dateCommId);
        if (null === $commissionInfo) {
            return $results;
        }

        $listeDossiers = $this->loadDossiers($dateCommId, (int) ($commissionInfo['GESTION_HEURES'] ?? 0));
        $listeDossiers = $this->enrichDossiers($listeDossiers, true);

        $commissionInfos = $this->getCommissionInfo((int) ($commissionInfo['COMMISSION_CONCERNE'] ?? 0));

        $dateComm = (string) ($commissionInfo['DATE_COMMISSION'] ?? '');

        // Pour le PV, on boucle sur les membres SANS groupement
        $membresFiles = $this->loadMembresFilesForPv((int) ($commissionInfo['COMMISSION_CONCERNE'] ?? 0));

        foreach ($membresFiles as $membre) {
            $membreLabel = (string) ($membre['LIBELLE_COMMISSIONMEMBRE'] ?? '');
            $membreId = (int) ($membre['ID_COMMISSIONMEMBRE'] ?? 0);

            if (!empty($membre['ID_GROUPEMENT'])) {
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => sprintf("Le fichier pour le membre : %s n'a pas été renseigné", $membreLabel),
                    'pieceJointeId' => null,
                ];

                continue;
            }

            $nomFichier = $membre['ID_COMMISSIONMEMBRE'].'PV_'.$membre['COURRIER_PV'];
            $templatePath = $this->courriersMembresRealDataPath.DIRECTORY_SEPARATOR.$nomFichier;

            if (!file_exists($templatePath)) {
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => sprintf("Le fichier pour le membre : %s n'a pas été renseigné", $membreLabel),
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
                    'message' => 'Le PV a été généré avec succès',
                    'pieceJointeId' => $pjId,
                ];
            } catch (\Exception $e) {
                $this->logger->error('Erreur génération PV membre '.$membreLabel, ['exception' => $e]);
                $results[] = [
                    'memberId' => $membreId,
                    'memberLabel' => $membreLabel,
                    'status' => 'error',
                    'message' => sprintf('Erreur lors de la génération PV pour le membre : %s - ', $membreLabel).$e->getMessage(),
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

        $commissionInfo = $this->getDateCommissionInfo($dateCommId);
        if (null === $commissionInfo) {
            return $results;
        }

        $listeDossiers = $this->loadDossiers($dateCommId, (int) ($commissionInfo['GESTION_HEURES'] ?? 0));
        $listeDossiers = $this->enrichDossiers($listeDossiers, false);

        $commissionInfos = $this->getCommissionInfo((int) ($commissionInfo['COMMISSION_CONCERNE'] ?? 0));

        $dateComm = (string) ($commissionInfo['DATE_COMMISSION'] ?? '');

        $nomFichier = (string) ($commissionInfos[0]['DOCUMENT_CR'] ?? '');
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

        $templatePath = $this->documentsCommissionRealDataPath.DIRECTORY_SEPARATOR.$nomFichier;

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
                'message' => 'Le compte rendu a été généré avec succès',
                'pieceJointeId' => $pjId,
            ];
        } catch (\Exception $exception) {
            $this->logger->error('Erreur génération compte-rendu', ['exception' => $exception]);
            $results[] = [
                'memberId' => 0,
                'memberLabel' => '',
                'status' => 'error',
                'message' => 'Erreur lors de la génération du compte-rendu - '.$exception->getMessage(),
                'pieceJointeId' => null,
            ];
        }

        return $results;
    }

    // ========================================================================
    // PRIVATE — Chargement des données
    // ========================================================================

    /**
     * @return array<string, mixed>|null
     */
    private function getDateCommissionInfo(int $dateCommId): ?array
    {
        /** @var array<string, mixed>|false $row */
        $row = $this->conn->fetchAssociative(
            'SELECT dc.*, c.ID_COMMISSIONTYPE
             FROM datecommission dc
             JOIN commission c ON dc.COMMISSION_CONCERNE = c.ID_COMMISSION
             WHERE dc.ID_DATECOMMISSION = :id',
            ['id' => $dateCommId]
        );

        if (false === $row) {
            return null;
        }

        return $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCommissionInfo(int $commissionId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT * FROM commission WHERE ID_COMMISSION = :id',
            ['id' => $commissionId]
        );
    }

    /**
     * Charge les dossiers affectés à une dateCommission.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadDossiers(int $dateCommId, int $gestionHeures): array
    {
        $orderBy = 1 === $gestionHeures ? 'da.HEURE_DEB' : 'da.ORDRE_AFFECTATION';

        return $this->conn->fetchAllAssociative(
            'SELECT da.*, d.*, dnl.LIBELLE_DOSSIERNATURE
             FROM dossieraffectation da
             JOIN dossier d ON da.ID_DOSSIER = d.ID_DOSSIER
             LEFT JOIN dossiernature dn ON d.ID_DOSSIER = dn.ID_DOSSIER
             LEFT JOIN dossiernatureliste dnl ON dn.ID_DOSSIERNATURE = dnl.ID_DOSSIERNATURE
             WHERE da.ID_DATECOMMISSION_AFFECT = :dateCommId
             ORDER BY '.$orderBy,
            ['dateCommId' => $dateCommId]
        );
    }

    /**
     * Enrichit les dossiers avec établissement, avis dérogations, prescriptions, formulaires.
     *
     * @param array<int, array<string, mixed>> $listeDossiers
     *
     * @return array<int, array<string, mixed>>
     */
    private function enrichDossiers(array $listeDossiers, bool $includePrescriptions): array
    {
        foreach ($listeDossiers as $val => $dossier) {
            $dossierId = (int) $dossier['ID_DOSSIER'];

            // Préventionnistes
            $listeDossiers[$val]['preventionnistes'] = $this->getPreventionnistes($dossierId);

            // Établissement lié au dossier
            $listeEtab = $this->getEtablissementDossier($dossierId);
            if (empty($listeEtab)) {
                unset($listeDossiers[$val]);

                continue;
            }

            $etabId = (int) $listeEtab[0]['ID_ETABLISSEMENT'];
            $etablissementInfos = $this->getEtablissementInfos($etabId);
            $listeDossiers[$val]['infosEtab'] = $etablissementInfos;

            // Avis dérogations du dossier
            $listeDossiers[$val]['AVIS_DEROGATIONS'] = $this->getAvisDerogationsFromDossier($dossierId);

            // Avis dérogations établissement (parent + enfants)
            $idsEtablissements = [$etabId];
            if (!empty($etablissementInfos['etablissement_lies'])) {
                $idsEtablissements = array_merge(
                    $idsEtablissements,
                    array_column($etablissementInfos['etablissement_lies'], 'ID_ETABLISSEMENT')
                );
            }

            $listeDossiers[$val]['AVIS_DEROGATIONS_ETABLISSEMENT'] = $this->getAvisDerogationsEtablissements($idsEtablissements);

            // Documents d'urbanisme
            $listeDossiers[$val]['listeDocUrba'] = $this->getDocUrba($dossierId);

            // Prescriptions (PV uniquement)
            if ($includePrescriptions) {
                $listeDossiers[$val] = $this->enrichWithPrescriptions($listeDossiers[$val], $dossierId);
            }

            // Formulaires personnalisés
            $listeDossiers[$val]['FORMULAIRES'] = $this->getFormulaires($dossierId, $etabId);
        }

        return array_values($listeDossiers);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPreventionnistes(int $dossierId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT ui.*
             FROM utilisateurinformations ui
             JOIN dossierpreventionniste dp ON ui.ID_UTILISATEURINFORMATIONS = dp.ID_UTILISATEURINFORMATIONS
             WHERE dp.ID_DOSSIER = :id',
            ['id' => $dossierId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getEtablissementDossier(int $dossierId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT ed.ID_ETABLISSEMENT
             FROM etablissementdossier ed
             WHERE ed.ID_DOSSIER = :id',
            ['id' => $dossierId]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getEtablissementInfos(int $etabId): array
    {
        // Infos générales
        /** @var array<string, mixed>|false $general */
        $general = $this->conn->fetchAssociative(
            'SELECT * FROM etablissement WHERE ID_ETABLISSEMENT = :id',
            ['id' => $etabId]
        );

        if (false === $general) {
            return [];
        }

        // Infos détaillées (dernière version)
        /** @var array<string, mixed>|false $informations */
        $informations = $this->conn->fetchAssociative(
            'SELECT ei.*, cat.LIBELLE_CATEGORIE, ta.LIBELLE_ACTIVITE, g.LIBELLE_GENRE,
                    t.LIBELLE_TYPE as LIBELLE_TYPE_PRINCIPAL
             FROM etablissementinformations ei
             LEFT JOIN categorie cat ON ei.ID_CATEGORIE = cat.ID_CATEGORIE
             LEFT JOIN typeactivite ta ON ei.ID_TYPEACTIVITE = ta.ID_TYPEACTIVITE
             LEFT JOIN genre g ON ei.ID_GENRE = g.ID_GENRE
             LEFT JOIN type t ON ei.ID_TYPE = t.ID_TYPE
             WHERE ei.ID_ETABLISSEMENT = :id
             ORDER BY ei.ID_ETABLISSEMENTINFORMATIONS DESC LIMIT 1',
            ['id' => $etabId]
        );

        // Adresses
        $adresses = $this->conn->fetchAllAssociative(
            'SELECT ea.NUMERO_ADRESSE, ar.LIBELLE_RUE, ac.LIBELLE_COMMUNE,
                    ac.CODEPOSTAL_COMMUNE, ac.NUMINSEE_COMMUNE
             FROM etablissementadresse ea
             JOIN adresserue ar ON ea.ID_RUE = ar.ID_RUE
             JOIN adressecommune ac ON ea.NUMINSEE_COMMUNE = ac.NUMINSEE_COMMUNE
             WHERE ea.ID_ETABLISSEMENT = :id',
            ['id' => $etabId]
        );

        // Parents
        $parents = $this->conn->fetchAllAssociative(
            'SELECT ei.LIBELLE_ETABLISSEMENTINFORMATIONS
             FROM etablissementlie el
             JOIN etablissementinformations ei ON el.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
             WHERE el.ID_FILS_ETABLISSEMENT = :id
             ORDER BY ei.ID_ETABLISSEMENTINFORMATIONS DESC',
            ['id' => $etabId]
        );

        // Établissements liés (enfants)
        $etablissementLies = $this->conn->fetchAllAssociative(
            'SELECT el.ID_FILS_ETABLISSEMENT as ID_ETABLISSEMENT
             FROM etablissementlie el
             WHERE el.ID_ETABLISSEMENT = :id',
            ['id' => $etabId]
        );

        // Dernière visite
        /** @var array<string, mixed>|false $lastVisite */
        $lastVisite = $this->conn->fetchAssociative(
            'SELECT d.DATEVISITE_DOSSIER
             FROM dossier d
             JOIN etablissementdossier ed ON d.ID_DOSSIER = ed.ID_DOSSIER
             WHERE ed.ID_ETABLISSEMENT = :id
               AND d.TYPE_DOSSIER IN (2,3)
               AND d.DATEVISITE_DOSSIER IS NOT NULL
             ORDER BY d.DATEVISITE_DOSSIER DESC LIMIT 1',
            ['id' => $etabId]
        );

        return [
            'general' => $general,
            'informations' => false !== $informations ? $informations : [],
            'adresses' => $adresses,
            'parents' => $parents,
            'etablissement_lies' => $etablissementLies,
            'last_visite' => false !== $lastVisite ? $lastVisite['DATEVISITE_DOSSIER'] : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAvisDerogationsFromDossier(int $dossierId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT ad.*, a.LIBELLE_AVIS, d.DATECOMM_DOSSIER, d.DATEVISITE_DOSSIER, ad.ID_DOSSIER_LIE
             FROM avisderogation ad
             LEFT JOIN avis a ON ad.ID_AVIS = a.ID_AVIS
             LEFT JOIN dossier d ON ad.ID_DOSSIER_LIE = d.ID_DOSSIER
             WHERE ad.ID_DOSSIER = :id',
            ['id' => $dossierId]
        );
    }

    /**
     * @param int[] $idsEtablissements
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAvisDerogationsEtablissements(array $idsEtablissements): array
    {
        if (empty($idsEtablissements)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsEtablissements), '?'));

        return $this->conn->fetchAllAssociative(
            'SELECT ad.*, a.LIBELLE_AVIS, d.DATECOMM_DOSSIER, d.DATEVISITE_DOSSIER, ad.ID_DOSSIER_LIE
             FROM avisderogation ad
             LEFT JOIN avis a ON ad.ID_AVIS = a.ID_AVIS
             LEFT JOIN dossier d ON ad.ID_DOSSIER_LIE = d.ID_DOSSIER
             JOIN etablissementdossier ed ON ad.ID_DOSSIER = ed.ID_DOSSIER
             WHERE ed.ID_ETABLISSEMENT IN ('.$placeholders.')
               AND ad.ID_DOSSIER != ed.ID_DOSSIER',
            $idsEtablissements
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDocUrba(int $dossierId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT * FROM dossierdocurba WHERE ID_DOSSIER = :id',
            ['id' => $dossierId]
        );
    }

    /**
     * @param array<string, mixed> $dossier
     *
     * @return array<string, mixed>
     */
    private function enrichWithPrescriptions(array $dossier, int $dossierId): array
    {
        $regles = $this->getPrescriptionsArray($dossierId, TypePrescription::REGLEMENTAIRE);
        $dossier['prescriptionReglDossier'] = PrescriptionArrayHelper::withoutLevees($regles);
        $dossier['prescriptionReglDossierReprises'] = PrescriptionArrayHelper::withoutActuals($dossier['prescriptionReglDossier']);
        $dossier['prescriptionReglDossierActuelles'] = PrescriptionArrayHelper::withoutPrevious($dossier['prescriptionReglDossier']);

        $exploitation = $this->getPrescriptionsArray($dossierId, TypePrescription::EXPLOITATION);
        $dossier['prescriptionExploitation'] = PrescriptionArrayHelper::withoutLevees($exploitation);
        $dossier['prescriptionExploitationReprises'] = PrescriptionArrayHelper::withoutActuals($dossier['prescriptionExploitation']);
        $dossier['prescriptionExploitationActuelles'] = PrescriptionArrayHelper::withoutPrevious($dossier['prescriptionExploitation']);

        $amelioration = $this->getPrescriptionsArray($dossierId, TypePrescription::AMELIORATION);
        $dossier['prescriptionAmelioration'] = PrescriptionArrayHelper::withoutLevees($amelioration);
        $dossier['prescriptionAmeliorationReprises'] = PrescriptionArrayHelper::withoutActuals($dossier['prescriptionAmelioration']);
        $dossier['prescriptionAmeliorationActuelles'] = PrescriptionArrayHelper::withoutPrevious($dossier['prescriptionAmelioration']);

        return $dossier;
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    private function getPrescriptionsArray(int $dossierId, int $type): array
    {
        $rows = $this->conn->fetchAllAssociative(
            'SELECT pd.ID_PRESCRIPTION_DOSSIER, pd.NUM_PRESCRIPTION_DOSSIER,
                    pd.LIBELLE_PRESCRIPTION_DOSSIER, pd.DATE_LEVEE,
                    pd.ID_DOSSIER_REPRISE,
                    pt.PRESCRIPTIONTYPE_LIBELLE,
                    pda.ID_PRESCRIPTIONARTICLELISTE, pda.ID_PRESCRIPTIONTEXTELISTE,
                    pal.LIBELLE_ARTICLE, ptl.LIBELLE_TEXTE
             FROM prescriptiondossier pd
             LEFT JOIN prescriptiontype pt ON pd.ID_PRESCRIPTIONTYPE = pt.ID_PRESCRIPTIONTYPE
             LEFT JOIN prescriptiondossierassociation pda ON pd.ID_PRESCRIPTION_DOSSIER = pda.ID_PRESCRIPTION_DOSSIER
             LEFT JOIN prescriptionarticleliste pal ON pda.ID_PRESCRIPTIONARTICLELISTE = pal.ID_PRESCRIPTIONARTICLELISTE
             LEFT JOIN prescriptiontexteliste ptl ON pda.ID_PRESCRIPTIONTEXTELISTE = ptl.ID_PRESCRIPTIONTEXTELISTE
             WHERE pd.ID_DOSSIER = :id AND pd.TYPE_PRESCRIPTION_DOSSIER = :type
             ORDER BY pd.NUM_PRESCRIPTION_DOSSIER, pda.ID_PRESCRIPTIONDOSSIERASSOCIATION',
            ['id' => $dossierId, 'type' => $type]
        );

        // Grouper par prescription
        $result = [];
        foreach ($rows as $row) {
            $prescId = $row['ID_PRESCRIPTION_DOSSIER'];
            if (!isset($result[$prescId])) {
                $result[$prescId] = [];
            }

            $result[$prescId][] = $row;
        }

        return array_values($result);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFormulaires(int $dossierId, int $etabId): array
    {
        // Récupère les capsules rubriques et leurs champs
        $capsules = $this->conn->fetchAllAssociative(
            'SELECT * FROM capsulerubrique ORDER BY ID_CAPSULERUBRIQUE'
        );

        foreach ($capsules as $idx => $capsule) {
            $nomInterne = $capsule['NOM_INTERNE'] ?? '';
            $capsules[$idx]['RUBRIQUES'] = $this->getRubriquesForCapsule($nomInterne, $dossierId, $etabId);
        }

        return $capsules;
    }

    /**
     * @return array<int, array<string, mixed>>|string
     */
    private function getRubriquesForCapsule(string $nomInterne, int $dossierId, int $etabId)
    {
        // Déterminer l'objet lié selon le nomInterne
        switch ($nomInterne) {
            case 'descriptifVerificationsTechniques':
            case 'effectifsDegagementsDossier':
                $objectId = $dossierId;
                $objectType = 'Dossier';

                break;
            case 'descriptifEtablissement':
            case 'effectifsDegagementsEtablissement':
                $objectId = $etabId;
                $objectType = 'Etablissement';

                break;
            default:
                return '';
        }

        $rubriques = $this->conn->fetchAllAssociative(
            'SELECT r.* FROM rubrique r
             JOIN capsulerubrique cr ON r.ID_CAPSULERUBRIQUE = cr.ID_CAPSULERUBRIQUE
             WHERE cr.NOM_INTERNE = :nomInterne
             ORDER BY r.ID_RUBRIQUE',
            ['nomInterne' => $nomInterne]
        );

        foreach ($rubriques as $rIdx => $rubrique) {
            $rubriques[$rIdx]['CHAMPS'] = $this->getChampsForRubrique(
                (int) $rubrique['ID_RUBRIQUE'],
                $objectId,
                $objectType
            );
        }

        return $rubriques;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getChampsForRubrique(int $rubriqueId, int $objectId, string $objectType): array
    {
        $champs = $this->conn->fetchAllAssociative(
            'SELECT c.*, tc.LIBELLE as TYPE
             FROM champ c
             JOIN typechamp tc ON c.ID_TYPECHAMP = tc.ID_TYPECHAMP
             WHERE c.ID_RUBRIQUE = :rubriqueId AND c.ID_PARENT IS NULL
             ORDER BY c.ORDRE',
            ['rubriqueId' => $rubriqueId]
        );

        foreach ($champs as $cIdx => $champ) {
            $champId = (int) $champ['ID_CHAMP'];

            // Récupérer la valeur
            /** @var array<string, mixed>|false $valeur */
            $valeur = $this->conn->fetchAssociative(
                'SELECT v.* FROM valeur v
                 WHERE v.ID_CHAMP = :champId AND v.ID_OBJET = :objectId AND v.TYPE_OBJET = :objectType',
                ['champId' => $champId, 'objectId' => $objectId, 'objectType' => $objectType]
            );
            $champs[$cIdx]['VALEUR'] = false !== $valeur ? ($valeur['VALEUR'] ?? null) : null;
            $champs[$cIdx]['tableau'] = (int) ($champ['TABLEAU'] ?? 0);

            if ('Parent' === $champ['TYPE']) {
                $champs[$cIdx]['FILS'] = $this->getChampsFils($champId, $objectId, $objectType);
            }
        }

        return $champs;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getChampsFils(int $parentId, int $objectId, string $objectType): array
    {
        $fils = $this->conn->fetchAllAssociative(
            'SELECT c.*, tc.LIBELLE as TYPE
             FROM champ c
             JOIN typechamp tc ON c.ID_TYPECHAMP = tc.ID_TYPECHAMP
             WHERE c.ID_PARENT = :parentId
             ORDER BY c.ORDRE',
            ['parentId' => $parentId]
        );

        $result = [];
        foreach ($fils as $idx => $f) {
            /** @var array<string, mixed>|false $valeur */
            $valeur = $this->conn->fetchAssociative(
                'SELECT v.* FROM valeur v
                 WHERE v.ID_CHAMP = :champId AND v.ID_OBJET = :objectId AND v.TYPE_OBJET = :objectType',
                ['champId' => (int) $f['ID_CHAMP'], 'objectId' => $objectId, 'objectType' => $objectType]
            );
            $f['VALEUR'] = false !== $valeur ? ($valeur['VALEUR'] ?? null) : null;
            $f['idx'] = $f['NOM'] ?? (string) $idx;
            $result[$idx] = $f;
        }

        // Récupérer les valeurs tableau si parent est un tableau
        $valeursTableau = $this->conn->fetchAllAssociative(
            'SELECT vt.*, c.NOM, tc.LIBELLE as TYPE
             FROM valeurtableau vt
             JOIN champ c ON vt.ID_CHAMP = c.ID_CHAMP
             JOIN typechamp tc ON c.ID_TYPECHAMP = tc.ID_TYPECHAMP
             WHERE c.ID_PARENT = :parentId AND vt.ID_OBJET = :objectId AND vt.TYPE_OBJET = :objectType
             ORDER BY vt.LIGNE, c.ORDRE',
            ['parentId' => $parentId, 'objectId' => $objectId, 'objectType' => $objectType]
        );

        // Grouper par ligne
        $lignes = [];
        foreach ($valeursTableau as $vt) {
            $ligne = (int) $vt['LIGNE'];
            if (!isset($lignes[$ligne])) {
                $lignes[$ligne] = [];
            }

            $lignes[$ligne][] = $vt;
        }

        $result['VALEURS'] = array_values($lignes);

        return $result;
    }

    /**
     * Charge les membres avec leurs compétences (catégories, types, classes, dossiertypes).
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadMembresWithCompetences(int $commissionId): array
    {
        $membres = $this->conn->fetchAllAssociative(
            'SELECT cm.ID_COMMISSIONMEMBRE as id_membre, cm.*
             FROM commissionmembre cm
             WHERE cm.ID_COMMISSION = :id',
            ['id' => $commissionId]
        );

        foreach ($membres as $idx => $membre) {
            $membreId = (int) $membre['id_membre'];

            // InfosFiles
            $membres[$idx]['infosFiles'] = $this->conn->fetchAllAssociative(
                'SELECT * FROM commissionmembre WHERE ID_COMMISSIONMEMBRE = :id',
                ['id' => $membreId]
            );

            // Compétences catégories
            $categories = $this->conn->fetchAllAssociative(
                'SELECT * FROM commissionmembrecategorie WHERE ID_COMMISSIONMEMBRE = :id',
                ['id' => $membreId]
            );
            $membres[$idx]['categories'] = [];
            foreach ($categories as $cat) {
                $membres[$idx]['categories'][$cat['ID_CATEGORIE']] = $cat;
            }

            // Compétences types
            $types = $this->conn->fetchAllAssociative(
                'SELECT * FROM commissionmembretype WHERE ID_COMMISSIONMEMBRE = :id',
                ['id' => $membreId]
            );
            $membres[$idx]['types'] = [];
            foreach ($types as $t) {
                $membres[$idx]['types'][$t['ID_TYPEACTIVITE']] = $t;
            }

            // Compétences classes
            $classes = $this->conn->fetchAllAssociative(
                'SELECT * FROM commissionmembreclasse WHERE ID_COMMISSIONMEMBRE = :id',
                ['id' => $membreId]
            );
            $membres[$idx]['classes'] = [];
            foreach ($classes as $cl) {
                $membres[$idx]['classes'][$cl['ID_CLASSE']] = $cl;
            }

            // Compétences dossier types
            $dossiertypes = $this->conn->fetchAllAssociative(
                'SELECT * FROM commissionmembredossiertype WHERE ID_COMMISSIONMEMBRE = :id',
                ['id' => $membreId]
            );
            $membres[$idx]['dossiertypes'] = [];
            foreach ($dossiertypes as $dt) {
                $membres[$idx]['dossiertypes'][$dt['ID_DOSSIERTYPE']] = $dt;
            }

            // Flag manifestation
            $membres[$idx]['manifestation'] = !empty($membre['MANIFESTATION']);
        }

        return $membres;
    }

    /**
     * Charge les membres (sans groupement) pour le PV.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadMembresFilesForPv(int $commissionId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT * FROM commissionmembre
             WHERE ID_COMMISSION = :id AND ID_GROUPEMENT IS NULL',
            ['id' => $commissionId]
        );
    }

    /**
     * Construit la liste des communes à partir des dossiers.
     *
     * @param array<int, array<string, mixed>> $listeDossiers
     *
     * @return array<int, array<int, mixed>>
     */
    private function buildListeCommunes(array $listeDossiers): array
    {
        $tabCommune = [];
        $numCommune = 0;

        foreach ($listeDossiers as $ue) {
            if (!isset($ue['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'])) {
                continue;
            }

            $libelleCommune = $ue['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'];
            $numInsee = $ue['infosEtab']['adresses'][0]['NUMINSEE_COMMUNE'] ?? '';

            // Vérifier si la commune existe déjà
            $existe = false;
            foreach ($tabCommune as $value) {
                if (isset($value[0]) && $value[0] == $libelleCommune) {
                    $existe = true;

                    break;
                }
            }

            if (!$existe) {
                $communeInfo = $this->getCommuneContactInfo($numInsee);
                $tabCommune[$numCommune] = [$libelleCommune, $communeInfo];
                ++$numCommune;
            }
        }

        return $tabCommune;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCommuneContactInfo(string $numInsee): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT ui.*
             FROM utilisateurinformations ui
             JOIN adressecommune ac ON ac.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
             WHERE ac.NUMINSEE_COMMUNE = :numInsee',
            ['numInsee' => $numInsee]
        );
    }

    // ========================================================================
    // PRIVATE — Génération convocation
    // ========================================================================

    /**
     * @param array<int, array<string, mixed>> $listeDossiers
     * @param array<string, mixed>             $membre
     * @param array<int, array<int, mixed>>    $listeCommunes
     * @param array<int, array<string, mixed>> $commissionInfos
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
            if (isset($commune[1][0])) {
                $this->addChamp($villeODF, 'numMairie', $commune[1][0]['NUMEROADRESSE_UTILISATEURINFORMATIONS'] ?? '');
                $this->addChamp($villeODF, 'rueMairie', $commune[1][0]['RUEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
                $this->addChamp($villeODF, 'cpMairie', $commune[1][0]['CPADRESSE_UTILISATEURINFORMATIONS'] ?? '');
            }

            $this->addChamp($villeODF, 'communeMairie', $commune[0] ?? '');
            $this->addChamp($villeODF, 'commissionNom', $commissionInfos[0]['LIBELLE_COMMISSION'] ?? '');

            $dateDuJour = new \DateTime();
            $this->addChamp($villeODF, 'date', $dateDuJour->format('d/m/Y'));

            $dateCommObj = $this->parseDateSql($dateComm);
            $this->addChamp($villeODF, 'dateCommission', null !== $dateCommObj ? $dateCommObj->format('d/m/Y') : '');

            $heureCommTab = explode(':', $heureDeb);
            $heureComm = $heureCommTab[0].':'.$heureCommTab[1];
            $this->addChamp($villeODF, 'heureDebutComm', $heureComm);

            // Heure début 1er dossier concerné
            $heureDebut = '';
            foreach ($listeDossiers as $dossierInfos) {
                if (
                    count($dossierInfos['infosEtab']['adresses']) > 0
                    && $dossierInfos['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'] == $commune[0]
                    && $this->eligibilityChecker->isDossierConcerneMembre($membre, $dossierInfos)
                    && isset($dossierInfos['HEURE_DEB_AFFECT'])
                ) {
                    $heureDebutTab = explode(':', $dossierInfos['HEURE_DEB_AFFECT']);
                    $heureDebut = $heureDebutTab[0].':'.$heureDebutTab[1];

                    break;
                }
            }

            $this->addChamp($villeODF, 'heureDeb1erDossier', $heureDebut);

            // Segment infosDoss
            $infosDoss = $villeODF->infosDoss;
            foreach ($listeDossiers as $dossierInfos) {
                if (
                    count($dossierInfos['infosEtab']['adresses']) > 0
                    && $dossierInfos['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'] == $commune[0]
                    && $this->eligibilityChecker->isDossierConcerneMembre($membre, $dossierInfos)
                ) {
                    $this->fillConvocationDossierInfos($infosDoss, $dossierInfos);
                    $villeODF->infosDoss->merge();
                }
            }

            $villeODF->merge();
        }

        $odf->mergeSegment($villeODF);

        $pj = $this->savePieceJointe(
            $dateCommId,
            'Convocation',
            'Convocations du membre '.($membre['infosFiles'][0]['LIBELLE_COMMISSIONMEMBRE'] ?? '')
        );

        $path = $this->dataStore->getFilePath($pj, 'dateCommission', $dateCommId, true);
        $odf->saveToDisk($path);

        return (int) $pj->getId();
    }

    /**
     * @param array<int, array<string, mixed>> $listeDossiers
     * @param array<string, mixed>             $membre
     * @param array<int, array<int, mixed>>    $listeCommunes
     * @param array<int, array<string, mixed>> $commissionInfos
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
        $groupementId = (int) $membre['infosFiles'][0]['ID_GROUPEMENT'];

        // Récupérer les infos du groupement
        /** @var array<string, mixed>|false $infosGroupement */
        $infosGroupement = $this->conn->fetchAssociative(
            'SELECT * FROM groupement WHERE ID_GROUPEMENT = :id',
            ['id' => $groupementId]
        );
        if (false === $infosGroupement) {
            $infosGroupement = [];
        }

        // Récupérer les communes du groupement
        $listeVillesGroupement = $this->conn->fetchAllAssociative(
            'SELECT ac.NUMINSEE_COMMUNE
             FROM groupementcommune gc
             JOIN adressecommune ac ON gc.NUMINSEE_COMMUNE = ac.NUMINSEE_COMMUNE
             WHERE gc.ID_GROUPEMENT = :id',
            ['id' => $groupementId]
        );

        $odf = new \Odf($templatePath);

        $this->fillGroupementCommissionInfo($odf, $commissionInfos, $heureDeb, $dateComm, $infosGroupement, $listeCommunes);

        $grpLoop = $odf->setSegment('grpLoop');
        $this->fillGroupementCommissionInfo($grpLoop, $commissionInfos, $heureDeb, $dateComm, $infosGroupement, $listeCommunes);

        foreach ($listeDossiers as $dossierInfos) {
            $dossierExported = false;

            foreach ($listeVillesGroupement as $villeGrp) {
                if (
                    count($dossierInfos['infosEtab']['adresses']) > 0
                    && $dossierInfos['infosEtab']['adresses'][0]['NUMINSEE_COMMUNE'] == $villeGrp['NUMINSEE_COMMUNE']
                    && $this->eligibilityChecker->isDossierConcerneMembre($membre, $dossierInfos)
                ) {
                    $this->fillConvocationDossierInfos($grpLoop, $dossierInfos);
                    $this->addChamp($odf, 'nomEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
                    $this->addChamp($odf, 'numWinPrev', $dossierInfos['infosEtab']['general']['NUMEROID_ETABLISSEMENT'] ?? '');
                    $dossierExported = true;

                    break;
                }
            }

            if ($dossierExported) {
                $grpLoop->merge();
            }
        }

        $odf->mergeSegment($grpLoop);

        $pj = $this->savePieceJointe(
            $dateCommId,
            'Convocation',
            'Convocations du membre '.($membre['infosFiles'][0]['LIBELLE_COMMISSIONMEMBRE'] ?? '')
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
     * @param object               $segment      Segment ODT (Odf ou Segment)
     * @param array<string, mixed> $dossierInfos
     */
    private function fillConvocationDossierInfos($segment, array $dossierInfos): void
    {
        $this->addChamp($segment, 'nomEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');

        $nomPere = '';
        if (isset($dossierInfos['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
            $nomPere = $dossierInfos['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'];
        }

        $this->addChamp($segment, 'nomPereEtab', $nomPere);
        $this->addChamp($segment, 'idDossier', (string) ($dossierInfos['ID_DOSSIER'] ?? ''));
        $this->addChamp($segment, 'numWinPrev', $dossierInfos['infosEtab']['general']['NUMEROID_ETABLISSEMENT'] ?? '');
        $this->addChamp($segment, 'mailEtab', $dossierInfos['infosEtab']['general']['COURRIEL_ETABLISSEMENT'] ?? '');
        $this->addChamp($segment, 'communeEtab', $dossierInfos['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'] ?? '');

        $adresse = ($dossierInfos['infosEtab']['adresses'][0]['NUMERO_ADRESSE'] ?? '').' ';
        $adresse .= ($dossierInfos['infosEtab']['adresses'][0]['LIBELLE_RUE'] ?? '').' ';
        $adresse .= ($dossierInfos['infosEtab']['adresses'][0]['CODEPOSTAL_COMMUNE'] ?? '').' ';
        $adresse .= ($dossierInfos['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'] ?? '');
        $this->addChamp($segment, 'etablissementAdresse', $adresse);

        $this->addChamp($segment, 'categorieEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_CATEGORIE'] ?? '');
        $this->addChamp($segment, 'typeLettreP', $dossierInfos['infosEtab']['informations']['LIBELLE_TYPE_PRINCIPAL'] ?? '');
        $this->addChamp($segment, 'natureDossier', $dossierInfos['LIBELLE_DOSSIERNATURE'] ?? '');
        $this->addChamp($segment, 'objetDossier', $dossierInfos['OBJET_DOSSIER'] ?? '');
        $this->addChamp($segment, 'avisDossierCommission', AvisCommission::getLibelle($dossierInfos['AVIS_DOSSIER_COMMISSION'] ?? null));
        $this->addChamp($segment, 'avisDossier', AvisCommission::getLibelle($dossierInfos['AVIS_DOSSIER'] ?? null));

        $heureDebut = '';
        if (isset($dossierInfos['HEURE_DEB_AFFECT'])) {
            $heureDebutTab = explode(':', $dossierInfos['HEURE_DEB_AFFECT']);
            $heureDebut = $heureDebutTab[0].':'.$heureDebutTab[1];
        }

        $this->addChamp($segment, 'heureDebDossier', $heureDebut);

        $heureFin = '';
        if (isset($dossierInfos['HEURE_FIN_AFFECT'])) {
            $heureFinTab = explode(':', $dossierInfos['HEURE_FIN_AFFECT']);
            $heureFin = $heureFinTab[0].':'.$heureFinTab[1];
        }

        $this->addChamp($segment, 'heureFinDossier', $heureFin);

        $listeDocUrba = '';
        if (!empty($dossierInfos['listeDocUrba'])) {
            foreach ($dossierInfos['listeDocUrba'] as $docurb) {
                $listeDocUrba .= $docurb['NUM_DOCURBA'].'    ';
            }
        }

        $this->addChamp($segment, 'numDocUrba', $listeDocUrba);

        // Préventionnistes
        $listePreventioniste = [];
        $listeTelPreventioniste = [];
        $listeMailPreventionniste = [];
        if (isset($dossierInfos['preventionnistes'])) {
            foreach ($dossierInfos['preventionnistes'] as $preventioniste) {
                $listePreventioniste[] = ($preventioniste['GRADE_UTILISATEURINFORMATIONS'] ?? '').' '.($preventioniste['NOM_UTILISATEURINFORMATIONS'] ?? '').' '.($preventioniste['PRENOM_UTILISATEURINFORMATIONS'] ?? '');
                $listeTelPreventioniste[] = $preventioniste['TELFIXE_UTILISATEURINFORMATIONS'] ?? '';
                $listeMailPreventionniste[] = $preventioniste['MAIL_UTILISATEURINFORMATIONS'] ?? '';
            }
        }

        $this->addChamp($segment, 'preventionnistes', implode(', ', $listePreventioniste));
        $this->addChamp($segment, 'listeTelPreventioniste', implode(', ', $listeTelPreventioniste));
        $this->addChamp($segment, 'listeMailPreventioniste', implode(', ', $listeMailPreventionniste));

        // Avis dérogations
        $this->fillAvisDerogationsSegment($segment, $dossierInfos);

        // Formulaires personnalisés
        $this->fillFormulairesSegment($segment, $dossierInfos);
    }

    /**
     * @param object                           $odf
     * @param array<int, array<string, mixed>> $commissionInfos
     * @param array<string, mixed>             $infosGroupement
     * @param array<int, array<int, mixed>>    $listeCommunes
     */
    private function fillGroupementCommissionInfo($odf, array $commissionInfos, string $heureDeb, string $dateComm, array $infosGroupement, array $listeCommunes): void
    {
        $this->addChamp($odf, 'commissionNom', $commissionInfos[0]['LIBELLE_COMMISSION'] ?? '');

        $dateDuJour = new \DateTime();
        $this->addChamp($odf, 'date', $dateDuJour->format('d/m/Y'));

        $this->addChamp($odf, 'communeGrp', $infosGroupement['VILLEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
        $this->addChamp($odf, 'numGrp', $infosGroupement['NUMEROADRESSE_UTILISATEURINFORMATIONS'] ?? '');
        $this->addChamp($odf, 'rueGrp', $infosGroupement['RUEADRESSE_UTILISATEURINFORMATIONS'] ?? '');
        $this->addChamp($odf, 'cpGrp', $infosGroupement['CPADRESSE_UTILISATEURINFORMATIONS'] ?? '');

        $dateCommObj = $this->parseDateSql($dateComm);
        $this->addChamp($odf, 'dateCommission', null !== $dateCommObj ? $dateCommObj->format('d/m/Y') : '');
        $this->addChamp($odf, 'heureDebutComm', $heureDeb);

        // Liste des maires
        $maires = [];
        foreach ($listeCommunes as $commune) {
            if (isset($commune[1][0]['NOM_UTILISATEURINFORMATIONS']) && $commune[1][0]['NOM_UTILISATEURINFORMATIONS']) {
                $maires[] = $commune[1][0]['NOM_UTILISATEURINFORMATIONS'].'  '.$commune[1][0]['PRENOM_UTILISATEURINFORMATIONS'];
            }
        }

        $this->addChamp($odf, 'maires', implode(', ', $maires));
    }

    // ========================================================================
    // PRIVATE — Génération ODJ
    // ========================================================================

    /**
     * @param array<int, array<string, mixed>> $listeDossiers
     * @param array<string, mixed>             $membre
     * @param array<int, array<int, mixed>>    $listeCommunes
     * @param array<int, array<string, mixed>> $commissionInfos
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
    ): int {
        $vowels = ['a', 'e', 'i', 'o', 'u', 'y', 'A', 'E', 'I', 'O', 'U', 'Y'];
        $odf = new \Odf($templatePath);
        $membreLoop = $odf->setSegment('membresComm');

        foreach ($listeCommunes as $commune) {
            if (isset($commune[0])) {
                $firstLetter = substr($commune[0], 0, 1);
                if (in_array($firstLetter, $vowels, true)) {
                    // voyelle → d'
                }

                // consonne → de

                if (isset($commune[1]) && 1 === count($commune[1])) {
                    foreach ($commune[1] as $info) {
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
                }
            }

            $this->addChamp($membreLoop, 'commissionNom', $commissionInfos[0]['LIBELLE_COMMISSION'] ?? '');

            $dateDuJour = new \DateTime();
            $this->addChamp($membreLoop, 'date', $dateDuJour->format('d/m/Y'));

            $dateCommObj = $this->parseDateSql($dateComm);
            $this->addChamp($membreLoop, 'dateCommission', null !== $dateCommObj ? $dateCommObj->format('d/m/Y') : '');

            $heureCommTab = explode(':', $heureDeb);
            $heureComm = $heureCommTab[0].':'.$heureCommTab[1];
            $this->addChamp($membreLoop, 'heureDebutComm', $heureComm);

            // Dossiers de la commune filtrés par compétence
            foreach ($listeDossiers as $dossierInfos) {
                if (
                    count($dossierInfos['infosEtab']['adresses']) > 0
                    && $dossierInfos['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'] == $commune[0]
                    && $this->eligibilityChecker->isDossierConcerneMembre($membre, $dossierInfos)
                ) {
                    $this->fillOdjDossierInfos($membreLoop, $dossierInfos);
                    $membreLoop->infosDoss->merge();
                }
            }

            $membreLoop->merge();
        }

        $odf->mergeSegment($membreLoop);

        $pj = $this->savePieceJointe(
            $dateCommId,
            'Ordre du jour',
            'Ordre du jour du membre '.($membre['infosFiles'][0]['LIBELLE_COMMISSIONMEMBRE'] ?? '')
        );

        $path = $this->dataStore->getFilePath($pj, 'dateCommission', $dateCommId, true);
        $odf->saveToDisk($path);

        return (int) $pj->getId();
    }

    /**
     * @param array<int, array<string, mixed>> $listeDossiers
     * @param array<string, mixed>             $membre
     * @param array<int, array<string, mixed>> $commissionInfos
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
        $groupementId = (int) $membre['infosFiles'][0]['ID_GROUPEMENT'];

        /** @var array<string, mixed>|false $infosGroupement */
        $infosGroupement = $this->conn->fetchAssociative(
            'SELECT * FROM groupement WHERE ID_GROUPEMENT = :id',
            ['id' => $groupementId]
        );
        if (false === $infosGroupement) {
            $infosGroupement = [];
        }

        $odf = new \Odf($templatePath);
        $grpLoop = $odf->setSegment('grpLoop');

        $this->addChamp($grpLoop, 'commissionNom', $commissionInfos[0]['LIBELLE_COMMISSION'] ?? '');

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
        foreach ($listeDossiers as $dossierInfos) {
            $this->fillOdjDossierInfos($grpLoop, $dossierInfos);
            $grpLoop->infosDoss->merge();
        }

        $grpLoop->merge();
        $odf->mergeSegment($grpLoop);

        $pj = $this->savePieceJointe(
            $dateCommId,
            'Ordre du jour',
            'Ordre du jour du membre '.($membre['infosFiles'][0]['LIBELLE_COMMISSIONMEMBRE'] ?? '')
        );

        $path = $this->dataStore->getFilePath($pj, 'dateCommission', $dateCommId, true);
        $odf->saveToDisk($path);

        return (int) $pj->getId();
    }

    /**
     * Port direct de retrieveInfoDoss() (generationodj.phtml).
     *
     * @param object               $segment
     * @param array<string, mixed> $dossierInfos
     */
    private function fillOdjDossierInfos($segment, array $dossierInfos): void
    {
        $typeDossierCourt = 'A';
        switch ((int) ($dossierInfos['TYPE_DOSSIER'] ?? 0)) {
            case 1:
                $typeDossierCourt = 'E';

                break;
            case 2:
            case 3:
                $typeDossierCourt = 'V';

                break;
            default:
                $typeDossierCourt = 'A';

                break;
        }

        $this->addChamp($segment->infosDoss, 'idDossier', (string) ($dossierInfos['ID_DOSSIER'] ?? ''));
        $this->addChamp($segment->infosDoss, 'avisDossier', AvisCommission::getLibelle($dossierInfos['AVIS_DOSSIER'] ?? null));
        $this->addChamp($segment->infosDoss, 'nomEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
        $this->addChamp($segment->infosDoss, 'numWinPrev', $dossierInfos['infosEtab']['general']['NUMEROID_ETABLISSEMENT'] ?? '');

        $nomPere = '';
        if (isset($dossierInfos['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
            $nomPere = $dossierInfos['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'];
        }

        $this->addChamp($segment->infosDoss, 'nomPereEtab', $nomPere);

        // Date visite
        $dateVisite = '';
        try {
            if (!empty($dossierInfos['DATEVISITE_DOSSIER'])) {
                $dtVisite = $this->parseDateSql($dossierInfos['DATEVISITE_DOSSIER']);
                if (null !== $dtVisite) {
                    $dateVisite = $dtVisite->format('d/m/Y');
                }
            }
        } catch (\Exception $exception) {
            // Silencieux
        }

        $this->addChamp($segment->infosDoss, 'dateVisite', $dateVisite);

        $this->addChamp($segment->infosDoss, 'categorieEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_CATEGORIE'] ?? '');
        $this->addChamp($segment->infosDoss, 'typeLettreP', $dossierInfos['infosEtab']['informations']['LIBELLE_TYPE_PRINCIPAL'] ?? '');
        $this->addChamp($segment->infosDoss, 'natureDossier', $dossierInfos['LIBELLE_DOSSIERNATURE'] ?? '');
        $this->addChamp($segment->infosDoss, 'objetDossier', $dossierInfos['OBJET_DOSSIER'] ?? '');

        $commune = '';
        if (count($dossierInfos['infosEtab']['adresses']) > 0) {
            $commune = $dossierInfos['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'] ?? '';
        }

        $this->addChamp($segment->infosDoss, 'communeConcerne', $commune);

        $adresse = '';
        if (!empty($dossierInfos['infosEtab']['adresses'])) {
            $adresse = ($dossierInfos['infosEtab']['adresses'][0]['NUMERO_ADRESSE'] ?? '').' ';
            $adresse .= ($dossierInfos['infosEtab']['adresses'][0]['LIBELLE_RUE'] ?? '').' ';
            $adresse .= ($dossierInfos['infosEtab']['adresses'][0]['CODEPOSTAL_COMMUNE'] ?? '').' ';
            $adresse .= ($dossierInfos['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'] ?? '');
        }

        $this->addChamp($segment->infosDoss, 'etablissementAdresse', $adresse);

        $heureDebut = '';
        if (isset($dossierInfos['HEURE_DEB_AFFECT'])) {
            $heureDebutTab = explode(':', $dossierInfos['HEURE_DEB_AFFECT']);
            $heureDebut = $heureDebutTab[0].':'.$heureDebutTab[1];
        }

        $this->addChamp($segment->infosDoss, 'heureDebDossier', $heureDebut);

        $heureFin = '';
        if (isset($dossierInfos['HEURE_FIN_AFFECT'])) {
            $heureFinTab = explode(':', $dossierInfos['HEURE_FIN_AFFECT']);
            $heureFin = $heureFinTab[0].':'.$heureFinTab[1];
        }

        $this->addChamp($segment->infosDoss, 'heureFinDossier', $heureFin);

        $listeDocUrba = '';
        if (!empty($dossierInfos['listeDocUrba'])) {
            foreach ($dossierInfos['listeDocUrba'] as $docurb) {
                $listeDocUrba .= $docurb['NUM_DOCURBA'].'    ';
            }
        }

        $this->addChamp($segment->infosDoss, 'numDocUrba', $listeDocUrba);
        $this->addChamp($segment->infosDoss, 'typeDossierCourt', $typeDossierCourt);

        // Préventionnistes
        $listePreventioniste = [];
        $listeTelPreventioniste = [];
        $listeMailPreventionniste = [];
        if (isset($dossierInfos['preventionnistes'])) {
            foreach ($dossierInfos['preventionnistes'] as $preventioniste) {
                $listePreventioniste[] = ($preventioniste['GRADE_UTILISATEURINFORMATIONS'] ?? '').' '.($preventioniste['NOM_UTILISATEURINFORMATIONS'] ?? '').' '.($preventioniste['PRENOM_UTILISATEURINFORMATIONS'] ?? '');
                $listeTelPreventioniste[] = $preventioniste['TELFIXE_UTILISATEURINFORMATIONS'] ?? '';
                $listeMailPreventionniste[] = $preventioniste['MAIL_UTILISATEURINFORMATIONS'] ?? '';
            }
        }

        $this->addChamp($segment->infosDoss, 'preventionnistes', implode(', ', $listePreventioniste));
        $this->addChamp($segment->infosDoss, 'listeTelPreventioniste', implode(', ', $listeTelPreventioniste));
        $this->addChamp($segment->infosDoss, 'listeMailPreventioniste', implode(', ', $listeMailPreventionniste));

        // Avis dérogations
        $this->fillAvisDerogationsOnSubSegment($segment->infosDoss, $dossierInfos);

        // Formulaires personnalisés
        $this->fillFormulairesSegment($segment->infosDoss, $dossierInfos);
    }

    // ========================================================================
    // PRIVATE — Génération PV
    // ========================================================================

    /**
     * @param array<int, array<string, mixed>> $listeDossiers
     * @param array<int, array<string, mixed>> $commissionInfos
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
            $nbAdresse = isset($dossierInfos['infosEtab']['adresses']) ? count($dossierInfos['infosEtab']['adresses']) : 0;
            if (0 === $nbAdresse) {
                continue;
            }

            foreach ($dossierInfos['infosEtab']['adresses'] as $commune) {
                $this->addChamp($dossInfos, 'commissionNom', $commissionInfos[0]['LIBELLE_COMMISSION'] ?? '');

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
                if (isset($dossierInfos['DATEVISITE_DOSSIER']) && null !== $dossierInfos['DATEVISITE_DOSSIER']) {
                    $dateVisite = $this->parseDateSql($dossierInfos['DATEVISITE_DOSSIER']);
                } elseif (isset($dossierInfos['infosEtab']['last_visite']) && null !== $dossierInfos['infosEtab']['last_visite']) {
                    $dateVisite = $this->parseDateSql($dossierInfos['infosEtab']['last_visite']);
                }

                $this->addChamp($dossInfos, 'dateVisite', null !== $dateVisite ? DateFormatterFr::formatLong($dateVisite) : '');

                // Adresse
                $this->addChamp($dossInfos, 'libelleRue', $dossierInfos['infosEtab']['adresses'][0]['LIBELLE_RUE'] ?? '');
                $this->addChamp($dossInfos, 'codePostal', $dossierInfos['infosEtab']['adresses'][0]['CODEPOSTAL_COMMUNE'] ?? '');
                $this->addChamp($dossInfos, 'libelleCommune', $dossierInfos['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'] ?? '');

                // Effectif total
                $effectifPublic = (int) ($dossierInfos['infosEtab']['informations']['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0);
                $effectifPersonnel = (int) ($dossierInfos['infosEtab']['informations']['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0);
                $effectifTotal = $effectifPublic + $effectifPersonnel;
                $this->addChamp($dossInfos, 'effectifTotal', 0 === $effectifTotal ? 'non renseigné' : (string) $effectifTotal);

                // Type dossier
                $type = '';
                switch ((int) ($dossierInfos['TYPE_DOSSIER'] ?? 0)) {
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
                if (isset($dossierInfos['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
                    $this->addChamp($dossInfos, 'nomPereEtab', $dossierInfos['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS']);
                } else {
                    $this->addChamp($dossInfos, 'nomPereEtab', '');
                }

                if (isset($dossierInfos['infosEtab']['informations'])) {
                    $this->addChamp($dossInfos, 'nomEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
                    $this->addChamp($dossInfos, 'categorieEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_CATEGORIE'] ?? '');
                    $this->addChamp($dossInfos, 'typeEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_TYPE_PRINCIPAL'] ?? '');
                } else {
                    $this->addChamp($dossInfos, 'nomEtab', '');
                    $this->addChamp($dossInfos, 'categorieEtab', '');
                    $this->addChamp($dossInfos, 'typeEtab', '');
                }

                // Avis
                $this->addChamp($dossInfos, 'avisDossier', AvisCommission::getLibelle($dossierInfos['AVIS_DOSSIER'] ?? null));
                $this->addChamp($dossInfos, 'avisDossierCommission', AvisCommission::getLibelle($dossierInfos['AVIS_DOSSIER_COMMISSION'] ?? null));

                // Doc urba
                $listeDocUrba = '';
                if (!empty($dossierInfos['listeDocUrba'])) {
                    foreach ($dossierInfos['listeDocUrba'] as $docurb) {
                        $listeDocUrba .= $docurb['NUM_DOCURBA'].'    ';
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
     * @param array<int, array<string, mixed>> $commissionInfos
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
            $nbAdresse = isset($dossierInfos['infosEtab']['adresses']) ? count($dossierInfos['infosEtab']['adresses']) : 0;
            if (0 === $nbAdresse) {
                continue;
            }

            foreach ($dossierInfos['infosEtab']['adresses'] as $commune) {
                $this->addChamp($dossInfos, 'commissionNom', $commissionInfos[0]['LIBELLE_COMMISSION'] ?? '');

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
                if (isset($dossierInfos['DATEVISITE_DOSSIER']) && null !== $dossierInfos['DATEVISITE_DOSSIER']) {
                    $dateVisite = $this->parseDateSql($dossierInfos['DATEVISITE_DOSSIER']);
                } elseif (isset($dossierInfos['infosEtab']['last_visite']) && null !== $dossierInfos['infosEtab']['last_visite']) {
                    $dateVisite = $this->parseDateSql($dossierInfos['infosEtab']['last_visite']);
                }

                $this->addChamp($dossInfos, 'dateVisite', null !== $dateVisite ? DateFormatterFr::formatLong($dateVisite) : '');

                // Adresse
                $this->addChamp($dossInfos, 'libelleRue', $dossierInfos['infosEtab']['adresses'][0]['LIBELLE_RUE'] ?? '');
                $this->addChamp($dossInfos, 'codePostal', $dossierInfos['infosEtab']['adresses'][0]['CODEPOSTAL_COMMUNE'] ?? '');

                // numWinPrev
                try {
                    $dossInfos->setVars('numWinPrev', $dossierInfos['infosEtab']['general']['NUMEROID_ETABLISSEMENT'] ?? '', true, 'UTF-8');
                } catch (\Exception $e) {
                    // Silencieux
                }

                $this->addChamp($dossInfos, 'libelleCommune', $dossierInfos['infosEtab']['adresses'][0]['LIBELLE_COMMUNE'] ?? '');

                // Effectif total
                $effectifPublic = (int) ($dossierInfos['infosEtab']['informations']['EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS'] ?? 0);
                $effectifPersonnel = (int) ($dossierInfos['infosEtab']['informations']['EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS'] ?? 0);
                $effectifTotal = $effectifPublic + $effectifPersonnel;
                $this->addChamp($dossInfos, 'effectifTotal', 0 === $effectifTotal ? 'non renseigné' : (string) $effectifTotal);

                // Type dossier
                $type = '';
                switch ((int) ($dossierInfos['TYPE_DOSSIER'] ?? 0)) {
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
                if (isset($dossierInfos['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS'])) {
                    $this->addChamp($dossInfos, 'nomPereEtab', $dossierInfos['infosEtab']['parents'][0]['LIBELLE_ETABLISSEMENTINFORMATIONS']);
                } else {
                    $this->addChamp($dossInfos, 'nomPereEtab', '');
                }

                if (isset($dossierInfos['infosEtab']['informations'])) {
                    $this->addChamp($dossInfos, 'nomEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS'] ?? '');
                    $this->addChamp($dossInfos, 'categorieEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_CATEGORIE'] ?? '');
                    $this->addChamp($dossInfos, 'typeEtab', $dossierInfos['infosEtab']['informations']['LIBELLE_TYPE_PRINCIPAL'] ?? '');
                } else {
                    $this->addChamp($dossInfos, 'nomEtab', '');
                    $this->addChamp($dossInfos, 'categorieEtab', '');
                    $this->addChamp($dossInfos, 'typeEtab', '');
                }

                // Avis
                $this->addChamp($dossInfos, 'avisDossier', AvisCommission::getLibelle($dossierInfos['AVIS_DOSSIER'] ?? null));
                $this->addChamp($dossInfos, 'avisDossierCommission', AvisCommission::getLibelle($dossierInfos['AVIS_DOSSIER_COMMISSION'] ?? null));

                // Doc urba
                $listeDocUrba = '';
                if (!empty($dossierInfos['listeDocUrba'])) {
                    foreach ($dossierInfos['listeDocUrba'] as $docurb) {
                        $listeDocUrba .= $docurb['NUM_DOCURBA'].'    ';
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
     * Remplit le segment avisDerogations directement sur un segment parent.
     *
     * @param object               $segment
     * @param array<string, mixed> $dossierInfos
     */
    private function fillAvisDerogationsSegment($segment, array $dossierInfos): void
    {
        // Avis dérogations dossier
        $avisDerogations = null;
        try {
            $avisDerogations = $segment->avisDerogations;
        } catch (\Exception $exception) {
            // Segment absent du template
        }

        if (null !== $avisDerogations) {
            $this->fillDerogationSegmentData($avisDerogations, $dossierInfos['AVIS_DEROGATIONS'] ?? []);
        }

        // Avis dérogations établissement
        $avisDerogationsEtablissement = null;
        try {
            $avisDerogationsEtablissement = $segment->avisDerogationsEtablissement;
        } catch (\Exception $exception) {
            // Segment absent du template
        }

        if (null !== $avisDerogationsEtablissement) {
            $this->fillDerogationSegmentData($avisDerogationsEtablissement, $dossierInfos['AVIS_DEROGATIONS_ETABLISSEMENT'] ?? []);
        }
    }

    /**
     * Remplit les dérogations sur un sous-segment (infosDoss).
     *
     * @param object               $parentSegment
     * @param array<string, mixed> $dossierInfos
     */
    private function fillAvisDerogationsOnSubSegment($parentSegment, array $dossierInfos): void
    {
        // Avis dérogations dossier
        $avisDerogations = null;
        try {
            $avisDerogations = $parentSegment->avisDerogations;
        } catch (\Exception $exception) {
            // Segment absent du template
        }

        if (null !== $avisDerogations) {
            $this->fillDerogationSegmentData($avisDerogations, $dossierInfos['AVIS_DEROGATIONS'] ?? []);
        }

        // Avis dérogations établissement
        $avisDerogationsEtablissement = null;
        try {
            $avisDerogationsEtablissement = $parentSegment->avisDerogationsEtablissement;
        } catch (\Exception $exception) {
            // Segment absent du template
        }

        if (null !== $avisDerogationsEtablissement) {
            $this->fillDerogationSegmentData($avisDerogationsEtablissement, $dossierInfos['AVIS_DEROGATIONS_ETABLISSEMENT'] ?? []);
        }
    }

    /**
     * @param object                           $derogationSegment
     * @param array<int, array<string, mixed>> $derogations
     */
    private function fillDerogationSegmentData($derogationSegment, array $derogations): void
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
                $this->addChamp($derogationSegment, 'identifiantdossierleve', (string) ($avisDerogation['ID_DOSSIER_LIE'] ?? ''));

                $date = null;
                if (!empty($avisDerogation['DATECOMM_DOSSIER'])) {
                    $date = DateFormatterFr::formatShort($avisDerogation['DATECOMM_DOSSIER']);
                } elseif (!empty($avisDerogation['DATEVISITE_DOSSIER'])) {
                    $date = DateFormatterFr::formatShort($avisDerogation['DATEVISITE_DOSSIER']);
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
     * @param object               $odfSegment
     * @param array<string, mixed> $dossierInfos
     */
    private function fillPrescriptionsSegment($odfSegment, array $dossierInfos): void
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
            $listePrescription = $dossierInfos[$pType['key']] ?? [];
            $this->addPrescription($listePrescription, $pType['type'], $odfSegment, $pType['contenu']);
        }
    }

    /**
     * Port direct de addPrescription() (generationpv.phtml).
     *
     * @param array<int, array<int, array<string, string>>> $listePrescription
     * @param object                                        $odf
     * @param string                                        $typeContenu       'default', 'reprises', 'actuelles'
     */
    private function addPrescription(array $listePrescription, int $type, $odf, string $typeContenu): void
    {
        try {
            $segmentName = $this->getPrescriptionSegmentNameLegacy($type, $typeContenu);
            $prescription = null;

            try {
                $prescription = $odf->$segmentName;
            } catch (\Exception $e) {
                // Segment absent du template
            }

            if (null === $prescription) {
                return;
            }

            $nbPrescDossier = count($listePrescription);

            if ($nbPrescDossier > 0) {
                for ($i = 0; $i < $nbPrescDossier; ++$i) {
                    $nbAssoc = count($listePrescription[$i]);
                    $cpt = true;
                    $textearticle = '';
                    $nbCpt = $nbAssoc;

                    for ($j = 0; $j < $nbAssoc; ++$j) {
                        $articleT = $listePrescription[$i][$j]['LIBELLE_ARTICLE'] ?? '';
                        $texteT = $listePrescription[$i][$j]['LIBELLE_TEXTE'] ?? '';

                        if ('' !== $texteT) {
                            $textearticle .= $texteT.' - ';
                        } else {
                            $textearticle .= ' - ';
                        }

                        if ($nbCpt > 1) {
                            if ('' !== $articleT) {
                                $textearticle .= $articleT.', ';
                            } else {
                                $textearticle .= ', ';
                            }
                        } elseif ('' !== $articleT) {
                            $textearticle .= $articleT.' ';
                        } else {
                            $textearticle .= ' ';
                        }

                        if ($cpt) {
                            $this->addChamp($prescription, 'num', $listePrescription[$i][0]['NUM_PRESCRIPTION_DOSSIER'] ?? '');

                            if ('' === ($listePrescription[$i][0]['LIBELLE_PRESCRIPTION_DOSSIER'] ?? '')) {
                                $this->addChamp($prescription, 'libelle', $listePrescription[$i][0]['PRESCRIPTIONTYPE_LIBELLE'] ?? '');
                            } else {
                                $this->addChamp($prescription, 'libelle', $listePrescription[$i][0]['LIBELLE_PRESCRIPTION_DOSSIER']);
                            }

                            $cpt = false;
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
        } catch (\Exception $exception) {
            // OdfException silencieuse — comme le legacy
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
     * Remplit les formulaires personnalisés dans un segment ODT.
     *
     * Port direct de la logique formulaires des templates legacy.
     *
     * @param object               $segment
     * @param array<string, mixed> $dossierInfos
     */
    private function fillFormulairesSegment($segment, array $dossierInfos): void
    {
        if (!isset($dossierInfos['FORMULAIRES'])) {
            return;
        }

        foreach ($dossierInfos['FORMULAIRES'] as $formulaire) {
            if (!is_array($formulaire['RUBRIQUES'] ?? null)) {
                continue;
            }

            $nomInterne = $formulaire['NOM_INTERNE'] ?? '';

            foreach ($formulaire['RUBRIQUES'] as $rubrique) {
                $rubriqueNom = $rubrique['NOM'] ?? '';

                if (!isset($rubrique['CHAMPS'])) {
                    continue;
                }

                foreach ($rubrique['CHAMPS'] as $champ) {
                    if ('Parent' === ($champ['TYPE'] ?? '')) {
                        if (1 === (int) ($champ['tableau'] ?? 0)) {
                            // Tableau : entêtes
                            if (isset($champ['FILS']) && is_array($champ['FILS'])) {
                                foreach ($champ['FILS'] as $idx => $champFils) {
                                    if (is_int($idx)) {
                                        $this->addChamp(
                                            $segment,
                                            ChampNamer::getFullFusionName(
                                                $nomInterne,
                                                [$rubriqueNom, $champ['NOM'] ?? '', $champFils['idx'] ?? '']
                                            ),
                                            $champFils['NOM'] ?? ''
                                        );
                                    }
                                }

                                // Segment tableau valeurs
                                $nomFusion = ChampNamer::getLoopName(
                                    $nomInterne.' '.$rubriqueNom.' '.($champ['NOM'] ?? '').' valeurs'
                                );

                                $segmentTableau = null;
                                try {
                                    $segmentTableau = $segment->$nomFusion;
                                } catch (\Exception $e) {
                                    // Segment absent
                                }

                                if (null !== $segmentTableau) {
                                    $valeurs = $champ['FILS']['VALEURS'] ?? [];
                                    if (0 === count($valeurs)) {
                                        foreach (array_keys($champ['FILS']) as $index) {
                                            if (is_int($index)) {
                                                $this->addChamp($segmentTableau, sprintf('valeur%d', $index), '');
                                            }
                                        }

                                        $segmentTableau->merge();
                                    } else {
                                        foreach ($valeurs as $ligne) {
                                            $idxCol = 0;
                                            foreach ($ligne as $col) {
                                                $valCol = $col['VALEUR'] ?? '';
                                                if ('Case à cocher' === ($col['TYPE'] ?? '')) {
                                                    $valCol = boolval($valCol) ? 'Oui' : 'Non';
                                                } elseif ('Date' === ($col['TYPE'] ?? '')) {
                                                    $valCol = $this->formatDateFormulaire($valCol);
                                                }

                                                $this->addChamp($segmentTableau, sprintf('valeur%d', $idxCol), $valCol);
                                                ++$idxCol;
                                            }

                                            $segmentTableau->merge();
                                        }
                                    }
                                }
                            }
                        } elseif (isset($champ['FILS']) && is_array($champ['FILS'])) {
                            // Parent non-tableau : champs fils simples
                            foreach ($champ['FILS'] as $champFils) {
                                if (!is_array($champFils)) {
                                    continue;
                                }

                                $valeurFils = $champFils['VALEUR'] ?? '';
                                if ('Case à cocher' === ($champFils['TYPE'] ?? '')) {
                                    $valeurFils = boolval($valeurFils) ? 'Oui' : 'Non';
                                } elseif ('Date' === ($champFils['TYPE'] ?? '')) {
                                    $valeurFils = $this->formatDateFormulaire($valeurFils);
                                }

                                $this->addChamp(
                                    $segment,
                                    ChampNamer::getFullFusionName(
                                        $nomInterne,
                                        [$rubriqueNom, $champ['NOM'] ?? '', $champFils['NOM'] ?? '']
                                    ),
                                    $valeurFils
                                );
                            }
                        }
                    } else {
                        // Champ simple
                        $valeur = $champ['VALEUR'] ?? '';
                        if ('Case à cocher' === ($champ['TYPE'] ?? '')) {
                            $valeur = boolval($valeur) ? 'Oui' : 'Non';
                        } elseif ('Date' === ($champ['TYPE'] ?? '')) {
                            $valeur = $this->formatDateFormulaire($valeur);
                        }

                        $this->addChamp(
                            $segment,
                            ChampNamer::getFullFusionName(
                                $nomInterne,
                                [$rubriqueNom, $champ['NOM'] ?? '']
                            ),
                            $valeur
                        );
                    }
                }
            }
        }
    }

    // ========================================================================
    // PRIVATE — Utilitaires
    // ========================================================================

    /**
     * Ajoute un champ à un segment ODT avec gestion d'erreur silencieuse.
     *
     * Port direct de setVars() / addChamp() du legacy.
     *
     * @param object $odf      Odf ou Segment
     * @param string $nomOdf   Nom du champ dans le template
     * @param mixed  $variable Valeur à injecter (sera convertie en string)
     */
    private function addChamp($odf, string $nomOdf, $variable): void
    {
        try {
            $value = (null === $variable) ? '' : (string) $variable;
            $odf->setVars($nomOdf, $value, true, 'UTF8'); // @phpstan-ignore-line
        } catch (\Exception $exception) {
            // Silencieux — variable absente du template ODT
        }
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
        $this->em->flush();

        // Lier à la dateCommission
        /** @var DateCommission|null $dateCommission */
        $dateCommission = $this->em->find(DateCommission::class, $dateCommId);
        if (null !== $dateCommission) {
            $dateCommission->addPiecesJointe($pj);
            $this->em->flush();
        }

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
     * Formate une date de formulaire (dd/MM/yyyy) en format long français.
     *
     * @param string|null $value
     */
    private function formatDateFormulaire($value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        $dt = \DateTime::createFromFormat('d/m/Y', $value);
        if (!$dt instanceof \DateTime) {
            return (string) $value;
        }

        return DateFormatterFr::formatLong($dt);
    }
}

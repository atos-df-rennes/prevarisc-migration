<?php

namespace App\Service;

use App\Dto\Repository\DossierSearchResultDto;
use App\Service\Search\SearchQueryHelper;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Recherche et export des dossiers.
 * Extrait de Search (refactoring REF-SVC-01).
 */
final readonly class DossierSearchService
{
    public const int MAX_LIMIT_PAGES_DOSSIERS = 100;

    public function __construct(private Connection $connection, private SearchQueryHelper $queryHelper, private TagAwareCacheInterface $searchCache)
    {
    }

    /**
     * Retourne la liste des préventionnistes actifs sur au moins un dossier.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listePrevActifs(): array
    {
        $sql = 'SELECT DISTINCT ui.NOM_UTILISATEURINFORMATIONS, ui.PRENOM_UTILISATEURINFORMATIONS,
                    dossierpreventionniste.ID_PREVENTIONNISTE
                FROM utilisateurinformations ui
                INNER JOIN utilisateur ON ui.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS
                INNER JOIN dossierpreventionniste ON dossierpreventionniste.ID_PREVENTIONNISTE = utilisateur.ID_UTILISATEUR
                ORDER BY NOM_UTILISATEURINFORMATIONS';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

        return $rows;
    }

    /**
     * Recherche des dossiers.
     *
     * @param array<int|string, int|string>|null $types
     * @param array<string, mixed>|null          $criterias
     *
     * @return array{results: array<string, DossierSearchResultDto>, search_metadata: array{count: int, current_page: int}}
     */
    public function dossiers(
        ?array $types,
        ?string $objet,
        ?string $numDocUrba,
        ?int $parent,
        ?bool $avisDiffere,
        int $count = 50,
        int $page = 1,
        ?array $criterias = null
    ): array {
        $searchId = 'search_dossiers_'.md5(serialize(func_get_args()));

        /** @var array{results: array<string, DossierSearchResultDto>, search_metadata: array{count: int, current_page: int}} $cached */
        $cached = $this->searchCache->get($searchId, function (ItemInterface $item) use (
            $types, $objet, $numDocUrba, $parent, $avisDiffere, $count, $page, $criterias
        ): array {
            $item->tag(['search']);

            $effectiveCount = $count > self::MAX_LIMIT_PAGES_DOSSIERS
                ? self::MAX_LIMIT_PAGES_DOSSIERS
                : $count;

            $sqlSelect = 'SELECT d.*,
            ( SELECT group_concat(dossierdocurba2.NUM_DOCURBA, \', \')
                FROM dossier d3
                INNER JOIN dossierdocurba dossierdocurba2 ON dossierdocurba2.ID_DOSSIER = d3.ID_DOSSIER
                WHERE d3.ID_DOSSIER = d.ID_DOSSIER
                LIMIT 1
            ) AS NB_URBA,
            (SELECT COUNT(dossierlie3.ID_DOSSIER2)
                FROM dossier d4
                INNER JOIN dossierlie dossierlie3 ON d4.ID_DOSSIER = dossierlie3.ID_DOSSIER1
                INNER JOIN dossiernature dossiernature3 ON dossierlie3.ID_DOSSIER1 = dossiernature3.ID_DOSSIER
                WHERE (dossiernature3.ID_NATURE = 2 OR dossiernature3.ID_NATURE = 1 OR dossiernature3.ID_NATURE = 13 OR dossiernature3.ID_NATURE = 12)
                AND d4.ID_DOSSIER = d.ID_DOSSIER
            ) AS ALERTE_RECEPTION_TRAVAUX,
            (SELECT COUNT(dossierlie4.ID_DOSSIER1)
                FROM dossier d5
                INNER JOIN dossierlie dossierlie4 ON d5.ID_DOSSIER = dossierlie4.ID_DOSSIER2
                INNER JOIN dossiernature dossiernature4 ON dossierlie4.ID_DOSSIER1 = dossiernature4.ID_DOSSIER
                WHERE dossiernature4.ID_NATURE = 46 AND d5.ID_DOSSIER = d.ID_DOSSIER
            ) AS ECHEANCIER_TRAVAUX,
            dossiernatureliste.LIBELLE_DOSSIERNATURE, dossiernatureliste.ID_DOSSIERNATURE,
            dossiertype.LIBELLE_DOSSIERTYPE,
            eia.LIBELLE_ETABLISSEMENTINFORMATIONS, eia.ID_ETABLISSEMENT, eia.ID_CATEGORIE,
            type.ID_TYPE, type.LIBELLE_TYPE,
            categorie.LIBELLE_CATEGORIE,
            genre.LIBELLE_GENRE,
            avis.ID_AVIS,
            adressecommune.CODEPOSTAL_COMMUNE, adressecommune.LIBELLE_COMMUNE,
            platauconsultation.DATE_REPONSE_ATTENDUE';

            $sqlFrom = ' FROM dossier d
            LEFT JOIN dossierlie ON d.ID_DOSSIER = dossierlie.ID_DOSSIER2
            INNER JOIN dossiernature ON dossiernature.ID_DOSSIER = d.ID_DOSSIER
            INNER JOIN dossiernatureliste ON dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE
            INNER JOIN dossiertype ON dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE
            LEFT JOIN etablissementdossier e ON d.ID_DOSSIER = e.ID_DOSSIER
            LEFT JOIN etablissementinformationsactuel eia ON e.ID_ETABLISSEMENT = eia.ID_ETABLISSEMENT
            LEFT JOIN type ON type.ID_TYPE = eia.ID_TYPE
            LEFT JOIN categorie ON categorie.ID_CATEGORIE = eia.ID_CATEGORIE
            LEFT JOIN genre ON genre.ID_GENRE = eia.ID_GENRE
            LEFT JOIN avis ON d.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS
            LEFT JOIN dossierdocurba ON dossierdocurba.ID_DOSSIER = d.ID_DOSSIER
            LEFT JOIN etablissementadresse ea ON ea.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
            LEFT JOIN adressecommune ON ea.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
            LEFT JOIN platauconsultation ON d.ID_PLATAU = platauconsultation.ID_PLATAU';

            $whereConditions = ['d.DATESUPPRESSION_DOSSIER IS NULL'];
            $havingConditions = [];
            $hasAdditionalConditions = false;

            // JOINs conditionnels
            $commissionsJoin = '';
            $preventionnisteJoin = '';
            $groupementJoin = '';

            // Flags COUNT lean : JOINs supplémentaires nécessaires uniquement pour certains filtres
            $needsAdresseCommune = false;
            $needsDocUrba = false;
            /** @var string[] $countWhereFromHaving */
            $countWhereFromHaving = [];

            // Critères : numéro de doc urba
            if (null !== $numDocUrba) {
                $nbUrbaPattern = $this->queryHelper->safeQuote('%'.$numDocUrba.'%');
                $havingConditions[] = 'NB_URBA LIKE '.$nbUrbaPattern;
                $countWhereFromHaving[] = 'EXISTS (SELECT 1 FROM dossierdocurba dc_urba WHERE dc_urba.ID_DOSSIER = d.ID_DOSSIER AND dc_urba.NUM_DOCURBA LIKE '.$nbUrbaPattern.')';
            }

            // Critères : objet
            if (null !== $objet) {
                $cleanObjet = trim($objet);
                if (str_starts_with($cleanObjet, '#')) {
                    $nbUrbaPattern = $this->queryHelper->safeQuote('%'.substr($cleanObjet, 1).'%');
                    $havingConditions[] = 'NB_URBA LIKE '.$nbUrbaPattern;
                    $countWhereFromHaving[] = 'EXISTS (SELECT 1 FROM dossierdocurba dc_urba WHERE dc_urba.ID_DOSSIER = d.ID_DOSSIER AND dc_urba.NUM_DOCURBA LIKE '.$nbUrbaPattern.')';
                } elseif (1 === preg_match('/^[0-9A-Z\.]+$/', $cleanObjet)) {
                    $nbUrbaPattern = $this->queryHelper->safeQuote('%'.$cleanObjet.'%');
                    $havingConditions[] = 'NB_URBA LIKE '.$nbUrbaPattern;
                    $countWhereFromHaving[] = 'EXISTS (SELECT 1 FROM dossierdocurba dc_urba WHERE dc_urba.ID_DOSSIER = d.ID_DOSSIER AND dc_urba.NUM_DOCURBA LIKE '.$nbUrbaPattern.')';
                } elseif (1 === preg_match('/^[A-Z0-9]{3}-[A-Z0-9]{3}-[A-Z0-9]{3}$/', $cleanObjet)) {
                    $whereConditions[] = 'd.ID_PLATAU='.$this->queryHelper->safeQuote($cleanObjet);
                    $hasAdditionalConditions = true;
                } else {
                    $whereConditions[] = $this->queryHelper->buildCriteria('OBJET_DOSSIER', $cleanObjet, false);
                    $hasAdditionalConditions = true;
                }
            }

            // Critères : parent
            if (null !== $parent) {
                if (0 === $parent) {
                    $whereConditions[] = 'dossierlie.ID_DOSSIER1 IS NULL';
                } else {
                    $whereConditions[] = 'dossierlie.ID_DOSSIER1='.$this->queryHelper->safeQuote((string) $parent);
                }

                $hasAdditionalConditions = true;
            }

            // Critères : type
            if (null !== $types) {
                $whereConditions[] = $this->queryHelper->buildCriteria('dossiertype.ID_DOSSIERTYPE', $types);
                $hasAdditionalConditions = true;
            }

            // Critères : avis différé
            if (null !== $avisDiffere) {
                $whereConditions[] = $this->queryHelper->buildCriteria('d.DIFFEREAVIS_DOSSIER', $avisDiffere);
                $hasAdditionalConditions = true;
            }

            // Critères issus du tableau criterias
            if (null !== $criterias) {
                // Critères : commissions
                if (isset($criterias['commissions'])) {
                    $commissionsJoin = '
                    LEFT JOIN dossieraffectation ON dossieraffectation.ID_DOSSIER_AFFECT = d.ID_DOSSIER
                    LEFT JOIN datecommission ON datecommission.ID_DATECOMMISSION = dossieraffectation.ID_DATECOMMISSION_AFFECT';
                    /** @var array<int|string, int|string>|int|string $commissionsValue */
                    $commissionsValue = $criterias['commissions'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('datecommission.COMMISSION_CONCERNE', $commissionsValue);
                    $hasAdditionalConditions = true;
                }

                // Critères : avis commission
                if (isset($criterias['avisCommission'])) {
                    /** @var array<int|string, int|string>|int|string $avisCommissionValue */
                    $avisCommissionValue = $criterias['avisCommission'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('d.AVIS_DOSSIER_COMMISSION', $avisCommissionValue);
                    $hasAdditionalConditions = true;
                }

                // Critères : avis rapporteur
                if (isset($criterias['avisRapporteur'])) {
                    /** @var array<int|string, int|string>|int|string $avisRapporteurValue */
                    $avisRapporteurValue = $criterias['avisRapporteur'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('d.AVIS_DOSSIER', $avisRapporteurValue);
                    $hasAdditionalConditions = true;
                }

                // Critères : avis différé (depuis criterias)
                if (isset($criterias['avisDiffere'])) {
                    /** @var array<int|string, int|string>|bool|int|string $avisDiffereValue */
                    $avisDiffereValue = $criterias['avisDiffere'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('d.DIFFEREAVIS_DOSSIER', $avisDiffereValue);
                    $hasAdditionalConditions = true;
                }

                // Critères : permis
                if (isset($criterias['permis'])) {
                    /** @var array<int|string, int|string>|int|string $permisValue */
                    $permisValue = $criterias['permis'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('dossierdocurba.NUM_DOCURBA', $permisValue);
                    $hasAdditionalConditions = true;
                    $needsDocUrba = true;
                }

                // Critères : courrier
                if (isset($criterias['courrier'])) {
                    /** @var array<int|string, int|string>|int|string $courrierValue */
                    $courrierValue = $criterias['courrier'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('d.REFCOURRIER_DOSSIER', $courrierValue, false);
                    $hasAdditionalConditions = true;
                }

                // Critères : preventionniste
                if (isset($criterias['preventionniste'])) {
                    $preventionnisteJoin = '
                    LEFT JOIN dossierpreventionniste ON dossierpreventionniste.ID_DOSSIER = d.ID_DOSSIER';
                    /** @var array<int|string, int|string>|int|string $preventionnisteValue */
                    $preventionnisteValue = $criterias['preventionniste'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('dossierpreventionniste.ID_PREVENTIONNISTE', $preventionnisteValue);
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['commune'])) {
                    /** @var array<int|string, int|string>|int|string $communeValue */
                    $communeValue = $criterias['commune'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('ea.NUMINSEE_COMMUNE', $communeValue);
                    $hasAdditionalConditions = true;
                    $needsAdresseCommune = true;
                }

                if (isset($criterias['voie'])) {
                    /** @var array<int|string, int|string>|int|string $voieValue */
                    $voieValue = $criterias['voie'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('ea.ID_RUE', $voieValue);
                    $hasAdditionalConditions = true;
                    $needsAdresseCommune = true;
                }

                if (isset($criterias['numero'])) {
                    /** @var array<int|string, int|string>|int|string $numeroValue */
                    $numeroValue = $criterias['numero'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('ea.NUMERO_ADRESSE', $numeroValue);
                    $hasAdditionalConditions = true;
                    $needsAdresseCommune = true;
                }

                // Critère : groupement territorial
                if (isset($criterias['groupements_territoriaux'])) {
                    $groupementJoin = '
                    LEFT JOIN groupementcommune ON groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
                    LEFT JOIN groupement ON groupement.ID_GROUPEMENT = groupementcommune.ID_GROUPEMENT';
                    /** @var array<int|string, int|string>|int|string $groupementValue */
                    $groupementValue = $criterias['groupements_territoriaux'];
                    $whereConditions[] = $this->queryHelper->buildCriteria('groupement.ID_GROUPEMENT', $groupementValue);
                    $hasAdditionalConditions = true;
                    $needsAdresseCommune = true;
                }

                if (isset($criterias['dateCreationStart']) && is_string($criterias['dateCreationStart'])) {
                    $whereConditions[] = 'd.DATEINSERT_DOSSIER >= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateCreationStart']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['dateCreationEnd']) && is_string($criterias['dateCreationEnd'])) {
                    $whereConditions[] = 'd.DATEINSERT_DOSSIER <= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateCreationEnd']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['dateReceptionStart']) && is_string($criterias['dateReceptionStart'])) {
                    $whereConditions[] = 'd.DATESDIS_DOSSIER >= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateReceptionStart']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['dateReceptionEnd']) && is_string($criterias['dateReceptionEnd'])) {
                    $whereConditions[] = 'd.DATESDIS_DOSSIER <= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateReceptionEnd']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['dateReponseStart']) && is_string($criterias['dateReponseStart'])) {
                    $whereConditions[] = 'd.DATEREP_DOSSIER >= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateReponseStart']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['dateReponseEnd']) && is_string($criterias['dateReponseEnd'])) {
                    $whereConditions[] = 'd.DATEREP_DOSSIER <= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateReponseEnd']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['dateCommissionStart']) && is_string($criterias['dateCommissionStart'])) {
                    $whereConditions[] = "STR_TO_DATE(d.DATECOMM_DOSSIER, '%Y-%m-%d') >= STR_TO_DATE(".$this->queryHelper->safeQuote($criterias['dateCommissionStart']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['dateCommissionEnd']) && is_string($criterias['dateCommissionEnd'])) {
                    $whereConditions[] = "STR_TO_DATE(d.DATECOMM_DOSSIER, '%Y-%m-%d') <= STR_TO_DATE(".$this->queryHelper->safeQuote($criterias['dateCommissionEnd']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['dateVisiteStart']) && is_string($criterias['dateVisiteStart'])) {
                    $whereConditions[] = 'd.DATEVISITE_DOSSIER >= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateVisiteStart']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                if (isset($criterias['dateVisiteEnd']) && is_string($criterias['dateVisiteEnd'])) {
                    $whereConditions[] = 'd.DATEVISITE_DOSSIER <= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateVisiteEnd']).", '%d/%m/%Y')";
                    $hasAdditionalConditions = true;
                }

                $provenanceValue = isset($criterias['provenance']) && is_string($criterias['provenance']) ? $criterias['provenance'] : null;
                if (null !== $provenanceValue) {
                    if ('1' === $provenanceValue) {
                        $whereConditions[] = 'd.ID_PLATAU IS NOT NULL';
                    } elseif ('2' === $provenanceValue) {
                        $whereConditions[] = 'd.ID_PLATAU IS NULL';
                    }

                    $hasAdditionalConditions = true;
                }
            }

            $sqlFull = $sqlSelect
                .$sqlFrom
                .$commissionsJoin
                .$preventionnisteJoin
                .$groupementJoin
                .' WHERE '.implode(' AND ', $whereConditions)
                .' GROUP BY d.ID_DOSSIER';

            if (!empty($havingConditions)) {
                $sqlFull .= ' HAVING '.implode(' AND ', $havingConditions);
            }

            // Performance optimisation : tri uniquement si critères supplémentaires
            if ($hasAdditionalConditions || !empty($havingConditions)) {
                $sqlFull .= ' ORDER BY d.DATEINSERT_DOSSIER DESC';
            }

            // COUNT lean : sans les sous-requêtes corrélées d'affichage
            // (NB_URBA, ALERTE_RECEPTION_TRAVAUX, ECHEANCIER_TRAVAUX)
            // ni les JOINs d'affichage uniquement (eia, type, genre, avis, platauconsultation).
            // Les conditions HAVING sur NB_URBA sont converties en WHERE EXISTS pour ne pas dépendre de la sous-requête SELECT.
            // Gain : évite ~3×N sous-requêtes corrélées sur la totalité des lignes.
            $sqlFromCountBase = ' FROM dossier d
            LEFT JOIN dossierlie ON d.ID_DOSSIER = dossierlie.ID_DOSSIER2
            INNER JOIN dossiernature ON dossiernature.ID_DOSSIER = d.ID_DOSSIER
            INNER JOIN dossiernatureliste ON dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE
            INNER JOIN dossiertype ON dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE';
            $sqlFromCountAdresse = '
            LEFT JOIN etablissementdossier e ON d.ID_DOSSIER = e.ID_DOSSIER
            LEFT JOIN etablissementadresse ea ON ea.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
            LEFT JOIN adressecommune ON ea.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE';
            $sqlFromCountDocUrba = '
            LEFT JOIN dossierdocurba ON dossierdocurba.ID_DOSSIER = d.ID_DOSSIER';

            $sqlFromCount = $sqlFromCountBase
                .($needsAdresseCommune ? $sqlFromCountAdresse : '')
                .($needsDocUrba ? $sqlFromCountDocUrba : '')
                .$commissionsJoin
                .$preventionnisteJoin
                .$groupementJoin;

            $countWhereAllConditions = array_merge($whereConditions, $countWhereFromHaving);
            $countSql = 'SELECT COUNT(DISTINCT d.ID_DOSSIER)'
                .$sqlFromCount
                .' WHERE '.implode(' AND ', $countWhereAllConditions);
            $totalCountRaw = $this->connection->executeQuery($countSql)->fetchOne();
            $totalCount = is_numeric($totalCountRaw) ? (int) $totalCountRaw : 0;

            // Requête paginée
            $offset = ($page - 1) * $effectiveCount;
            $paginatedSql = $sqlFull.' LIMIT '.$effectiveCount.' OFFSET '.$offset;

            /** @var array<int, array<string, mixed>> $rows */
            $rows = $this->connection->executeQuery($paginatedSql)->fetchAllAssociative();

            // Index par ID_DOSSIER (comme le legacy), mappé vers DTO
            /** @var array<string, DossierSearchResultDto> $newResults */
            $newResults = [];
            foreach ($rows as $row) {
                $dossierIdValue = $row['ID_DOSSIER'];
                $dossierIdStr = is_int($dossierIdValue) || is_string($dossierIdValue) ? (string) $dossierIdValue : '';
                if ('' !== $dossierIdStr) {
                    $newResults[$dossierIdStr] = DossierSearchResultDto::fromArray($row);
                }
            }

            /** @var string[] $sIDsTable */
            $sIDsTable = array_keys($newResults);

            // Post-traitement batch : préventionnistes et PJ sur les IDs résultats
            if ([] !== $sIDsTable) {
                // Préventionnistes
                $prevSql = 'SELECT u.ID_UTILISATEUR, ui.PRENOM_UTILISATEURINFORMATIONS, ui.NOM_UTILISATEURINFORMATIONS,
                    dp.ID_DOSSIER
                FROM utilisateur u
                INNER JOIN utilisateurinformations ui ON u.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
                INNER JOIN dossierpreventionniste dp ON dp.ID_PREVENTIONNISTE = u.ID_UTILISATEUR
                WHERE dp.ID_DOSSIER IN (?)';

                /** @var array<int, array<string, mixed>> $preventionnistes */
                $preventionnistes = $this->connection->executeQuery(
                    $prevSql,
                    [$sIDsTable],
                    [Connection::PARAM_STR_ARRAY]
                )->fetchAllAssociative();

                foreach ($preventionnistes as $prev) {
                    $prevDossId = $prev['ID_DOSSIER'];
                    if (null !== $prevDossId && (is_string($prevDossId) || is_int($prevDossId))) {
                        $dossId = (string) $prevDossId;
                        if (isset($newResults[$dossId])) {
                            $existingPrevList = \is_array($newResults[$dossId]->PREVENTIONNISTES)
                                ? $newResults[$dossId]->PREVENTIONNISTES
                                : [];
                            $existingPrevList[] = $prev;
                            $newResults[$dossId]->PREVENTIONNISTES = $existingPrevList;
                        }
                    }
                }

                // Pièces jointes
                $pjSql = 'SELECT pj.NOM_PIECEJOINTE, pj.EXTENSION_PIECEJOINTE, dpj.ID_DOSSIER
                FROM piecejointe pj
                INNER JOIN dossierpj dpj ON dpj.ID_PIECEJOINTE = pj.ID_PIECEJOINTE
                WHERE dpj.ID_DOSSIER IN (?)';

                /** @var array<int, array<string, mixed>> $piecesjointes */
                $piecesjointes = $this->connection->executeQuery(
                    $pjSql,
                    [$sIDsTable],
                    [Connection::PARAM_STR_ARRAY]
                )->fetchAllAssociative();

                foreach ($piecesjointes as $pj) {
                    $pjDossId = $pj['ID_DOSSIER'];
                    if (null !== $pjDossId && (is_string($pjDossId) || is_int($pjDossId))) {
                        $dossId = (string) $pjDossId;
                        if (isset($newResults[$dossId])) {
                            $existingPjList = \is_array($newResults[$dossId]->PIECESJOINTES)
                                ? $newResults[$dossId]->PIECESJOINTES
                                : [];
                            $existingPjList[] = $pj;
                            $newResults[$dossId]->PIECESJOINTES = $existingPjList;
                        }
                    }
                }
            }

            return [
                'results' => $newResults,
                'search_metadata' => [
                    'count' => $totalCount,
                    'current_page' => $page,
                ],
            ];
        });

        return $cached;
    }

    /**
     * Extraction de tous les dossiers pour export calc.
     * Port direct de Service_Search::extractionDossiers() (legacy).
     * Pas de pagination — retourne tous les résultats.
     *
     * @param array<int|string, int|string>|null $types
     * @param array<string, mixed>|null          $criterias
     *
     * @return array{results: array<int, array<string, string|null>>, search_metadata: array{count: int}}
     */
    public function extractionDossiers(
        ?array $types,
        ?string $objet,
        ?string $numDocUrba,
        ?int $parent,
        ?bool $avisDiffere,
        ?array $criterias = null
    ): array {
        $sqlSelect = 'SELECT d.*,
            (SELECT COUNT(dossierlie2.ID_DOSSIER2)
                FROM dossier d2
                INNER JOIN dossierlie dossierlie2 ON d2.ID_DOSSIER = dossierlie2.ID_DOSSIER1
                WHERE d2.ID_DOSSIER = d.ID_DOSSIER
            ) AS NB_DOSS_LIES,
            ( SELECT group_concat(dossierdocurba2.NUM_DOCURBA, \', \')
                FROM dossier d3
                INNER JOIN dossierdocurba dossierdocurba2 ON dossierdocurba2.ID_DOSSIER = d3.ID_DOSSIER
                WHERE d3.ID_DOSSIER = d.ID_DOSSIER
                LIMIT 1
            ) AS NB_URBA,
            (SELECT COUNT(dossierlie3.ID_DOSSIER2)
                FROM dossier d4
                INNER JOIN dossierlie dossierlie3 ON d4.ID_DOSSIER = dossierlie3.ID_DOSSIER1
                INNER JOIN dossiernature dossiernature3 ON dossierlie3.ID_DOSSIER1 = dossiernature3.ID_DOSSIER
                WHERE (dossiernature3.ID_NATURE = 2 OR dossiernature3.ID_NATURE = 1 OR dossiernature3.ID_NATURE = 13 OR dossiernature3.ID_NATURE = 12)
                AND d4.ID_DOSSIER = d.ID_DOSSIER
            ) AS ALERTE_RECEPTION_TRAVAUX,
            (SELECT COUNT(dossierlie4.ID_DOSSIER1)
                FROM dossier d5
                INNER JOIN dossierlie dossierlie4 ON d5.ID_DOSSIER = dossierlie4.ID_DOSSIER2
                INNER JOIN dossiernature dossiernature4 ON dossierlie4.ID_DOSSIER1 = dossiernature4.ID_DOSSIER
                WHERE dossiernature4.ID_NATURE = 46 AND d5.ID_DOSSIER = d.ID_DOSSIER
            ) AS ECHEANCIER_TRAVAUX,
            dossiernatureliste.LIBELLE_DOSSIERNATURE, dossiernatureliste.ID_DOSSIERNATURE,
            dossiertype.LIBELLE_DOSSIERTYPE,
            commission.LIBELLE_COMMISSION,
            ei.LIBELLE_ETABLISSEMENTINFORMATIONS, ei.ID_ETABLISSEMENT, ei.ID_CATEGORIE,
            e.NUMEROID_ETABLISSEMENT,
            categorie.LIBELLE_CATEGORIE,
            type.ID_TYPE, type.LIBELLE_TYPE AS LIBELLE_TYPE_ETABLISSEMENT,
            typeactivite.LIBELLE_ACTIVITE,
            statut.LIBELLE_STATUT,
            genre.LIBELLE_GENRE,
            ar.LIBELLE_AVIS AS LIBELLE_AVIS_RAPPORTEUR,
            ac.LIBELLE_AVIS AS LIBELLE_AVIS_COMMISSION,
            dossierdocurba.NUM_DOCURBA,
            utilisateurinformations.NOM_UTILISATEURINFORMATIONS, utilisateurinformations.PRENOM_UTILISATEURINFORMATIONS,
            adressecommune.CODEPOSTAL_COMMUNE, adressecommune.LIBELLE_COMMUNE,
            groupement.LIBELLE_GROUPEMENT,
            dossierpj.ID_PIECEJOINTE';

        $sqlFrom = ' FROM dossier d
            LEFT JOIN dossierlie ON d.ID_DOSSIER = dossierlie.ID_DOSSIER2
            LEFT JOIN commission ON d.COMMISSION_DOSSIER = commission.ID_COMMISSION
            INNER JOIN dossiernature ON dossiernature.ID_DOSSIER = d.ID_DOSSIER
            INNER JOIN dossiernatureliste ON dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE
            INNER JOIN dossiertype ON dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE
            INNER JOIN etablissementdossier ed ON d.ID_DOSSIER = ed.ID_DOSSIER
            INNER JOIN etablissement e ON ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
            INNER JOIN etablissementinformations ei ON e.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(etablissementinformations2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations etablissementinformations2
                    WHERE etablissementinformations2.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                )
            LEFT JOIN categorie ON categorie.ID_CATEGORIE = ei.ID_CATEGORIE
            LEFT JOIN type ON type.ID_TYPE = ei.ID_TYPE
            LEFT JOIN typeactivite ON ei.ID_TYPEACTIVITE = typeactivite.ID_TYPEACTIVITE
            LEFT JOIN statut ON ei.ID_STATUT = statut.ID_STATUT
            INNER JOIN genre ON genre.ID_GENRE = ei.ID_GENRE
            LEFT JOIN avis ar ON d.AVIS_DOSSIER = ar.ID_AVIS
            LEFT JOIN avis ac ON d.AVIS_DOSSIER_COMMISSION = ac.ID_AVIS
            LEFT JOIN dossierdocurba ON dossierdocurba.ID_DOSSIER = d.ID_DOSSIER
            LEFT JOIN dossieraffectation ON dossieraffectation.ID_DOSSIER_AFFECT = d.ID_DOSSIER
            LEFT JOIN datecommission ON datecommission.ID_DATECOMMISSION = dossieraffectation.ID_DATECOMMISSION_AFFECT
            LEFT JOIN dossierpreventionniste ON dossierpreventionniste.ID_DOSSIER = d.ID_DOSSIER
            LEFT JOIN utilisateur ON dossierpreventionniste.ID_PREVENTIONNISTE = utilisateur.ID_UTILISATEUR
            LEFT JOIN utilisateurinformations ON utilisateurinformations.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS
            LEFT JOIN etablissementadresse ea ON ea.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
            LEFT JOIN adressecommune ON ea.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
            LEFT JOIN groupementcommune ON groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
            LEFT JOIN groupement ON groupement.ID_GROUPEMENT = groupementcommune.ID_GROUPEMENT
            LEFT JOIN dossierpj ON dossierpj.ID_DOSSIER = d.ID_DOSSIER';

        $whereConditions = ['d.DATESUPPRESSION_DOSSIER IS NULL'];
        $havingConditions = [];

        // Critères : numéro de doc urba
        if (null !== $numDocUrba) {
            $havingConditions[] = 'NB_URBA LIKE '.$this->queryHelper->safeQuote('%'.$numDocUrba.'%');
        }

        // Critères : objet
        if (null !== $objet) {
            $cleanObjet = trim($objet);
            if (str_starts_with($cleanObjet, '#')) {
                $havingConditions[] = 'NB_URBA LIKE '.$this->queryHelper->safeQuote('%'.substr($cleanObjet, 1).'%');
            } elseif (1 === preg_match('/^[0-9A-Z\.]+$/', $cleanObjet)) {
                $havingConditions[] = 'NB_URBA LIKE '.$this->queryHelper->safeQuote('%'.$cleanObjet.'%');
            } elseif (1 === preg_match('/^[A-Z0-9]{3}-[A-Z0-9]{3}-[A-Z0-9]{3}$/', $cleanObjet)) {
                $whereConditions[] = 'd.ID_PLATAU='.$this->queryHelper->safeQuote($cleanObjet);
            } else {
                $whereConditions[] = $this->queryHelper->buildCriteria('OBJET_DOSSIER', $cleanObjet, false);
            }
        }

        // Critères : parent
        if (null !== $parent) {
            if (0 === $parent) {
                $whereConditions[] = 'dossierlie.ID_DOSSIER1 IS NULL';
            } else {
                $whereConditions[] = 'dossierlie.ID_DOSSIER1='.$this->queryHelper->safeQuote((string) $parent);
            }
        }

        // Critères : type
        if (null !== $types) {
            $whereConditions[] = $this->queryHelper->buildCriteria('dossiertype.ID_DOSSIERTYPE', $types);
        }

        // Critères : avis différé
        if (null !== $avisDiffere) {
            $whereConditions[] = $this->queryHelper->buildCriteria('d.DIFFEREAVIS_DOSSIER', $avisDiffere);
        }

        // Critères issus du tableau criterias
        if (null !== $criterias) {
            // Critères : commissions
            if (isset($criterias['commissions'])) {
                /** @var array<int|string, int|string>|int|string $commissionsValue */
                $commissionsValue = $criterias['commissions'];
                $whereConditions[] = $this->queryHelper->buildCriteria('datecommission.COMMISSION_CONCERNE', $commissionsValue);
            }

            // Critères : avis commission
            if (isset($criterias['avisCommission'])) {
                /** @var array<int|string, int|string>|int|string $avisCommissionValue */
                $avisCommissionValue = $criterias['avisCommission'];
                $whereConditions[] = $this->queryHelper->buildCriteria('d.AVIS_DOSSIER_COMMISSION', $avisCommissionValue);
            }

            // Critères : avis rapporteur
            if (isset($criterias['avisRapporteur'])) {
                /** @var array<int|string, int|string>|int|string $avisRapporteurValue */
                $avisRapporteurValue = $criterias['avisRapporteur'];
                $whereConditions[] = $this->queryHelper->buildCriteria('d.AVIS_DOSSIER', $avisRapporteurValue);
            }

            // Critères : avis différé (depuis criterias)
            if (isset($criterias['avisDiffere'])) {
                /** @var array<int|string, int|string>|bool|int|string $avisDiffereValue */
                $avisDiffereValue = $criterias['avisDiffere'];
                $whereConditions[] = $this->queryHelper->buildCriteria('d.DIFFEREAVIS_DOSSIER', $avisDiffereValue);
            }

            // Critères : permis
            if (isset($criterias['permis'])) {
                /** @var array<int|string, int|string>|int|string $permisValue */
                $permisValue = $criterias['permis'];
                $whereConditions[] = $this->queryHelper->buildCriteria('dossierdocurba.NUM_DOCURBA', $permisValue);
            }

            // Critères : courrier
            if (isset($criterias['courrier'])) {
                /** @var array<int|string, int|string>|int|string $courrierValue */
                $courrierValue = $criterias['courrier'];
                $whereConditions[] = $this->queryHelper->buildCriteria('d.REFCOURRIER_DOSSIER', $courrierValue, false);
            }

            // Critères : preventionniste
            if (isset($criterias['preventionniste'])) {
                /** @var array<int|string, int|string>|int|string $preventionnisteValue */
                $preventionnisteValue = $criterias['preventionniste'];
                $whereConditions[] = $this->queryHelper->buildCriteria('dossierpreventionniste.ID_PREVENTIONNISTE', $preventionnisteValue);
            }

            if (isset($criterias['commune'])) {
                /** @var array<int|string, int|string>|int|string $communeValue */
                $communeValue = $criterias['commune'];
                $whereConditions[] = $this->queryHelper->buildCriteria('ea.NUMINSEE_COMMUNE', $communeValue);
            }

            if (isset($criterias['voie'])) {
                /** @var array<int|string, int|string>|int|string $voieValue */
                $voieValue = $criterias['voie'];
                $whereConditions[] = $this->queryHelper->buildCriteria('ea.ID_RUE', $voieValue);
            }

            if (isset($criterias['numero'])) {
                /** @var array<int|string, int|string>|int|string $numeroValue */
                $numeroValue = $criterias['numero'];
                $whereConditions[] = $this->queryHelper->buildCriteria('ea.NUMERO_ADRESSE', $numeroValue);
            }

            // Critère : groupement territorial
            if (isset($criterias['groupements_territoriaux'])) {
                /** @var array<int|string, int|string>|int|string $groupementValue */
                $groupementValue = $criterias['groupements_territoriaux'];
                $whereConditions[] = $this->queryHelper->buildCriteria('groupement.ID_GROUPEMENT', $groupementValue);
            }

            // Critères : label établissement
            if (isset($criterias['label']) && is_string($criterias['label'])) {
                $cleanLabel = trim($criterias['label']);
                if (str_starts_with($cleanLabel, '#')) {
                    $whereConditions[] = $this->queryHelper->buildCriteria('NUMEROID_ETABLISSEMENT', substr($cleanLabel, 1), false);
                } elseif (1 === preg_match('/^[E0-9\/\-\.]+([0-9A-Z]{1,2})?$/', $cleanLabel)) {
                    $whereConditions[] = $this->queryHelper->buildCriteria('NUMEROID_ETABLISSEMENT', $cleanLabel, false);
                } else {
                    $whereConditions[] = $this->queryHelper->buildCriteria('LIBELLE_ETABLISSEMENTINFORMATIONS', $cleanLabel, false);
                }
            }

            // Critères : identifiant établissement
            if (isset($criterias['identifiant'])) {
                /** @var array<int|string, int|string>|int|string $identifiantValue */
                $identifiantValue = $criterias['identifiant'];
                $whereConditions[] = $this->queryHelper->buildCriteria('NUMEROID_ETABLISSEMENT', $identifiantValue);
            }

            if (isset($criterias['dateCreationStart']) && is_string($criterias['dateCreationStart'])) {
                $whereConditions[] = 'd.DATEINSERT_DOSSIER >= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateCreationStart']).", '%d/%m/%Y')";
            }

            if (isset($criterias['dateCreationEnd']) && is_string($criterias['dateCreationEnd'])) {
                $whereConditions[] = 'd.DATEINSERT_DOSSIER <= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateCreationEnd']).", '%d/%m/%Y')";
            }

            if (isset($criterias['dateReceptionStart']) && is_string($criterias['dateReceptionStart'])) {
                $whereConditions[] = 'd.DATESDIS_DOSSIER >= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateReceptionStart']).", '%d/%m/%Y')";
            }

            if (isset($criterias['dateReceptionEnd']) && is_string($criterias['dateReceptionEnd'])) {
                $whereConditions[] = 'd.DATESDIS_DOSSIER <= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateReceptionEnd']).", '%d/%m/%Y')";
            }

            if (isset($criterias['dateReponseStart']) && is_string($criterias['dateReponseStart'])) {
                $whereConditions[] = 'd.DATEREP_DOSSIER >= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateReponseStart']).", '%d/%m/%Y')";
            }

            if (isset($criterias['dateReponseEnd']) && is_string($criterias['dateReponseEnd'])) {
                $whereConditions[] = 'd.DATEREP_DOSSIER <= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateReponseEnd']).", '%d/%m/%Y')";
            }

            if (isset($criterias['dateCommissionStart']) && is_string($criterias['dateCommissionStart'])) {
                $whereConditions[] = "STR_TO_DATE(d.DATECOMM_DOSSIER, '%Y-%m-%d') >= STR_TO_DATE(".$this->queryHelper->safeQuote($criterias['dateCommissionStart']).", '%d/%m/%Y')";
            }

            if (isset($criterias['dateCommissionEnd']) && is_string($criterias['dateCommissionEnd'])) {
                $whereConditions[] = "STR_TO_DATE(d.DATECOMM_DOSSIER, '%Y-%m-%d') <= STR_TO_DATE(".$this->queryHelper->safeQuote($criterias['dateCommissionEnd']).", '%d/%m/%Y')";
            }

            if (isset($criterias['dateVisiteStart']) && is_string($criterias['dateVisiteStart'])) {
                $whereConditions[] = 'd.DATEVISITE_DOSSIER >= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateVisiteStart']).", '%d/%m/%Y')";
            }

            if (isset($criterias['dateVisiteEnd']) && is_string($criterias['dateVisiteEnd'])) {
                $whereConditions[] = 'd.DATEVISITE_DOSSIER <= STR_TO_DATE('.$this->queryHelper->safeQuote($criterias['dateVisiteEnd']).", '%d/%m/%Y')";
            }

            $provenanceValue = isset($criterias['provenance']) && is_string($criterias['provenance']) ? $criterias['provenance'] : null;
            if (null !== $provenanceValue) {
                if ('1' === $provenanceValue) {
                    $whereConditions[] = 'd.ID_PLATAU IS NOT NULL';
                } elseif ('2' === $provenanceValue) {
                    $whereConditions[] = 'd.ID_PLATAU IS NULL';
                }
            }
        }

        $sqlFull = $sqlSelect
            .$sqlFrom
            .' WHERE '.implode(' AND ', $whereConditions)
            .' GROUP BY d.ID_DOSSIER';

        if (!empty($havingConditions)) {
            $sqlFull .= ' HAVING '.implode(' AND ', $havingConditions);
        }

        $sqlFull .= ' ORDER BY adressecommune.LIBELLE_COMMUNE ASC'
            .', categorie.LIBELLE_CATEGORIE ASC'
            .', type.LIBELLE_TYPE ASC'
            .', ei.LIBELLE_ETABLISSEMENTINFORMATIONS ASC'
            .', d.DATEINSERT_DOSSIER DESC';

        /** @var array<int, array<string, string|null>> $rows */
        $rows = $this->connection->executeQuery($sqlFull)->fetchAllAssociative();

        return [
            'results' => $rows,
            'search_metadata' => ['count' => count($rows)],
        ];
    }
}

<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Service de recherche.
 * Port direct de Service_Search (legacy).
 * Le cache Zend_Cache n'est pas porté (inutile avec LIMIT 50).
 */
class Search
{
    public const MAX_LIMIT_PAGES_ETABLISSEMENTS = 1000;

    public const MAX_LIMIT_PAGES_DOSSIERS = 100;

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
     * Recherche des établissements.
     *
     * @param array<int|string, int|string>|null $genres
     * @param array<int|string, int|string>|null $categories
     * @param array<int|string, int|string>|null $classes
     * @param array<int|string, int|string>|null $familles
     * @param array<int|string, int|string>|null $typesActivites
     * @param array<int|string, int|string>|null $statuts
     * @param array<int|string, int|string>|null $commissions
     * @param array<int|string, int|string>|null $groupementsTerritoriaux
     *
     * @return array{results: array<int|string, array<string, mixed>>, search_metadata: array{count: int, current_page: int}}
     */
    public function etablissements(
        ?string $label,
        ?string $identifiant,
        ?array $genres,
        ?array $categories,
        ?array $classes,
        ?array $familles,
        ?array $typesActivites,
        ?bool $avisFavorable,
        ?array $statuts,
        ?bool $localSommeil,
        ?float $lon,
        ?float $lat,
        ?int $parent,
        ?string $city,
        ?string $street,
        ?string $number,
        ?array $commissions,
        ?array $groupementsTerritoriaux,
        ?int $preventionniste,
        int $count = 50,
        int $page = 1
    ): array {
        $effectiveCount = $count > self::MAX_LIMIT_PAGES_ETABLISSEMENTS
            ? self::MAX_LIMIT_PAGES_ETABLISSEMENTS
            : $count;

        $sqlSelect = 'SELECT e.NUMEROID_ETABLISSEMENT, e.DUREEVISITE_ETABLISSEMENT, e.NBPREV_ETABLISSEMENT,
            ( SELECT COUNT(etablissementlie2.ID_FILS_ETABLISSEMENT)
                FROM etablissement etab2
                INNER JOIN etablissementlie etablissementlie2 ON etab2.ID_ETABLISSEMENT = etablissementlie2.ID_ETABLISSEMENT
                WHERE etab2.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                AND etab2.DATESUPPRESSION_ETABLISSEMENT IS NULL
            ) AS NB_ENFANTS,
            (SELECT COUNT(dossierlie2.ID_DOSSIER1)
                FROM dossier doss2
                INNER JOIN etablissementdossier etdoss2 ON doss2.ID_DOSSIER = etdoss2.ID_DOSSIER
                INNER JOIN dossierlie dossierlie2 ON doss2.ID_DOSSIER = dossierlie2.ID_DOSSIER2
                INNER JOIN dossiernature dossnature2 ON dossierlie2.ID_DOSSIER1 = dossnature2.ID_DOSSIER
                WHERE dossnature2.ID_NATURE = 46
                AND etdoss2.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                AND doss2.DATESUPPRESSION_DOSSIER IS NULL
            ) AS PRESENCE_ECHEANCIER_TRAVAUX,
            etablissementinformations.*,
            dossier.DATEVISITE_DOSSIER, dossier.DATECOMM_DOSSIER, dossier.DATEINSERT_DOSSIER, dossier.DIFFEREAVIS_DOSSIER,
            avis.ID_AVIS, avis.LIBELLE_AVIS,
            type.LIBELLE_TYPE,
            typeactivite.LIBELLE_ACTIVITE,
            genre.LIBELLE_GENRE,
            etablissementlie.ID_ETABLISSEMENT AS pere, etablissementlie.ID_FILS_ETABLISSEMENT,
            etablissementadresse.NUMINSEE_COMMUNE, etablissementadresse.LON_ETABLISSEMENTADRESSE, etablissementadresse.LAT_ETABLISSEMENTADRESSE, etablissementadresse.ID_ADRESSE, etablissementadresse.ID_RUE, etablissementadresse.NUMERO_ADRESSE,
            adressecommune.LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_DEFAULT,
            adresserue.LIBELLE_RUE,
            etablissementadressesite.ID_RUE AS ID_RUE_SITE,
            adressecommunesite.LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_SITE,
            etablissementadressecell.ID_RUE AS ID_RUE_CELL,
            adressecommunecell.LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_CELLULE';

        $sqlFrom = ' FROM etablissement e
            INNER JOIN etablissementinformations ON e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT
                AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                )
            LEFT JOIN dossier ON e.ID_DOSSIER_DONNANT_AVIS = dossier.ID_DOSSIER
            LEFT JOIN avis ON dossier.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS
            LEFT JOIN type ON etablissementinformations.ID_TYPE = type.ID_TYPE
            LEFT JOIN typeactivite ON etablissementinformations.ID_TYPEACTIVITE = typeactivite.ID_TYPEACTIVITE
            INNER JOIN genre ON etablissementinformations.ID_GENRE = genre.ID_GENRE
            LEFT JOIN etablissementlie ON e.ID_ETABLISSEMENT = etablissementlie.ID_FILS_ETABLISSEMENT
            LEFT JOIN etablissementadresse ON e.ID_ETABLISSEMENT = etablissementadresse.ID_ETABLISSEMENT
            LEFT JOIN adressecommune ON etablissementadresse.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
            LEFT JOIN adresserue ON adresserue.ID_RUE = etablissementadresse.ID_RUE
            LEFT JOIN etablissementadresse AS etablissementadressesite
                ON etablissementadressesite.ID_ETABLISSEMENT = (
                    SELECT ID_FILS_ETABLISSEMENT FROM etablissementlie WHERE ID_ETABLISSEMENT = e.ID_ETABLISSEMENT LIMIT 1
                )
            LEFT JOIN adressecommune AS adressecommunesite
                ON etablissementadressesite.NUMINSEE_COMMUNE = adressecommunesite.NUMINSEE_COMMUNE
            LEFT JOIN etablissementadresse AS etablissementadressecell
                ON etablissementadressecell.ID_ETABLISSEMENT = (
                    SELECT ID_ETABLISSEMENT FROM etablissementlie WHERE ID_FILS_ETABLISSEMENT = e.ID_ETABLISSEMENT LIMIT 1
                )
            LEFT JOIN adressecommune AS adressecommunecell
                ON etablissementadressecell.NUMINSEE_COMMUNE = adressecommunecell.NUMINSEE_COMMUNE';

        $whereConditions = ['e.DATESUPPRESSION_ETABLISSEMENT IS NULL'];
        $hasAdditionalConditions = false;

        // Critères : nom de l'établissement
        if (null !== $label) {
            $cleanLabel = trim($label);
            if ('#' === substr($cleanLabel, 0, 1)) {
                $whereConditions[] = $this->buildCriteria('NUMEROID_ETABLISSEMENT', substr($cleanLabel, 1), false);
            } elseif (1 === preg_match('/^[E0-9\/\-\.]+([0-9A-Z]{1,2})?$/', $cleanLabel)) {
                $whereConditions[] = $this->buildCriteria('NUMEROID_ETABLISSEMENT', $cleanLabel, false);
            } else {
                $whereConditions[] = $this->buildCriteria('LIBELLE_ETABLISSEMENTINFORMATIONS', $cleanLabel, false);
            }

            $hasAdditionalConditions = true;
        }

        // Critères : identifiant
        if (null !== $identifiant) {
            $whereConditions[] = $this->buildCriteria('NUMEROID_ETABLISSEMENT', $identifiant);
            $hasAdditionalConditions = true;
        }

        // Critères : genre
        if (null !== $genres) {
            $whereConditions[] = $this->buildCriteria('genre.ID_GENRE', $genres);
            $hasAdditionalConditions = true;
        }

        // Critères : catégorie
        if (null !== $categories) {
            $whereConditions[] = $this->buildCriteria('ID_CATEGORIE', $categories);
            $hasAdditionalConditions = true;
        }

        // Critères : classe
        if (null !== $classes) {
            $whereConditions[] = $this->buildCriteria('ID_CLASSE', $classes);
            $hasAdditionalConditions = true;
        }

        // Critères : famille
        if (null !== $familles) {
            $whereConditions[] = $this->buildCriteria('ID_FAMILLE', $familles);
            $hasAdditionalConditions = true;
        }

        // Critères : type d'activité
        if (null !== $typesActivites) {
            $whereConditions[] = $this->buildCriteria('typeactivite.ID_TYPEACTIVITE', $typesActivites);
            $hasAdditionalConditions = true;
        }

        // Critères : avis favorable
        if (null !== $avisFavorable) {
            $whereConditions[] = $this->buildCriteria('avis.ID_AVIS', $avisFavorable ? 1 : 2);
            $hasAdditionalConditions = true;
        }

        // Critères : statuts
        if (null !== $statuts) {
            $whereConditions[] = $this->buildCriteria('ID_STATUT', $statuts);
            $hasAdditionalConditions = true;
        }

        // Critères : local à sommeil
        if (null !== $localSommeil) {
            $whereConditions[] = $this->buildCriteria('LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS', $localSommeil);
            $hasAdditionalConditions = true;
        }

        // Critères : numéro de rue
        if (null !== $number) {
            $clauses = [];
            $clauses[] = 'etablissementadresse.NUMERO_ADRESSE='.$this->safeQuote($number);
            if (null === $genres || in_array('1', $genres)) {
                $clauses[] = 'etablissementadressesite.NUMERO_ADRESSE='.$this->safeQuote($number);
            }

            if (null === $genres || in_array('3', $genres)) {
                $clauses[] = 'etablissementadressecell.NUMERO_ADRESSE='.$this->safeQuote($number);
            }

            $whereConditions[] = '('.implode(' OR ', $clauses).')';
            $hasAdditionalConditions = true;
        }

        // Critère : commune et rue
        if (null !== $street) {
            $clauses = [];
            $clauses[] = 'etablissementadresse.ID_RUE='.$this->safeQuote($street);
            if (null === $genres || in_array('1', $genres)) {
                $clauses[] = 'etablissementadressesite.ID_RUE='.$this->safeQuote($street);
            }

            if (null === $genres || in_array('3', $genres)) {
                $clauses[] = 'etablissementadressecell.ID_RUE='.$this->safeQuote($street);
            }

            $whereConditions[] = '('.implode(' OR ', $clauses).')';
            $hasAdditionalConditions = true;
        } elseif (null !== $city) {
            $clauses = [];
            $clauses[] = 'etablissementadresse.NUMINSEE_COMMUNE='.$this->safeQuote($city);
            if (null === $genres || in_array('1', $genres)) {
                $clauses[] = 'etablissementadressesite.NUMINSEE_COMMUNE='.$this->safeQuote($city);
            }

            if (null === $genres || in_array('3', $genres)) {
                $clauses[] = 'etablissementadressecell.NUMINSEE_COMMUNE='.$this->safeQuote($city);
            }

            $whereConditions[] = '('.implode(' OR ', $clauses).')';
            $hasAdditionalConditions = true;
        }

        // Critère : commission
        if (null !== $commissions) {
            $whereConditions[] = $this->buildCriteria('ID_COMMISSION', $commissions);
            $hasAdditionalConditions = true;
        }

        // Critère : groupement territorial (JOIN conditionnel)
        $groupementJoin = '';
        if (null !== $groupementsTerritoriaux) {
            $groupementJoin = '
                LEFT JOIN groupementcommune ON groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
                LEFT JOIN groupement ON groupement.ID_GROUPEMENT = groupementcommune.ID_GROUPEMENT AND groupement.ID_GROUPEMENTTYPE = 5';
            $whereConditions[] = $this->buildCriteria('groupement.ID_GROUPEMENT', $groupementsTerritoriaux);
            $hasAdditionalConditions = true;
        }

        // Critères : géolocalisation
        if (null !== $lon && null !== $lat) {
            $whereConditions[] = $this->buildCriteria('etablissementadresse.LON_ETABLISSEMENTADRESSE', $lon);
            $whereConditions[] = $this->buildCriteria('etablissementadresse.LAT_ETABLISSEMENTADRESSE', $lat);
            $hasAdditionalConditions = true;
        }

        // Critères : parent
        if (null !== $parent) {
            if (0 === $parent) {
                $whereConditions[] = 'etablissementlie.ID_ETABLISSEMENT IS NULL';
            } else {
                $whereConditions[] = 'etablissementlie.ID_ETABLISSEMENT='.$this->safeQuote((string) $parent);
            }

            $hasAdditionalConditions = true;
        }

        // Critère : preventionniste (JOIN conditionnel)
        $preventionnisteJoin = '';
        if (null !== $preventionniste) {
            $preventionnisteJoin = '
                LEFT JOIN etablissementinformationspreventionniste
                    ON etablissementinformationspreventionniste.ID_ETABLISSEMENTINFORMATIONS = etablissementinformations.ID_ETABLISSEMENTINFORMATIONS';
            $whereConditions[] = 'etablissementinformationspreventionniste.ID_UTILISATEUR='.$this->safeQuote((string) $preventionniste);
            $hasAdditionalConditions = true;
        }

        $sqlFull = $sqlSelect
            .$sqlFrom
            .$groupementJoin
            .$preventionnisteJoin
            .' WHERE '.implode(' AND ', $whereConditions)
            .' GROUP BY e.ID_ETABLISSEMENT';

        // Performance optimisation : tri uniquement si critères supplémentaires
        if ($hasAdditionalConditions) {
            $sqlFull .= ' ORDER BY etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS ASC';
        }

        // COUNT total
        $countSql = 'SELECT COUNT(*) FROM ('.$sqlFull.') AS total_count';
        $totalCountRaw = $this->connection->executeQuery($countSql)->fetchOne();
        $totalCount = is_numeric($totalCountRaw) ? (int) $totalCountRaw : 0;

        // Requête paginée
        $offset = ($page - 1) * $effectiveCount;
        $paginatedSql = $sqlFull.' LIMIT '.$effectiveCount.' OFFSET '.$offset;

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->connection->executeQuery($paginatedSql)->fetchAllAssociative();

        return [
            'results' => $rows,
            'search_metadata' => [
                'count' => $totalCount,
                'current_page' => $page,
            ],
        ];
    }

    /**
     * Recherche des dossiers.
     *
     * @param array<int|string, int|string>|null $types
     * @param array<string, mixed>|null          $criterias
     *
     * @return array{results: array<int|string, array<string, mixed>>, search_metadata: array{count: int, current_page: int}}
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
        $effectiveCount = $count > self::MAX_LIMIT_PAGES_DOSSIERS
            ? self::MAX_LIMIT_PAGES_DOSSIERS
            : $count;

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
            eia.LIBELLE_ETABLISSEMENTINFORMATIONS, eia.ID_ETABLISSEMENT,
            type.ID_TYPE, type.LIBELLE_TYPE,
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

        // Critères : numéro de doc urba
        if (null !== $numDocUrba) {
            $havingConditions[] = 'NB_URBA LIKE '.$this->safeQuote('%'.$numDocUrba.'%');
        }

        // Critères : objet
        if (null !== $objet) {
            $cleanObjet = trim($objet);
            if ('#' === substr($cleanObjet, 0, 1)) {
                $havingConditions[] = 'NB_URBA LIKE '.$this->safeQuote('%'.substr($cleanObjet, 1).'%');
            } elseif (1 === preg_match('/^[0-9A-Z\.]+$/', $cleanObjet)) {
                $havingConditions[] = 'NB_URBA LIKE '.$this->safeQuote('%'.$cleanObjet.'%');
            } elseif (1 === preg_match('/^[A-Z0-9]{3}-[A-Z0-9]{3}-[A-Z0-9]{3}$/', $cleanObjet)) {
                $whereConditions[] = 'd.ID_PLATAU='.$this->safeQuote($cleanObjet);
                $hasAdditionalConditions = true;
            } else {
                $whereConditions[] = $this->buildCriteria('OBJET_DOSSIER', $cleanObjet, false);
                $hasAdditionalConditions = true;
            }
        }

        // Critères : parent
        if (null !== $parent) {
            if (0 === $parent) {
                $whereConditions[] = 'dossierlie.ID_DOSSIER1 IS NULL';
            } else {
                $whereConditions[] = 'dossierlie.ID_DOSSIER1='.$this->safeQuote((string) $parent);
            }

            $hasAdditionalConditions = true;
        }

        // Critères : type
        if (null !== $types) {
            $whereConditions[] = $this->buildCriteria('dossiertype.ID_DOSSIERTYPE', $types);
            $hasAdditionalConditions = true;
        }

        // Critères : avis différé
        if (null !== $avisDiffere) {
            $whereConditions[] = $this->buildCriteria('d.DIFFEREAVIS_DOSSIER', $avisDiffere);
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
                $whereConditions[] = $this->buildCriteria('datecommission.COMMISSION_CONCERNE', $commissionsValue);
                $hasAdditionalConditions = true;
            }

            // Critères : avis commission
            if (isset($criterias['avisCommission'])) {
                /** @var array<int|string, int|string>|int|string $avisCommissionValue */
                $avisCommissionValue = $criterias['avisCommission'];
                $whereConditions[] = $this->buildCriteria('d.AVIS_DOSSIER_COMMISSION', $avisCommissionValue);
                $hasAdditionalConditions = true;
            }

            // Critères : avis rapporteur
            if (isset($criterias['avisRapporteur'])) {
                /** @var array<int|string, int|string>|int|string $avisRapporteurValue */
                $avisRapporteurValue = $criterias['avisRapporteur'];
                $whereConditions[] = $this->buildCriteria('d.AVIS_DOSSIER', $avisRapporteurValue);
                $hasAdditionalConditions = true;
            }

            // Critères : avis différé (depuis criterias)
            if (isset($criterias['avisDiffere'])) {
                /** @var array<int|string, int|string>|bool|int|string $avisDiffereValue */
                $avisDiffereValue = $criterias['avisDiffere'];
                $whereConditions[] = $this->buildCriteria('d.DIFFEREAVIS_DOSSIER', $avisDiffereValue);
                $hasAdditionalConditions = true;
            }

            // Critères : permis
            if (isset($criterias['permis'])) {
                /** @var array<int|string, int|string>|int|string $permisValue */
                $permisValue = $criterias['permis'];
                $whereConditions[] = $this->buildCriteria('dossierdocurba.NUM_DOCURBA', $permisValue);
                $hasAdditionalConditions = true;
            }

            // Critères : courrier
            if (isset($criterias['courrier'])) {
                /** @var array<int|string, int|string>|int|string $courrierValue */
                $courrierValue = $criterias['courrier'];
                $whereConditions[] = $this->buildCriteria('d.REFCOURRIER_DOSSIER', $courrierValue, false);
                $hasAdditionalConditions = true;
            }

            // Critères : preventionniste
            if (isset($criterias['preventionniste'])) {
                $preventionnisteJoin = '
                    LEFT JOIN dossierpreventionniste ON dossierpreventionniste.ID_DOSSIER = d.ID_DOSSIER';
                /** @var array<int|string, int|string>|int|string $preventionnisteValue */
                $preventionnisteValue = $criterias['preventionniste'];
                $whereConditions[] = $this->buildCriteria('dossierpreventionniste.ID_PREVENTIONNISTE', $preventionnisteValue);
                $hasAdditionalConditions = true;
            }

            if (isset($criterias['commune'])) {
                /** @var array<int|string, int|string>|int|string $communeValue */
                $communeValue = $criterias['commune'];
                $whereConditions[] = $this->buildCriteria('ea.NUMINSEE_COMMUNE', $communeValue);
                $hasAdditionalConditions = true;
            }

            if (isset($criterias['voie'])) {
                /** @var array<int|string, int|string>|int|string $voieValue */
                $voieValue = $criterias['voie'];
                $whereConditions[] = $this->buildCriteria('ea.ID_RUE', $voieValue);
                $hasAdditionalConditions = true;
            }

            if (isset($criterias['numero'])) {
                /** @var array<int|string, int|string>|int|string $numeroValue */
                $numeroValue = $criterias['numero'];
                $whereConditions[] = $this->buildCriteria('ea.NUMERO_ADRESSE', $numeroValue);
                $hasAdditionalConditions = true;
            }

            // Critère : groupement territorial
            if (isset($criterias['groupements_territoriaux'])) {
                $groupementJoin = '
                    LEFT JOIN groupementcommune ON groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
                    LEFT JOIN groupement ON groupement.ID_GROUPEMENT = groupementcommune.ID_GROUPEMENT';
                /** @var array<int|string, int|string>|int|string $groupementValue */
                $groupementValue = $criterias['groupements_territoriaux'];
                $whereConditions[] = $this->buildCriteria('groupement.ID_GROUPEMENT', $groupementValue);
                $hasAdditionalConditions = true;
            }

            if (isset($criterias['dateCreationStart']) && is_string($criterias['dateCreationStart'])) {
                $whereConditions[] = 'd.DATEINSERT_DOSSIER >= STR_TO_DATE('.$this->safeQuote($criterias['dateCreationStart']).", '%d/%m/%Y')";
                $hasAdditionalConditions = true;
            }

            if (isset($criterias['dateCreationEnd']) && is_string($criterias['dateCreationEnd'])) {
                $whereConditions[] = 'd.DATEINSERT_DOSSIER <= STR_TO_DATE('.$this->safeQuote($criterias['dateCreationEnd']).", '%d/%m/%Y')";
                $hasAdditionalConditions = true;
            }

            if (isset($criterias['dateReceptionStart']) && is_string($criterias['dateReceptionStart'])) {
                $whereConditions[] = 'd.DATESDIS_DOSSIER >= STR_TO_DATE('.$this->safeQuote($criterias['dateReceptionStart']).", '%d/%m/%Y')";
                $hasAdditionalConditions = true;
            }

            if (isset($criterias['dateReceptionEnd']) && is_string($criterias['dateReceptionEnd'])) {
                $whereConditions[] = 'd.DATESDIS_DOSSIER <= STR_TO_DATE('.$this->safeQuote($criterias['dateReceptionEnd']).", '%d/%m/%Y')";
                $hasAdditionalConditions = true;
            }

            if (isset($criterias['dateReponseStart']) && is_string($criterias['dateReponseStart'])) {
                $whereConditions[] = 'd.DATEREP_DOSSIER >= STR_TO_DATE('.$this->safeQuote($criterias['dateReponseStart']).", '%d/%m/%Y')";
                $hasAdditionalConditions = true;
            }

            if (isset($criterias['dateReponseEnd']) && is_string($criterias['dateReponseEnd'])) {
                $whereConditions[] = 'd.DATEREP_DOSSIER <= STR_TO_DATE('.$this->safeQuote($criterias['dateReponseEnd']).", '%d/%m/%Y')";
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

        // COUNT total
        $countSql = 'SELECT COUNT(*) FROM ('.$sqlFull.') AS total_count';
        $totalCountRaw = $this->connection->executeQuery($countSql)->fetchOne();
        $totalCount = is_numeric($totalCountRaw) ? (int) $totalCountRaw : 0;

        // Requête paginée
        $offset = ($page - 1) * $effectiveCount;
        $paginatedSql = $sqlFull.' LIMIT '.$effectiveCount.' OFFSET '.$offset;

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->connection->executeQuery($paginatedSql)->fetchAllAssociative();

        // Index par ID_DOSSIER (comme le legacy)
        /** @var array<string, array<string, mixed>> $newResults */
        $newResults = [];
        foreach ($rows as $row) {
            $dossierIdValue = $row['ID_DOSSIER'];
            $dossierIdStr = is_int($dossierIdValue) || is_string($dossierIdValue) ? (string) $dossierIdValue : '';
            if ('' !== $dossierIdStr) {
                $newResults[$dossierIdStr] = $row;
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
                        $existingPrevList = isset($newResults[$dossId]['PREVENTIONNISTES']) && is_array($newResults[$dossId]['PREVENTIONNISTES'])
                            ? $newResults[$dossId]['PREVENTIONNISTES']
                            : [];
                        $existingPrevList[] = $prev;
                        $newResults[$dossId]['PREVENTIONNISTES'] = $existingPrevList;
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
                        $existingPjList = isset($newResults[$dossId]['PIECESJOINTES']) && is_array($newResults[$dossId]['PIECESJOINTES'])
                            ? $newResults[$dossId]['PIECESJOINTES']
                            : [];
                        $existingPjList[] = $pj;
                        $newResults[$dossId]['PIECESJOINTES'] = $existingPjList;
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
    }

    /**
     * Méthode helper pour construire une condition WHERE.
     *
     * @param array<int|string, bool|int|string>|bool|float|int|string $value
     */
    private function buildCriteria(string $key, $value, bool $exact = true): string
    {
        $parts = [];

        $values = is_array($value) ? $value : [$value];

        foreach ($values as $singleValue) {
            $stringValue = (string) $singleValue;
            $quotedVal = $this->safeQuote(
                $exact ? $stringValue : '%'.$stringValue.'%'
            );
            $parts[] = $key.($exact ? '=' : ' LIKE ').$quotedVal;
        }

        return '('.implode(' OR ', $parts).')';
    }

    /**
     * Wraps Connection::quote() to ensure a string return type (DBAL 2.x has untyped quote()).
     */
    private function safeQuote(string $value): string
    {
        $quoted = $this->connection->quote($value);

        return is_string($quoted) ? $quoted : "'".addslashes($value)."'";
    }
}

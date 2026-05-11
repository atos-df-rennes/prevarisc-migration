<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Recherche et export des établissements.
 * Extrait de Search (refactoring REF-SVC-01).
 */
class EtablissementSearchService
{
    public const MAX_LIMIT_PAGES_ETABLISSEMENTS = 1000;

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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

        // FROM de base : tables nécessaires aux filtres ET à l'affichage
        $sqlFromBase = ' FROM etablissement e
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
            LEFT JOIN adresserue ON adresserue.ID_RUE = etablissementadresse.ID_RUE';

        // JOINs adressesite/adressecell : utilisés pour l'affichage ET pour les filtres géographiques.
        // Ces JOINs contiennent des sous-requêtes corrélées : on les inclut conditionnellement
        // dans la requête de COUNT (uniquement si un filtre géographique est actif).
        $sqlFromAdresseSiteCell = '
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

        // Requête de données : inclut toujours adressesite/adressecell pour l'affichage
        $sqlFrom = $sqlFromBase.$sqlFromAdresseSiteCell;

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

        // COUNT lean : sans les sous-requêtes corrélées d'affichage (NB_ENFANTS, PRESENCE_ECHEANCIER_TRAVAUX)
        // ni les JOINs adressesite/adressecell sauf si un filtre géographique les requiert.
        // Gain : évite d'exécuter ~N×2 sous-requêtes corrélées sur la totalité des lignes.
        $needsAdresseFiltering = null !== $city || null !== $street || null !== $number;
        $sqlFromCount = $sqlFromBase
            .($needsAdresseFiltering ? $sqlFromAdresseSiteCell : '')
            .$groupementJoin
            .$preventionnisteJoin;
        $countSql = 'SELECT COUNT(DISTINCT e.ID_ETABLISSEMENT)'
            .$sqlFromCount
            .' WHERE '.implode(' AND ', $whereConditions);
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
     * Extraction de tous les établissements pour export calc.
     * Port direct de Service_Search::extractionEtablissements() (legacy).
     * Pas de pagination — retourne tous les résultats.
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
     * @return array{results: array<int, array<string, string|null>>, search_metadata: array{count: int}}
     */
    public function extractionEtablissements(
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
        ?int $preventionniste
    ): array {
        $sqlSelect = 'SELECT e.NUMEROID_ETABLISSEMENT,
            (SELECT MIN(dossier.DATEVISITE_DOSSIER)
                FROM dossier
                INNER JOIN dossiernature ON dossier.ID_DOSSIER = dossiernature.ID_DOSSIER
                INNER JOIN etablissementdossier ON dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
                WHERE dossiernature.ID_NATURE IN (19, 21, 23, 24, 26, 28, 29, 47, 48) AND dossier.AVIS_DOSSIER_COMMISSION = 1 AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
            ) AS DATE_PREMIER_AVIS_FAVORABLE,
            (SELECT MAX(dossier.DATEVISITE_DOSSIER)
                FROM dossier
                INNER JOIN dossiernature ON dossier.ID_DOSSIER = dossiernature.ID_DOSSIER
                INNER JOIN etablissementdossier ON dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
                WHERE dossiernature.ID_NATURE IN (21, 26, 47, 48) AND dossier.DATEVISITE_DOSSIER < CURDATE() AND dossier.DATESUPPRESSION_DOSSIER IS NULL AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
            ) AS DATE_DERNIERE_VISITE,
            (SELECT MAX(dossier.DATEVISITE_DOSSIER)
                FROM dossier
                INNER JOIN dossiernature ON dossier.ID_DOSSIER = dossiernature.ID_DOSSIER
                INNER JOIN etablissementdossier ON dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
                WHERE dossiernature.ID_NATURE IN (21, 26, 47, 48) AND dossier.DATEVISITE_DOSSIER >= CURDATE() AND dossier.DATESUPPRESSION_DOSSIER IS NULL AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
            ) AS DATE_VISITE_PREVUE,
            (SELECT CASE
                WHEN d.DATEVISITE_DOSSIER IS NOT NULL THEN (SELECT dossier.DATEVISITE_DOSSIER FROM dossier WHERE dossier.ID_DOSSIER = d.ID_DOSSIER)
                WHEN d.DATECOMM_DOSSIER IS NOT NULL THEN (SELECT dossier.DATECOMM_DOSSIER FROM dossier WHERE dossier.ID_DOSSIER = d.ID_DOSSIER)
                WHEN d.DATEINSERT_DOSSIER IS NOT NULL THEN (SELECT dossier.DATEINSERT_DOSSIER FROM dossier WHERE dossier.ID_DOSSIER = d.ID_DOSSIER)
                END
                FROM dossier d
                WHERE d.ID_DOSSIER = e.ID_DOSSIER_DONNANT_AVIS
            ) AS DATE_DERNIER_AVIS,
            (SELECT DISTINCT d1.DATEVISITE_DOSSIER FROM dossier d1 LEFT JOIN etablissementdossier ed ON d1.ID_DOSSIER = ed.ID_DOSSIER
                WHERE d1.DATEVISITE_DOSSIER = (SELECT MIN(DATEVISITE_DOSSIER) AS date_visite_defavorable_mini FROM dossier d2 INNER JOIN etablissementdossier ON etablissementdossier.ID_DOSSIER = d2.ID_DOSSIER
                    WHERE d2.AVIS_DOSSIER_COMMISSION = 2 AND d2.TYPE_DOSSIER = 2 AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                    AND NOT EXISTS (SELECT * FROM dossier INNER JOIN etablissementdossier ON etablissementdossier.ID_DOSSIER = dossier.ID_DOSSIER
                            WHERE dossier.AVIS_DOSSIER_COMMISSION = 1 AND dossier.TYPE_DOSSIER = 2 AND dossier.DATEVISITE_DOSSIER >= d2.DATEVISITE_DOSSIER AND etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT))
                AND ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
            ) AS DATE_PREMIER_AVIS_DEFAVORABLE_CONSECUTIF,
            etablissementinformations.*,
            dossier.DATEVISITE_DOSSIER, dossier.DATECOMM_DOSSIER, dossier.DATEINSERT_DOSSIER, dossier.DIFFEREAVIS_DOSSIER,
            avis.ID_AVIS, avis.LIBELLE_AVIS,
            categorie.LIBELLE_CATEGORIE,
            type.LIBELLE_TYPE,
            typeactivite.LIBELLE_ACTIVITE,
            commission.LIBELLE_COMMISSION,
            statut.LIBELLE_STATUT,
            genre.LIBELLE_GENRE,
            etablissementadresse.NUMINSEE_COMMUNE, etablissementadresse.ID_ADRESSE, etablissementadresse.ID_RUE,
            etablissementadresse.NUMERO_ADRESSE, etablissementadresse.COMPLEMENT_ADRESSE,
            adresserue.LIBELLE_RUE,
            adressecommune.CODEPOSTAL_COMMUNE, adressecommune.LIBELLE_COMMUNE,
            groupement.LIBELLE_GROUPEMENT,
            etablissementinformationspere.LIBELLE_ETABLISSEMENTINFORMATIONS AS LIBELLE_ETABLISSEMENT_PERE,
            etablissementadressesite.ID_RUE AS ID_RUE_SITE,
            adressecommunesite.LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_SITE,
            etablissementadressecell.ID_RUE AS ID_RUE_CELL,
            adressecommunecell.LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_CELLULE,
            utilisateurinformations.NOM_UTILISATEURINFORMATIONS, utilisateurinformations.PRENOM_UTILISATEURINFORMATIONS';

        $sqlFrom = ' FROM etablissement e
            INNER JOIN etablissementinformations ON e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT
                AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                )
            LEFT JOIN dossier ON e.ID_DOSSIER_DONNANT_AVIS = dossier.ID_DOSSIER
            LEFT JOIN avis ON dossier.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS
            LEFT JOIN categorie ON etablissementinformations.ID_CATEGORIE = categorie.ID_CATEGORIE
            LEFT JOIN type ON etablissementinformations.ID_TYPE = type.ID_TYPE
            LEFT JOIN typeactivite ON etablissementinformations.ID_TYPEACTIVITE = typeactivite.ID_TYPEACTIVITE
            LEFT JOIN commission ON etablissementinformations.ID_COMMISSION = commission.ID_COMMISSION
            LEFT JOIN statut ON etablissementinformations.ID_STATUT = statut.ID_STATUT
            INNER JOIN genre ON etablissementinformations.ID_GENRE = genre.ID_GENRE
            LEFT JOIN etablissementadresse ON e.ID_ETABLISSEMENT = etablissementadresse.ID_ETABLISSEMENT
            LEFT JOIN adresserue ON adresserue.ID_RUE = etablissementadresse.ID_RUE
            LEFT JOIN adressecommune ON etablissementadresse.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
            LEFT JOIN groupementcommune ON groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
            LEFT JOIN groupement ON groupement.ID_GROUPEMENT = groupementcommune.ID_GROUPEMENT AND groupement.ID_GROUPEMENTTYPE = 5
            LEFT JOIN etablissementlie ON e.ID_ETABLISSEMENT = etablissementlie.ID_FILS_ETABLISSEMENT
            LEFT JOIN etablissementinformations AS etablissementinformationspere
                ON etablissementinformationspere.ID_ETABLISSEMENT = etablissementlie.ID_ETABLISSEMENT
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
                ON etablissementadressecell.NUMINSEE_COMMUNE = adressecommunecell.NUMINSEE_COMMUNE
            LEFT JOIN etablissementinformationspreventionniste
                ON etablissementinformations.ID_ETABLISSEMENTINFORMATIONS = etablissementinformationspreventionniste.ID_ETABLISSEMENTINFORMATIONS
            LEFT JOIN utilisateur ON etablissementinformationspreventionniste.ID_UTILISATEUR = utilisateur.ID_UTILISATEUR
            LEFT JOIN utilisateurinformations
                ON utilisateurinformations.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS';

        $whereConditions = ['e.DATESUPPRESSION_ETABLISSEMENT IS NULL'];

        // Critères : nom de l'établissement
        if (null !== $label) {
            $cleanLabel = trim($label);
            if ('#' === substr($cleanLabel, 0, 1)) {
                $whereConditions[] = $this->buildCriteria('e.NUMEROID_ETABLISSEMENT', substr($cleanLabel, 1), false);
            } elseif (1 === preg_match('/^[E0-9\/\-\.]+([0-9A-Z]{1,2})?$/', $cleanLabel)) {
                $whereConditions[] = $this->buildCriteria('e.NUMEROID_ETABLISSEMENT', $cleanLabel, false);
            } else {
                $whereConditions[] = $this->buildCriteria('etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS', $cleanLabel, false);
            }
        }

        // Critères : identifiant
        if (null !== $identifiant) {
            $whereConditions[] = $this->buildCriteria('e.NUMEROID_ETABLISSEMENT', $identifiant);
        }

        // Critères : genre
        if (null !== $genres) {
            $whereConditions[] = $this->buildCriteria('genre.ID_GENRE', $genres);
        }

        // Critères : catégorie
        if (null !== $categories) {
            $whereConditions[] = $this->buildCriteria('categorie.ID_CATEGORIE', $categories);
        }

        // Critères : classe
        if (null !== $classes) {
            $whereConditions[] = $this->buildCriteria('etablissementinformations.ID_CLASSE', $classes);
        }

        // Critères : famille
        if (null !== $familles) {
            $whereConditions[] = $this->buildCriteria('etablissementinformations.ID_FAMILLE', $familles);
        }

        // Critères : type d'activité
        if (null !== $typesActivites) {
            $whereConditions[] = $this->buildCriteria('typeactivite.ID_TYPEACTIVITE', $typesActivites);
        }

        // Critères : avis favorable
        if (null !== $avisFavorable) {
            $whereConditions[] = $this->buildCriteria('avis.ID_AVIS', $avisFavorable ? 1 : 2);
        }

        // Critères : statuts
        if (null !== $statuts) {
            $whereConditions[] = $this->buildCriteria('etablissementinformations.ID_STATUT', $statuts);
        }

        // Critères : local à sommeil
        if (null !== $localSommeil) {
            $whereConditions[] = $this->buildCriteria('etablissementinformations.LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS', $localSommeil);
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
        }

        // Critère : commission
        if (null !== $commissions) {
            $whereConditions[] = $this->buildCriteria('commission.ID_COMMISSION', $commissions);
        }

        // Critère : groupement territorial
        if (null !== $groupementsTerritoriaux) {
            $whereConditions[] = $this->buildCriteria('groupement.ID_GROUPEMENT', $groupementsTerritoriaux);
        }

        // Critères : géolocalisation
        if (null !== $lon && null !== $lat) {
            $whereConditions[] = $this->buildCriteria('etablissementadresse.LON_ETABLISSEMENTADRESSE', $lon);
            $whereConditions[] = $this->buildCriteria('etablissementadresse.LAT_ETABLISSEMENTADRESSE', $lat);
        }

        // Critères : parent
        if (null !== $parent) {
            if (0 === $parent) {
                $whereConditions[] = 'etablissementlie.ID_ETABLISSEMENT IS NULL';
            } else {
                $whereConditions[] = 'etablissementlie.ID_ETABLISSEMENT='.$this->safeQuote((string) $parent);
            }
        }

        // Critère : preventionniste
        if (null !== $preventionniste) {
            $whereConditions[] = 'etablissementinformationspreventionniste.ID_UTILISATEUR='.$this->safeQuote((string) $preventionniste);
        }

        $sqlFull = $sqlSelect
            .$sqlFrom
            .' WHERE '.implode(' AND ', $whereConditions)
            .' GROUP BY e.ID_ETABLISSEMENT'
            .' ORDER BY adressecommune.LIBELLE_COMMUNE ASC, categorie.LIBELLE_CATEGORIE ASC, type.LIBELLE_TYPE ASC, statut.LIBELLE_STATUT ASC, etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS ASC';

        /** @var array<int, array<string, string|null>> $rows */
        $rows = $this->connection->executeQuery($sqlFull)->fetchAllAssociative();

        return [
            'results' => $rows,
            'search_metadata' => ['count' => count($rows)],
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
            $stringValue = is_bool($singleValue) ? (string) (int) $singleValue : (string) $singleValue;
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

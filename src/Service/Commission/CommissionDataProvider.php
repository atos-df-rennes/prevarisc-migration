<?php

namespace App\Service\Commission;

use App\Entity\EtablissementAdresse;
use App\Repository\EtablissementAdresseRepository;
use App\Service\Etablissement\CelluleCategorieResolver;
use Doctrine\DBAL\Connection;

/**
 * Fournisseur de données DBAL pour la génération de documents de commission.
 *
 * Centralise toutes les requêtes SQL brutes (lecture) utilisées lors
 * de la génération des convocations, ODJ, PV et comptes-rendus.
 */
class CommissionDataProvider
{
    public function __construct(private readonly Connection $conn, private readonly EtablissementAdresseRepository $etablissementAdresseRepository)
    {
    }

    /**
     * Récupère les informations d'une dateCommission (avec commission jointe).
     *
     * @return array<string, mixed>|null
     */
    public function getDateCommissionInfo(int $dateCommId): ?array
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
     * Récupère les informations d'une commission.
     *
     * @return array<string, mixed>|null
     */
    public function getCommissionInfo(int $commissionId): ?array
    {
        /** @var array<string, mixed>|false $row */
        $row = $this->conn->fetchAssociative(
            'SELECT * FROM commission WHERE ID_COMMISSION = :id',
            ['id' => $commissionId]
        );

        return false !== $row ? $row : null;
    }

    /**
     * Charge les membres avec leurs compétences en batch.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loadMembresWithCompetences(int $commissionId): array
    {
        $membres = $this->conn->fetchAllAssociative(
            'SELECT cm.ID_COMMISSIONMEMBRE as id_membre, cm.*
             FROM commissionmembre cm
             WHERE cm.ID_COMMISSION = :id',
            ['id' => $commissionId]
        );

        if (empty($membres)) {
            return [];
        }

        $membreIds = [];
        foreach ($membres as $membre) {
            /** @var int|string $membreIdRaw */
            $membreIdRaw = $membre['id_membre'] ?? 0;
            $membreIds[] = (int) $membreIdRaw;
        }

        // Batch : toutes les compétences en 4 requêtes
        $allCategories = $this->fetchCompetencesBatch('commissionmembrecategorie', $membreIds);
        $allTypes = $this->fetchCompetencesBatch('commissionmembretypeactivite', $membreIds);
        $allClasses = $this->fetchCompetencesBatch('commissionmembreclasse', $membreIds);
        $allDossierTypes = $this->fetchCompetencesBatch('commissionmembredossiertype', $membreIds);

        foreach ($membres as $idx => $membre) {
            /** @var int|string $membreIdRaw2 */
            $membreIdRaw2 = $membre['id_membre'] ?? 0;
            $membreId = (int) $membreIdRaw2;

            // infosFiles : le membre lui-même (pas de requête supplémentaire)
            $membres[$idx]['infosFiles'] = [$membre];

            // Compétences catégories (indexées par ID_CATEGORIE)
            $membres[$idx]['categories'] = [];
            foreach ($allCategories[$membreId] ?? [] as $cat) {
                /** @var int|string $catId */
                $catId = $cat['ID_CATEGORIE'] ?? 0;
                $membres[$idx]['categories'][$catId] = $cat;
            }

            // Compétences types (indexées par ID_TYPEACTIVITE)
            $membres[$idx]['types'] = [];
            foreach ($allTypes[$membreId] ?? [] as $t) {
                /** @var int|string $typeActiviteId */
                $typeActiviteId = $t['ID_TYPEACTIVITE'] ?? 0;
                $membres[$idx]['types'][$typeActiviteId] = $t;
            }

            // Compétences classes (indexées par ID_CLASSE)
            $membres[$idx]['classes'] = [];
            foreach ($allClasses[$membreId] ?? [] as $cl) {
                /** @var int|string $classeId */
                $classeId = $cl['ID_CLASSE'] ?? 0;
                $membres[$idx]['classes'][$classeId] = $cl;
            }

            // Compétences dossier types (indexées par ID_DOSSIERTYPE)
            $membres[$idx]['dossiertypes'] = [];
            foreach ($allDossierTypes[$membreId] ?? [] as $dt) {
                /** @var int|string $dossierTypeId */
                $dossierTypeId = $dt['ID_DOSSIERTYPE'] ?? 0;
                $membres[$idx]['dossiertypes'][$dossierTypeId] = $dt;
            }

            // Flag manifestation
            $membres[$idx]['manifestation'] = !empty($membre['MANIFESTATION_COMMISSIONMEMBRE']);
        }

        return $membres;
    }

    /**
     * Charge les membres (sans groupement) pour le PV.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loadMembresFilesForPv(int $commissionId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT * FROM commissionmembre
             WHERE ID_COMMISSION = :id AND ID_GROUPEMENT IS NULL',
            ['id' => $commissionId]
        );
    }

    /**
     * Charge les informations d'établissement en batch.
     *
     * @param array<int, int> $etabIds
     *
     * @return array<int, array<string, mixed>> Indexées par ID_ETABLISSEMENT
     */
    public function batchGetEtablissementInfos(array $etabIds): array
    {
        if (empty($etabIds)) {
            return [];
        }

        $uniqueIds = array_values(array_unique($etabIds));
        $placeholders = implode(',', array_fill(0, count($uniqueIds), '?'));

        // Infos générales
        $generals = $this->conn->fetchAllAssociative(
            'SELECT * FROM etablissement WHERE ID_ETABLISSEMENT IN ('.$placeholders.')',
            $uniqueIds
        );
        $generalsMap = [];
        foreach ($generals as $g) {
            /** @var int|string $gEtabId */
            $gEtabId = $g['ID_ETABLISSEMENT'] ?? 0;
            $generalsMap[(int) $gEtabId] = $g;
        }

        // Infos détaillées (dernière version)
        $informationsRows = $this->conn->fetchAllAssociative(
            'SELECT ei.*, cat.LIBELLE_CATEGORIE, ta.LIBELLE_ACTIVITE, g.LIBELLE_GENRE,
                    t.LIBELLE_TYPE as LIBELLE_TYPE_PRINCIPAL
             FROM etablissementinformations ei
             LEFT JOIN categorie cat ON ei.ID_CATEGORIE = cat.ID_CATEGORIE
             LEFT JOIN typeactivite ta ON ei.ID_TYPEACTIVITE = ta.ID_TYPEACTIVITE
             LEFT JOIN genre g ON ei.ID_GENRE = g.ID_GENRE
             LEFT JOIN type t ON ei.ID_TYPE = t.ID_TYPE
             WHERE ei.ID_ETABLISSEMENT IN ('.$placeholders.')
               AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                   SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                   FROM etablissementinformations ei2
                   WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
               )',
            $uniqueIds
        );
        $informationsMap = [];
        foreach ($informationsRows as $info) {
            /** @var int|string $infoEtabId */
            $infoEtabId = $info['ID_ETABLISSEMENT'] ?? 0;
            $informationsMap[(int) $infoEtabId] = $info;
        }

        // Adresses : résolution par genre d'établissement (site => enfant à l'effectif max,
        // cellule => établissement parent direct, cf. EtablissementAdresseRepository::get()),
        // comme le fait Service_Etablissement::get() dans le legacy.
        $adressesMap = [];
        foreach ($uniqueIds as $etabId) {
            /** @var array<int, EtablissementAdresse> $etablissementAdresses */
            $etablissementAdresses = $this->etablissementAdresseRepository->get($etabId);
            foreach ($etablissementAdresses as $adresse) {
                $rue = $adresse->getRue();
                if (null === $rue) {
                    continue;
                }

                $commune = $adresse->getCommune();
                $adressesMap[$etabId][] = [
                    'ID_ETABLISSEMENT' => $etabId,
                    'NUMERO_ADRESSE' => $adresse->getNumero(),
                    'LIBELLE_RUE' => $rue->getLibelle(),
                    'LIBELLE_COMMUNE' => null !== $commune ? $commune->getLibelle() : null,
                    'CODEPOSTAL_COMMUNE' => null !== $commune ? $commune->getCodePostal() : null,
                    'NUMINSEE_COMMUNE' => null !== $commune ? $commune->getId() : null,
                ];
            }
        }

        // Parents
        $parentsRows = $this->conn->fetchAllAssociative(
            'SELECT el.ID_FILS_ETABLISSEMENT, ei.LIBELLE_ETABLISSEMENTINFORMATIONS
             FROM etablissementlie el
             JOIN etablissementinformations ei ON el.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
             WHERE el.ID_FILS_ETABLISSEMENT IN ('.$placeholders.')
             ORDER BY ei.ID_ETABLISSEMENTINFORMATIONS DESC',
            $uniqueIds
        );
        $parentsMap = [];
        foreach ($parentsRows as $p) {
            /** @var int|string $pFilsId */
            $pFilsId = $p['ID_FILS_ETABLISSEMENT'] ?? 0;
            $parentsMap[(int) $pFilsId][] = $p;
        }

        // Catégorie effective : parent direct non supprimé, dernière fiche seulement.
        $parentInformationsRows = $this->conn->fetchAllAssociative(
            'SELECT el.ID_FILS_ETABLISSEMENT, ei.ID_CATEGORIE, cat.LIBELLE_CATEGORIE
             FROM etablissementlie el
             JOIN etablissement e ON e.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT
                 AND e.DATESUPPRESSION_ETABLISSEMENT IS NULL
             LEFT JOIN etablissementinformations ei ON ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                 AND ei.ID_ETABLISSEMENTINFORMATIONS = (
                     SELECT MAX(ei2.ID_ETABLISSEMENTINFORMATIONS)
                     FROM etablissementinformations ei2
                     WHERE ei2.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                 )
             LEFT JOIN categorie cat ON cat.ID_CATEGORIE = ei.ID_CATEGORIE
             WHERE el.ID_FILS_ETABLISSEMENT IN ('.$placeholders.')',
            $uniqueIds
        );
        $parentInformationsMap = [];
        foreach ($parentInformationsRows as $parentInformations) {
            /** @var int|string $filsId */
            $filsId = $parentInformations['ID_FILS_ETABLISSEMENT'];
            $parentInformationsMap[(int) $filsId] ??= $parentInformations;
        }

        // Établissements liés (enfants)
        $liesRows = $this->conn->fetchAllAssociative(
            'SELECT el.ID_ETABLISSEMENT, el.ID_FILS_ETABLISSEMENT as ID_ETABLISSEMENT_FILS
             FROM etablissementlie el
             WHERE el.ID_ETABLISSEMENT IN ('.$placeholders.')',
            $uniqueIds
        );
        $liesMap = [];
        foreach ($liesRows as $lie) {
            /** @var int|string $lieEtabId */
            $lieEtabId = $lie['ID_ETABLISSEMENT'] ?? 0;
            $liesMap[(int) $lieEtabId][] = ['ID_ETABLISSEMENT' => $lie['ID_ETABLISSEMENT_FILS']];
        }

        // Dernière visite
        $lastVisiteRows = $this->conn->fetchAllAssociative(
            'SELECT ed.ID_ETABLISSEMENT, MAX(d.DATEVISITE_DOSSIER) as DATEVISITE_DOSSIER
             FROM dossier d
             JOIN etablissementdossier ed ON d.ID_DOSSIER = ed.ID_DOSSIER
             WHERE ed.ID_ETABLISSEMENT IN ('.$placeholders.')
               AND d.TYPE_DOSSIER IN (2,3)
               AND d.DATEVISITE_DOSSIER IS NOT NULL
             GROUP BY ed.ID_ETABLISSEMENT',
            $uniqueIds
        );
        $lastVisiteMap = [];
        foreach ($lastVisiteRows as $lv) {
            /** @var int|string $lvEtabId */
            $lvEtabId = $lv['ID_ETABLISSEMENT'] ?? 0;
            $lastVisiteMap[(int) $lvEtabId] = $lv['DATEVISITE_DOSSIER'];
        }

        // Assembler les résultats
        $result = [];
        foreach ($uniqueIds as $etabId) {
            if (!isset($generalsMap[$etabId])) {
                continue;
            }

            $result[$etabId] = [
                'general' => $generalsMap[$etabId],
                'informations' => CelluleCategorieResolver::resolve($informationsMap[$etabId] ?? [], $parentInformationsMap[$etabId] ?? null),
                'adresses' => $adressesMap[$etabId] ?? [],
                'parents' => $parentsMap[$etabId] ?? [],
                'etablissement_lies' => $liesMap[$etabId] ?? [],
                'last_visite' => $lastVisiteMap[$etabId] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Charge les avis dérogations en batch pour plusieurs dossiers.
     *
     * @param array<int, int> $dossierIds
     *
     * @return array<int, array<int, array<string, mixed>>> Groupées par ID_DOSSIER
     */
    public function batchGetAvisDerogationsFromDossiers(array $dossierIds): array
    {
        if (empty($dossierIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($dossierIds), '?'));
        $params = array_values($dossierIds);
        $rows = $this->conn->fetchAllAssociative(
            'SELECT ad.*, d.ID_DOSSIER, d.DATECOMM_DOSSIER, d.DATEVISITE_DOSSIER,
                    a.LIBELLE_AVIS, dl.TYPE_DOSSIER, dl.OBJET_DOSSIER
             FROM avisderogations ad
             JOIN dossier d ON ad.ID_DOSSIER = d.ID_DOSSIER
             JOIN avis a ON ad.AVIS = a.ID_AVIS
             LEFT JOIN dossier dl ON ad.ID_DOSSIER_LIE = dl.ID_DOSSIER
             WHERE d.ID_DOSSIER IN ('.$placeholders.')',
            $params
        );

        $grouped = [];
        foreach ($rows as $row) {
            /** @var int|string $rawId */
            $rawId = $row['ID_DOSSIER'] ?? 0;
            $id = (int) $rawId;
            $grouped[$id][] = $row;
        }

        return $grouped;
    }

    /**
     * Charge les avis dérogations pour un ensemble d'établissements.
     *
     * @param array<int, int> $idsEtablissements
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvisDerogationsEtablissements(array $idsEtablissements): array
    {
        if (empty($idsEtablissements)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsEtablissements), '?'));

        $params = array_values($idsEtablissements);

        return $this->conn->fetchAllAssociative(
            'SELECT d.ID_DOSSIER, d.DATECOMM_DOSSIER, d.DATEVISITE_DOSSIER,
                    ad.*, a.LIBELLE_AVIS, d2.TYPE_DOSSIER
             FROM dossier d
             JOIN etablissementdossier ed ON d.ID_DOSSIER = ed.ID_DOSSIER
             JOIN etablissement e ON ed.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
             JOIN avisderogations ad ON d.ID_DOSSIER = ad.ID_DOSSIER
             JOIN avis a ON a.ID_AVIS = ad.AVIS
             LEFT JOIN dossier d2 ON d2.ID_DOSSIER = ad.ID_DOSSIER_LIE
             WHERE e.ID_ETABLISSEMENT IN ('.$placeholders.')
               AND ad.DISPLAY_HISTORIQUE = 1',
            $params
        );
    }

    /**
     * Charge les documents d'urbanisme en batch.
     *
     * @param array<int, int> $dossierIds
     *
     * @return array<int, array<int, array<string, mixed>>> Groupés par ID_DOSSIER
     */
    public function batchGetDocUrba(array $dossierIds): array
    {
        if (empty($dossierIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($dossierIds), '?'));
        $params = array_values($dossierIds);
        $rows = $this->conn->fetchAllAssociative(
            'SELECT * FROM dossierdocurba WHERE ID_DOSSIER IN ('.$placeholders.')',
            $params
        );

        $grouped = [];
        foreach ($rows as $row) {
            /** @var int|string $rawId */
            $rawId = $row['ID_DOSSIER'] ?? 0;
            $id = (int) $rawId;
            $grouped[$id][] = $row;
        }

        return $grouped;
    }

    /**
     * Récupère les informations de contact d'une commune.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCommuneContactInfo(string $numInsee): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT ui.*
             FROM utilisateurinformations ui
             JOIN adressecommune ac ON ac.ID_UTILISATEURINFORMATIONS = ui.ID_UTILISATEURINFORMATIONS
             WHERE ac.NUMINSEE_COMMUNE = :numInsee',
            ['numInsee' => $numInsee]
        );
    }

    /**
     * Charge toutes les lignes d'une table de compétences pour un ensemble de membres.
     *
     * @param string          $table     Nom de la table
     * @param array<int, int> $membreIds IDs des membres
     *
     * @return array<int, array<int, array<string, mixed>>> Résultats groupés par ID_COMMISSIONMEMBRE
     */
    private function fetchCompetencesBatch(string $table, array $membreIds): array
    {
        if (empty($membreIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($membreIds), '?'));
        $params = array_values($membreIds);
        $rows = $this->conn->fetchAllAssociative(
            'SELECT * FROM '.$table.' WHERE ID_COMMISSIONMEMBRE IN ('.$placeholders.')',
            $params
        );

        $grouped = [];
        foreach ($rows as $row) {
            /** @var int|string $rawMembreId */
            $rawMembreId = $row['ID_COMMISSIONMEMBRE'] ?? 0;
            $id = (int) $rawMembreId;
            $grouped[$id][] = $row;
        }

        return $grouped;
    }
}

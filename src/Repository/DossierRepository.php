<?php

namespace App\Repository;

use App\Constant\DossierNature;
use App\Constant\DossierType;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dossier>
 *
 * @method Dossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dossier[]    findAll()
 * @method Dossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierRepository extends ServiceEntityRepository implements HasContacts
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    public function findDeleted(): array
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $query = $queryBuilder
            ->addSelect('u')
            ->addSelect('dt')
            ->addSelect('dn')
            ->addSelect('e')
            ->addSelect('ei')
            ->leftJoin('d.deletedBy', 'u')
            ->join('d.type', 'dt')
            ->join('d.nature', 'dn')
            ->join('d.etablissements', 'e')
            ->join('e.informations', 'ei')
            ->where($queryBuilder->expr()->isNotNull('d.dateSuppression'))
            ->andWhere('ei.date = (SELECT MAX(etabInfos.date) from App\Entity\EtablissementInformations etabInfos where etabInfos.etablissement = e)')
            ->orderBy('d.dateSuppression', 'DESC')
            ->getQuery()
        ;

        return $query->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function getHistoriqueDossiersData(int $idEtablissement): array
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $query = $queryBuilder
            ->addSelect('e')
            ->addSelect('COALESCE(d.dateCommission, d.dateVisite) as hidden columnOrder')
            ->addSelect('dn')
            ->addSelect('da')
            ->addSelect('c')
            ->join('d.etablissements', 'e')
            ->join('d.nature', 'dn')
            ->join('d.avisCommission', 'da')
            ->leftJoin('d.createur', 'c')
            ->where('e.id = :id')
            ->andWhere('dn.id in (:nature)')
            ->andWhere('da.id in (1,2)')
            ->andWhere('d.dateCommission is not null OR d.dateVisite is not null')
            ->orderBy('columnOrder', 'ASC')
            ->setParameter('id', $idEtablissement)
            ->setParameter('nature', DossierNature::NATURES_DONNANT_AVIS)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Retourne le nombre de dossiers ayant comme contact celui passé en paramètre.
     */
    public function findNumberByContacts(UtilisateurInformations $contact): int
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $query = $queryBuilder
            ->select('COUNT(d.id)')
            ->join('d.contacts', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $contact->getId())
            ->getQuery()
        ;

        return $query->getSingleScalarResult();
    }

    /**
     * Récupère la dernière visite périodique d'un établissement.
     */
    public function getLastVisitePeriodique(int $idEtablissement, bool $useDateCommissionForPeriodicity = false): ?array
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->enable('dossier_non_supprime');

        try {
            $queryBuilder = $this->createQueryBuilder('dossier');

            if ($useDateCommissionForPeriodicity) {
                $queryBuilder->addSelect(
                    'CASE WHEN dossier.dateCommission >= dossier.dateVisite THEN dossier.dateCommission ELSE dossier.dateVisite END AS date_relance'
                );
                $queryBuilder->orderBy('date_relance', 'DESC');
            } else {
                $queryBuilder->addSelect('dossier.dateVisite AS date_relance');
                $queryBuilder->orderBy('dossier.dateVisite', 'DESC');
            }

            $queryBuilder
                ->join('dossier.etablissements', 'etablissement')
                ->join('dossier.type', 'type')
                ->join('dossier.nature', 'nature')
                ->where('etablissement.id = :idEtablissement')
                ->andWhere('type.id IN (:types)')
                ->andWhere('dossier.avisCommission > 0')
                ->andWhere('nature.id IN (:natures)')
                ->setParameter('idEtablissement', $idEtablissement)
                ->setParameter('types', [2, 3]) // Visite et Groupe de visite
                ->setParameter('natures', [
                    21,
                    26,
                    47,
                    48
                ]) // Natures périodiques
            ;

            $queryBuilder->setMaxResults(1);

            return $queryBuilder->getQuery()->getOneOrNullResult();
        } finally {
            $filters->disable('dossier_non_supprime');
        }
    }

    /**
     * Récupère le PC initial d'un établissement.
     */
    public function getPCInitial(Etablissement $etablissement): ?\DateTime
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder
            ->join('d.etablissements', 'e')
            ->join('d.type', 'dt')
            ->join('d.nature', 'dn')
            ->where('e.id = :idEtablissement')
            ->andWhere('dt.id = 1') // Type étude
            ->andWhere('dn.id = 1') // Nature PC
            ->setParameter('idEtablissement', $etablissement->getId())
            ->orderBy('d.dateInsert', 'ASC')
            ->setMaxResults(1)
        ;

        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return null === $result ? null : $result->getDateInsert();
    }

    /**
     * Retourne les dossiers d'un établissement en fonction de leur type.
     *
     * La pagination utilise le Paginator Doctrine pour gérer correctement les fetch-joins
     * (relations one-to-many) qui multiplient les lignes SQL — un LIMIT brut serait inexact.
     *
     * @param int        $id      Identifiant de l'établissement
     * @param array<int> $types   Types de dossiers à récupérer
     * @param bool       $reverse Si true, récupère les dossiers dont le type n'est pas dans $types
     * @param int|null   $limit   Nombre maximum de résultats (null = pas de limite)
     * @param int        $offset  Position de départ
     *
     * @return array<Dossier>
     */
    public function getDossiersEtablissement(int $id, array $types = [], bool $reverse = false, ?int $limit = null, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->join('d.etablissements', 'e')
            ->addSelect('e')
            ->leftJoin('d.documentsUrbanisme', 'du')
            ->addSelect('du')
            ->join('d.nature', 'dn')
            ->addSelect('dn')
            ->join('d.type', 'dt')
            ->addSelect('dt')
            ->join('e.informations', 'ei')
            ->addSelect('ei')
            ->leftJoin('d.dossiersLies', 'dl')
            ->addSelect('dl')
            ->leftJoin('d.consultationPlatau', 'dcp')
            ->addSelect('dcp')
            ->leftJoin('d.avisCommission', 'dac')
            ->addSelect('dac')
            ->where('e.id = :id');
        if ([] !== $types) {
            if ($reverse) {
                $query->andWhere($query->expr()->notIn('d.type', ':types'));
            } else {
                $query->andWhere($query->expr()->in('d.type', ':types'));
            }

            $query->setParameter('types', $types);
        }

        $query->setParameter('id', $id);

        if ($reverse) {
            $query->orderBy('d.dateInsert', 'DESC');
        } elseif ([1] === $types) {
            $query->addSelect('COALESCE(d.dateCommission, d.dateInsert) as HIDDEN sortDate')
                ->orderBy('sortDate', 'DESC');
        } else {
            $query->addSelect('COALESCE(d.dateVisite, d.dateCommission, d.dateInsert) as HIDDEN sortDate')
                ->orderBy('sortDate', 'DESC');
        }

        if (null === $limit && 0 === $offset) {
            return $query->getQuery()->getResult();
        }

        // Le Paginator Doctrine n'entre dans le chemin "fetch-join sécurisé" (subquery sur IDs)
        // que si maxResults !== null. Sans maxResults, l'OFFSET est appliqué sur des lignes SQL
        // brutes (les joins one-to-many multiplient les lignes), ce qui retourne des résultats
        // incorrects. On utilise PHP_INT_MAX pour simuler "pas de limite" tout en forçant le bon chemin.
        $paginator = new Paginator($query->getQuery());
        $paginator->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($limit ?? PHP_INT_MAX);

        return array_values(iterator_to_array($paginator->getIterator()));
    }

    /**
     * Retourne le nombre de dossiers d'un établissement en fonction de leur type.
     *
     * @param int        $id      Identifiant de l'établissement
     * @param array<int> $types   Types de dossiers à récupérer
     * @param bool       $reverse Si true, compte les dossiers dont le type n'est pas dans $types
     */
    public function countDossiersEtablissement(int $id, array $types = [], bool $reverse = false): int
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->select('COUNT(d.id)')
            ->join('d.etablissements', 'e')
            ->where('e.id = :id');

        if ([] !== $types) {
            if ($reverse) {
                $query->andWhere($query->expr()->notIn('d.type', ':types'));
            } else {
                $query->andWhere($query->expr()->in('d.type', ':types'));
            }

            $query->setParameter('types', $types);
        }

        $query->setParameter('id', $id);

        return (int) $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Retourne le nombre de pièces jointes nouvelles pour un dossier.
     */
    public function findNombreNouvellesPieces(Dossier $dossier, \DateTimeInterface $dateDerniereVisitePage): int
    {
        $query = $this->createQueryBuilder('d')
            ->select('COUNT(p)')
            ->join('d.piecesJointes', 'dpj')
            ->join('dpj.pieceJointe', 'p')
            ->where('d.id = :idDossier')
            ->andWhere('p.dateNotification >= :dateDerniereVisitePage')
            ->setParameter('idDossier', $dossier->getId())
            ->setParameter('dateDerniereVisitePage', $dateDerniereVisitePage)
        ;

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve le dernier dossier donnant avis valide pour un établissement.
     *
     * Logique legacy (getDernierIdDossierDonnantAvis) :
     * - Dossier lié à l'établissement
     * - Nature dans NATURES_DONNANT_AVIS
     * - Avis favorable (1) ou défavorable (2)
     * - Non supprimé
     * - Tri par date commission ou date visite (DESC)
     *
     * @param Etablissement $etablissement  Établissement pour lequel chercher le dernier dossier
     * @param Dossier|null  $excludeDossier Dossier à exclure de la recherche (ex: dossier courant en désaffectation)
     *
     * @return Dossier|null Dernier dossier valide ou null si aucun
     */
    public function findLastDossierDonnantAvisForEtablissement(Etablissement $etablissement, ?Dossier $excludeDossier = null): ?Dossier
    {
        $qb = $this->createQueryBuilder('d');

        $qb
            ->join('d.etablissements', 'e')
            ->join('d.nature', 'n')
            ->join('d.avisCommission', 'a') // AVIS_DOSSIER_COMMISSION (pas AVIS_DOSSIER)
            ->where('e.id = :etablissementId')
            ->andWhere('n.id IN (:naturesIds)')
            ->andWhere('a.id IN (1, 2)') // 1 = favorable, 2 = défavorable
            ->andWhere($qb->expr()->isNull('d.dateSuppression'))
            ->setParameter('etablissementId', $etablissement->getId())
            ->setParameter('naturesIds', DossierNature::NATURES_DONNANT_AVIS)
        ;

        // Exclure le dossier spécifié (pour éviter de retrouver le dossier courant en désaffectation)
        if (null !== $excludeDossier) {
            $qb->andWhere('d.id != :excludeDossierId')
                ->setParameter('excludeDossierId', $excludeDossier->getId());
        }

        // Tri par dateCommission puis dateVisite (DESC)
        // COALESCE dans addSelect() est interprété comme SQL brut (pas parsé par DQL)
        $qb->addSelect('COALESCE(d.dateCommission, d.dateVisite, d.dateInsert) as HIDDEN sortDate');
        $qb->orderBy('sortDate', 'DESC');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve le dernier dossier avec avis différé pour un établissement.
     *
     * Équivalent legacy : Service_Etablissement::getAvisEtablissement()
     *                      → Search avec DIFFEREAVIS_DOSSIER = 1
     *
     * Cherche parmi tous les dossiers de l'établissement celui avec :
     * - differeAvis = true
     * - Plus récente date d'insertion
     *
     * @param Etablissement $etablissement Établissement concerné
     *
     * @return Dossier|null Dernier dossier avec avis différé ou null
     */
    public function findLastDossierAvisDiffere(Etablissement $etablissement): ?Dossier
    {
        $qb = $this->createQueryBuilder('d');

        $qb->join('d.etablissements', 'e')
            ->where('e.id = :etablissementId')
            ->andWhere('d.avisDiffere = :avisDiffere')
            ->andWhere('d.dateSuppression IS NULL')
            ->setParameter('etablissementId', $etablissement->getId())
            ->setParameter('avisDiffere', true)
            ->orderBy('d.dateInsert', 'DESC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<Dossier>
     */
    public function getDossiersEnfants(int $parentId): array
    {
        $parent = $this->find($parentId);
        $dossiersLies = $parent->getDossiersLies();

        if ($dossiersLies->isEmpty()) {
            return [];
        }

        $ids = [];
        foreach ($dossiersLies as $dossierLie) {
            $ids[] = $dossierLie->getId();
        }

        return $this->createQueryBuilder('d')
            ->leftJoin('d.etablissements', 'e')
            ->addSelect('e')
            ->leftJoin('d.documentsUrbanisme', 'du')
            ->addSelect('du')
            ->join('d.nature', 'dn')
            ->addSelect('dn')
            ->join('d.type', 'dt')
            ->addSelect('dt')
            ->leftJoin('e.informations', 'ei')
            ->addSelect('ei')
            ->leftJoin('d.dossiersLies', 'dl')
            ->addSelect('dl')
            ->leftJoin('d.consultationPlatau', 'dcp')
            ->addSelect('dcp')
            ->leftJoin('d.avisCommission', 'dac')
            ->addSelect('dac')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie en une seule requête si chacun des dossiers fournis a une alerte réception travaux.
     *
     * Un dossier est éligible si lui-même a une nature d'étude (PC/AT, natures 1, 2, 12, 13)
     * et possède au moins un dossier lié (il est ID_DOSSIER1 dans dossierlie).
     *
     * @param array<int> $dossierIds
     *
     * @return array<int, bool> Map [dossierId => bool]
     */
    public function hasAlerteReceptionTravauxBulk(array $dossierIds): array
    {
        if ([] === $dossierIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('d');
        $rows = $qb->select('d.id')
            ->join('d.dossiersLies', 'dl')
            ->join('d.nature', 'dn')
            ->where($qb->expr()->in('d.id', ':ids'))
            ->andWhere($qb->expr()->in('dn.id', ':natures'))
            ->setParameter('ids', $dossierIds)
            ->setParameter('natures', DossierNature::NATURES_ALERTE_RECEPTION_TRAVAUX)
            ->groupBy('d.id')
            ->getQuery()
            ->getArrayResult();

        $idsWithAlerte = array_map('intval', array_column($rows, 'id'));

        $map = [];
        foreach ($dossierIds as $id) {
            $map[$id] = in_array($id, $idsWithAlerte, true);
        }

        return $map;
    }

    /**
     * Vérifie en une seule requête si chacun des dossiers fournis fait l'objet d'un échéancier travaux.
     *
     * Un dossier est éligible s'il est ID_DOSSIER2 dans dossierlie et que le maître (ID_DOSSIER1)
     * possède la nature 46 (Échéancier de travaux).
     *
     * @param array<int> $dossierIds
     *
     * @return array<int, bool> Map [dossierId => bool]
     */
    public function hasEcheancierTravauxBulk(array $dossierIds): array
    {
        if ([] === $dossierIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('d');
        $rows = $qb->select('d.id')
            ->join('d.dossiersAyantLie', 'dal')
            ->join('dal.nature', 'daln')
            ->where($qb->expr()->in('d.id', ':ids'))
            ->andWhere($qb->expr()->in('daln.id', ':natures'))
            ->setParameter('ids', $dossierIds)
            ->setParameter('natures', [DossierNature::ECHEANCIER_TRAVAUX])
            ->groupBy('d.id')
            ->getQuery()
            ->getArrayResult();

        $idsWithEcheancier = array_map('intval', array_column($rows, 'id'));

        $map = [];
        foreach ($dossierIds as $id) {
            $map[$id] = in_array($id, $idsWithEcheancier, true);
        }

        return $map;
    }

    /**
     * Compte en une seule requête le nombre de pièces jointes pour chaque dossier.
     *
     * @param array<int> $dossierIds
     *
     * @return array<int, int> Map [dossierId => count]
     */
    public function countPiecesJointesBulk(array $dossierIds): array
    {
        if ([] === $dossierIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('d')
            ->select('d.id, COUNT(dpj.pieceJointe) as cnt')
            ->leftJoin('d.piecesJointes', 'dpj')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $dossierIds)
            ->groupBy('d.id')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['id']] = (int) $row['cnt'];
        }

        foreach ($dossierIds as $id) {
            if (!isset($map[$id])) {
                $map[$id] = 0;
            }
        }

        return $map;
    }

    public function getTextesApplicablesByType(int $dossierId): ?Dossier
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $query = $queryBuilder
            ->addSelect('dta')
            ->addSelect('tta')
            ->join('d.textesApplicables', 'dta')
            ->join('dta.type', 'tta')
            ->where('d.id = :id')
            ->orderBy('tta.id', 'ASC')
            ->addOrderBy('dta.num', 'ASC')
            ->setParameter('id', $dossierId)
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * Retourne les dossiers d'un établissement groupés par catégorie (études, visites, autres),
     * en excluant le dossier courant et les dossiers supprimés.
     *
     * @return array{etudes: Dossier[], visites: Dossier[], autres: Dossier[]}
     */
    public function findByEtablissementGrouped(Etablissement $etablissement, Dossier $excludeDossier): array
    {
        $all = $this->createQueryBuilder('d')
            ->join('d.etablissements', 'e')
            ->join('d.type', 't')
            ->addSelect('t')
            ->leftJoin('d.nature', 'n')
            ->addSelect('n')
            ->leftJoin('d.avis', 'a')
            ->addSelect('a')
            ->leftJoin('d.consultationPlatau', 'cp')
            ->addSelect('cp')
            ->where('e.id = :etablissementId')
            ->andWhere('d.id != :excludeId')
            ->andWhere('d.dateSuppression IS NULL')
            ->setParameter('etablissementId', $etablissement->getId())
            ->setParameter('excludeId', $excludeDossier->getId())
            ->getQuery()
            ->getResult();

        $etudes = [];
        $visites = [];
        $autres = [];

        foreach ($all as $dossier) {
            $typeId = null !== $dossier->getType() ? $dossier->getType()->getId() : null;

            if (DossierType::ETUDE === $typeId) {
                $etudes[] = $dossier;
            } elseif (in_array($typeId, DossierType::TYPES_VISITE, true)) {
                $visites[] = $dossier;
            } else {
                $autres[] = $dossier;
            }
        }

        // Legacy sort: visites → COALESCE(dateVisite, dateCommission, dateInsert) DESC
        usort($visites, function (Dossier $a, Dossier $b): int {
            return $this->sortDateVisite($b) <=> $this->sortDateVisite($a);
        });

        // Legacy sort: études → COALESCE(dateCommission, dateInsert) DESC
        usort($etudes, function (Dossier $a, Dossier $b): int {
            return $this->sortDateEtude($b) <=> $this->sortDateEtude($a);
        });

        // Legacy sort: autres → dateInsert DESC
        usort($autres, function (Dossier $a, Dossier $b): int {
            $dateA = null !== $a->getDateInsert() ? $a->getDateInsert()->format('Y-m-d') : '0000-00-00';
            $dateB = null !== $b->getDateInsert() ? $b->getDateInsert()->format('Y-m-d') : '0000-00-00';

            return $dateB <=> $dateA;
        });

        return ['etudes' => $etudes, 'visites' => $visites, 'autres' => $autres];
    }

    /**
     * Date de tri pour les visites : COALESCE(dateVisite, dateCommission, dateInsert).
     */
    private function sortDateVisite(Dossier $dossier): string
    {
        if (null !== $dossier->getDateVisite()) {
            return $dossier->getDateVisite()->format('Y-m-d');
        }

        if (null !== $dossier->getDateCommission()) {
            return substr($dossier->getDateCommission(), 0, 10);
        }

        return null !== $dossier->getDateInsert() ? $dossier->getDateInsert()->format('Y-m-d') : '0000-00-00';
    }

    /**
     * Date de tri pour les études : COALESCE(dateCommission, dateInsert).
     */
    private function sortDateEtude(Dossier $dossier): string
    {
        if (null !== $dossier->getDateCommission()) {
            return substr($dossier->getDateCommission(), 0, 10);
        }

        return null !== $dossier->getDateInsert() ? $dossier->getDateInsert()->format('Y-m-d') : '0000-00-00';
    }

    /**
     * Retourne le dossier avec ses contacts triés par nom.
     */
    public function findContactsOrderedByNom(int $id): Dossier
    {
        return $this->createQueryBuilder('d')
            ->addSelect('c')
            ->addSelect('f')
            ->leftJoin('d.contacts', 'c')
            ->leftJoin('c.fonction', 'f')
            ->where('d.id = :id')
            ->orderBy('c.nom', 'ASC')
            ->addOrderBy('c.prenom', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Construit le SQL de base pour la recherche de dossiers (port de Model_DbTable_Search::setItem('dossier')).
     * Sélectionne tous les champs attendus par les templates de tableau de bord.
     */
    private function buildSearchDossierBaseSql(bool $getCount): string
    {
        if ($getCount) {
            $select = 'SELECT COUNT(DISTINCT d.ID_DOSSIER) as count';
        } else {
            $select = 'SELECT d.*,
                (SELECT COUNT(dossierpj.ID_DOSSIER) FROM dossierpj WHERE dossierpj.ID_DOSSIER = d.ID_DOSSIER) AS NB_PJ,
                (SELECT COUNT(dossierlie.ID_DOSSIER2)
                    FROM dossier
                    INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER1
                    WHERE dossier.ID_DOSSIER = d.ID_DOSSIER) AS NB_DOSS_LIES,
                (SELECT group_concat(dossierdocurba.NUM_DOCURBA, \', \')
                    FROM dossier
                    INNER JOIN dossierdocurba ON dossierdocurba.ID_DOSSIER = dossier.ID_DOSSIER
                    WHERE dossier.ID_DOSSIER = d.ID_DOSSIER LIMIT 1) AS NB_URBA,
                (SELECT COUNT(dossierlie.ID_DOSSIER2)
                    FROM dossier
                    INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER1
                    INNER JOIN dossiernature dn2 ON dossierlie.ID_DOSSIER1 = dn2.ID_DOSSIER
                    WHERE (dn2.ID_NATURE = 2 OR dn2.ID_NATURE = 1 OR dn2.ID_NATURE = 13 OR dn2.ID_NATURE = 12)
                    AND dossier.ID_DOSSIER = d.ID_DOSSIER) AS ALERTE_RECEPTION_TRAVAUX,
                (SELECT COUNT(dossierlie.ID_DOSSIER1)
                    FROM dossier
                    INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER2
                    INNER JOIN dossiernature dn3 ON dossierlie.ID_DOSSIER1 = dn3.ID_DOSSIER
                    WHERE dn3.ID_NATURE = 46
                    AND dossier.ID_DOSSIER = d.ID_DOSSIER) AS ECHEANCIER_TRAVAUX,
                dossierlie.ID_DOSSIER1, dossierlie.ID_DOSSIER2,
                dossiernatureliste.LIBELLE_DOSSIERNATURE, dossiernatureliste.ID_DOSSIERNATURE,
                dossiertype.LIBELLE_DOSSIERTYPE,
                dossierdocurba.NUM_DOCURBA,
                avis.ID_AVIS, avis.LIBELLE_AVIS,
                utilisateur.ID_UTILISATEUR,
                etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS';
        }

        return $select.'
            FROM dossier d
            LEFT JOIN dossierlie ON d.ID_DOSSIER = dossierlie.ID_DOSSIER2
            JOIN dossiernature ON dossiernature.ID_DOSSIER = d.ID_DOSSIER
            JOIN dossiernatureliste ON dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE
            JOIN dossiertype ON dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE
            LEFT JOIN dossierdocurba ON d.ID_DOSSIER = dossierdocurba.ID_DOSSIER
            LEFT JOIN etablissementdossier e ON d.ID_DOSSIER = e.ID_DOSSIER
            LEFT JOIN avis ON d.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS
            LEFT JOIN dossierpreventionniste ON dossierpreventionniste.ID_DOSSIER = d.ID_DOSSIER
            LEFT JOIN utilisateur ON utilisateur.ID_UTILISATEUR = dossierpreventionniste.ID_PREVENTIONNISTE
            LEFT JOIN etablissementinformations ON e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT
                AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = IFNULL(
                    (CASE
                        WHEN d.DATECOMM_DOSSIER IS NOT NULL THEN (
                            SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                            WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                            AND ei.DATE_ETABLISSEMENTINFORMATIONS <= d.DATEINSERT_DOSSIER)
                        WHEN d.DATEVISITE_DOSSIER IS NOT NULL THEN (
                            SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                            WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                            AND ei.DATE_ETABLISSEMENTINFORMATIONS <= d.DATEVISITE_DOSSIER)
                        WHEN d.DATEINSERT_DOSSIER IS NOT NULL THEN (
                            SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                            WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                            AND ei.DATE_ETABLISSEMENTINFORMATIONS <= d.DATEINSERT_DOSSIER)
                        ELSE (
                            SELECT MIN(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                            WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT)
                    END),
                    (SELECT MIN(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                    WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT)
                )
            WHERE d.DATESUPPRESSION_DOSSIER IS NULL';
    }

    /**
     * Retourne les dossiers dont la date de commission est échue et sans avis pour le tableau de bord.
     * Port direct de Model_DbTable_Dossier::listeDesDossierDateCommissionEchu().
     *
     * @param int[] $idsCommission
     *
     * @return array<int, array<string, mixed>>|int
     */
    public function findDossierDateCommissionEchu(array $idsCommission, int $sinceDays = 10, int $untilDays = 100, bool $getCount = false)
    {
        $conn = $this->getEntityManager()->getConnection();

        if ($getCount) {
            $selectCols = 'SELECT COUNT(*) as count';
        } else {
            $selectCols = 'SELECT d.*, dossierlie.*,
                dossiernatureliste.LIBELLE_DOSSIERNATURE, dossiernatureliste.ID_DOSSIERNATURE,
                dossiertype.LIBELLE_DOSSIERTYPE,
                dossierdocurba.NUM_DOCURBA,
                avis.*,
                utilisateur.ID_UTILISATEUR,
                etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS,
                dossieraffectation.*,
                datecommission.*';
        }

        $sql = $selectCols.'
            FROM dossier d
            LEFT JOIN dossierlie ON d.ID_DOSSIER = dossierlie.ID_DOSSIER2
            JOIN dossiernature ON dossiernature.ID_DOSSIER = d.ID_DOSSIER
            JOIN dossiernatureliste ON dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE
            JOIN dossiertype ON dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE
            LEFT JOIN dossierdocurba ON d.ID_DOSSIER = dossierdocurba.ID_DOSSIER
            LEFT JOIN etablissementdossier e ON d.ID_DOSSIER = e.ID_DOSSIER
            LEFT JOIN avis ON d.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS
            LEFT JOIN dossierpreventionniste ON dossierpreventionniste.ID_DOSSIER = d.ID_DOSSIER
            LEFT JOIN utilisateur ON utilisateur.ID_UTILISATEUR = dossierpreventionniste.ID_PREVENTIONNISTE
            LEFT JOIN etablissementinformations ON e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT
                AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei
                    WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                )
            LEFT JOIN dossieraffectation ON dossieraffectation.ID_DOSSIER_AFFECT = d.ID_DOSSIER
            LEFT JOIN datecommission ON dossieraffectation.ID_DATECOMMISSION_AFFECT = datecommission.ID_DATECOMMISSION
            WHERE DATEDIFF(CURDATE(), datecommission.DATE_COMMISSION) >= :sinceDays
            AND DATEDIFF(CURDATE(), datecommission.DATE_COMMISSION) <= :untilDays
            AND (d.AVIS_DOSSIER_COMMISSION IS NULL OR d.AVIS_DOSSIER_COMMISSION = 0)';

        $params = ['sinceDays' => $sinceDays, 'untilDays' => $untilDays];

        if ([] !== $idsCommission) {
            $inList = implode(',', array_map('intval', $idsCommission));
            $sql .= ' AND datecommission.COMMISSION_CONCERNE IN ('.$inList.')';
        }

        if ($getCount) {
            $count = $conn->fetchOne($sql, $params);

            return is_numeric($count) ? (int) $count : 0;
        }

        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY datecommission.DATE_COMMISSION DESC';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, $params);

        return $rows;
    }

    /**
     * Retourne les dossiers avec avis différé pour le tableau de bord.
     * Port direct de Model_DbTable_Dossier::listeDossierAvecAvisDiffere().
     *
     * @param int[] $idsCommission
     *
     * @return array<int, array<string, mixed>>|int
     */
    public function findDossierAvecAvisDiffere(array $idsCommission, bool $getCount = false)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql($getCount);
        $params = [];

        if ([] !== $idsCommission) {
            $inList = implode(',', array_map('intval', $idsCommission));
            $sql .= ' AND d.COMMISSION_DOSSIER IN ('.$inList.')';
        }

        $sql .= ' AND d.DIFFEREAVIS_DOSSIER = 1';

        if ($getCount) {
            $count = $conn->fetchOne($sql, $params);

            return is_numeric($count) ? (int) $count : 0;
        }

        $sql .= ' GROUP BY d.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, $params);

        return $rows;
    }

    /**
     * Retourne les courriers sans réponse pour le tableau de bord.
     * Port direct de Model_DbTable_Dossier::listeDesCourrierSansReponse().
     *
     * @return array<int, array<string, mixed>>|int
     */
    public function findCourrierSansReponse(int $dureeEnJour = 5, bool $getCount = false)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql($getCount);
        $params = ['dureeEnJour' => $dureeEnJour];

        $sql .= ' AND d.TYPE_DOSSIER = 5';
        $sql .= ' AND d.DATEREP_DOSSIER IS NULL';
        $sql .= ' AND d.OBJET_DOSSIER IS NOT NULL';
        $sql .= ' AND DATEDIFF(CURDATE(), d.DATEINSERT_DOSSIER) > :dureeEnJour';

        if ($getCount) {
            $count = $conn->fetchOne($sql, $params);

            return is_numeric($count) ? (int) $count : 0;
        }

        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY d.DATEINSERT_DOSSIER DESC';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, $params);

        return $rows;
    }

    /**
     * Retourne le type d'un dossier (Etude=1 / Visite=2,3 / Courrier=5 etc.).
     * Port direct de Model_DbTable_Dossier::getTypeDossier().
     *
     * @return array<string, mixed>|false
     */
    public function getTypeDossier(int $idDossier)
    {
        return $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT TYPE_DOSSIER FROM dossier WHERE ID_DOSSIER = :id',
            ['id' => $idDossier]
        );
    }

    /**
     * Retourne les dossiers avec délai de prescription à lever, après filtrage PHP sur les dossiers liés.
     * Port direct de Service_Dashboard::getLeveePresc().
     * Les dossiers ayant un dossier lié de nature 7 (levée prescriptions), 19 (levée avis déf.) ou 46 (échéancier travaux) sont exclus.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findLeveePresc(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql(false)
            .' AND d.DELAIPRESC_DOSSIER IS NOT NULL'
            .' GROUP BY d.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $dossiers */
        $dossiers = $conn->fetchAllAssociative($sql, []);

        foreach ($dossiers as $key => $dossier) {
            $idDossier = is_numeric($dossier['ID_DOSSIER']) ? (int) $dossier['ID_DOSSIER'] : 0;

            /** @var array<int, array<string, mixed>> $dossiersLies */
            $dossiersLies = $conn->fetchAllAssociative(
                'SELECT * FROM dossierlie WHERE ID_DOSSIER1 = :id OR ID_DOSSIER2 = :id',
                ['id' => $idDossier]
            );

            foreach ($dossiersLies as $lien) {
                $id1 = is_numeric($lien['ID_DOSSIER1']) ? (int) $lien['ID_DOSSIER1'] : 0;
                $id2 = is_numeric($lien['ID_DOSSIER2']) ? (int) $lien['ID_DOSSIER2'] : 0;
                $idLien = $id1 === $idDossier ? $id2 : $id1;
                $row = $conn->fetchAssociative(
                    'SELECT ID_DOSSIERNATURE, ID_NATURE FROM dossiernature WHERE ID_DOSSIER = :id',
                    ['id' => $idLien]
                );
                if (false !== $row) {
                    $idNature = is_numeric($row['ID_NATURE']) ? (int) $row['ID_NATURE'] : 0;
                    // 7 = levée prescriptions, 19 = levée avis déf., 46 = échéancier travaux
                    if (7 === $idNature || 19 === $idNature || 46 === $idNature) {
                        unset($dossiers[$key]);
                    }
                }
            }
        }

        return array_values($dossiers);
    }

    /**
     * Retourne les dossiers avec absence de quorum, après filtrage PHP sur les dossiers liés.
     * Port direct de Service_Dashboard::getAbsenceQuorum().
     * Les dossiers ayant un dossier lié de type 0 ou 5 (courrier) sont exclus.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAbsenceQuorum(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql(false)
            .' AND d.ABSQUORUM_DOSSIER = 1'
            .' GROUP BY d.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $dossiers */
        $dossiers = $conn->fetchAllAssociative($sql, []);

        foreach ($dossiers as $key => $dossier) {
            $idDossier = is_numeric($dossier['ID_DOSSIER']) ? (int) $dossier['ID_DOSSIER'] : 0;

            /** @var array<int, array<string, mixed>> $dossiersLies */
            $dossiersLies = $conn->fetchAllAssociative(
                'SELECT * FROM dossierlie WHERE ID_DOSSIER1 = :id OR ID_DOSSIER2 = :id',
                ['id' => $idDossier]
            );

            foreach ($dossiersLies as $lien) {
                $id1 = is_numeric($lien['ID_DOSSIER1']) ? (int) $lien['ID_DOSSIER1'] : 0;
                $id2 = is_numeric($lien['ID_DOSSIER2']) ? (int) $lien['ID_DOSSIER2'] : 0;
                $idLien = $id1 === $idDossier ? $id2 : $id1;
                $tabType = $this->getTypeDossier($idLien);
                if (false !== $tabType) {
                    $typeDossier = is_numeric($tabType['TYPE_DOSSIER']) ? (int) $tabType['TYPE_DOSSIER'] : 0;
                    // 5 = ID_DOSSIERTYPE_COURRIER
                    if (0 === $typeDossier || 5 === $typeDossier) {
                        unset($dossiers[$key]);
                    }
                }
            }
        }

        return array_values($dossiers);
    }

    /**
     * Retourne les dossiers NPSP (Ne Peut Se Prononcer), après filtrage PHP sur les dossiers liés.
     * Port direct de Service_Dashboard::getNpsp().
     * Les dossiers ayant un dossier lié de type 0 ou 5 (courrier) sont exclus.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findNpsp(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql(false)
            .' AND d.NPSP_DOSSIER = 1'
            .' GROUP BY d.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $dossiers */
        $dossiers = $conn->fetchAllAssociative($sql, []);

        foreach ($dossiers as $key => $dossier) {
            $idDossier = is_numeric($dossier['ID_DOSSIER']) ? (int) $dossier['ID_DOSSIER'] : 0;

            /** @var array<int, array<string, mixed>> $dossiersLies */
            $dossiersLies = $conn->fetchAllAssociative(
                'SELECT * FROM dossierlie WHERE ID_DOSSIER1 = :id OR ID_DOSSIER2 = :id',
                ['id' => $idDossier]
            );

            foreach ($dossiersLies as $lien) {
                $id1 = is_numeric($lien['ID_DOSSIER1']) ? (int) $lien['ID_DOSSIER1'] : 0;
                $id2 = is_numeric($lien['ID_DOSSIER2']) ? (int) $lien['ID_DOSSIER2'] : 0;
                $idLien = $id1 === $idDossier ? $id2 : $id1;
                $tabType = $this->getTypeDossier($idLien);
                if (false !== $tabType) {
                    $typeDossier = is_numeric($tabType['TYPE_DOSSIER']) ? (int) $tabType['TYPE_DOSSIER'] : 0;
                    // 5 = ID_DOSSIERTYPE_COURRIER
                    if (0 === $typeDossier || 5 === $typeDossier) {
                        unset($dossiers[$key]);
                    }
                }
            }
        }

        return array_values($dossiers);
    }

    /**
     * Retourne les dossiers incomplets pour le tableau de bord.
     * Port direct de Service_Dashboard::getDossiersIncomplets().
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossiersIncomplets(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql(false)
            .' AND d.INCOMPLET_DOSSIER = 1'
            .' GROUP BY d.ID_DOSSIER'
            .' ORDER BY d.DATEINSERT_DOSSIER DESC';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, []);

        return $rows;
    }

    /**
     * Retourne les dossiers suivis par un utilisateur et non verrouillés.
     * Port direct de Service_Dashboard::getDossiersSuivisNonVerrouilles().
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossiersSuivisNonVerrouilles(int $userId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql(false);
        $sql .= ' AND utilisateur.ID_UTILISATEUR = :userId';
        $sql .= ' AND d.VERROU_DOSSIER = 0';
        $sql .= ' GROUP BY d.ID_DOSSIER';
        $sql .= ' ORDER BY IFNULL(d.DATEVISITE_DOSSIER, d.DATEINSERT_DOSSIER) DESC';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['userId' => $userId]);

        return $rows;
    }

    /**
     * Retourne les dossiers suivis par un utilisateur et sans avis du rapporteur.
     * Port direct de Service_Dashboard::getDossiersSuivisSansAvis().
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossiersSuivisSansAvis(int $userId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql(false);
        $sql .= ' AND utilisateur.ID_UTILISATEUR = :userId';
        $sql .= ' AND ((d.AVIS_DOSSIER IS NULL AND (d.AVIS_DOSSIER_COMMISSION IS NULL OR d.AVIS_DOSSIER_COMMISSION = 0) AND d.TYPE_DOSSIER = 1)'
            .' OR (d.DATEREP_DOSSIER IS NULL AND d.TYPE_DOSSIER = 5))';
        $sql .= ' GROUP BY d.ID_DOSSIER';
        $sql .= ' ORDER BY d.DATEINSERT_DOSSIER DESC';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['userId' => $userId]);

        return $rows;
    }

    /**
     * Construit le fragment SQL SELECT+FROM+JOINs pour les dossiers Plat'AU.
     * Identique à buildSearchDossierBaseSql mais avec des joins Plat'AU supplémentaires insérés
     * avant la clause WHERE.
     *
     * @param string $extraJoins      JOINs Plat'AU supplémentaires à insérer avant WHERE
     * @param string $extraSelectCols Colonnes supplémentaires à insérer dans le SELECT (avec virgule de tête)
     */
    private function buildPlatauDossierSql(string $extraJoins, string $extraSelectCols): string
    {
        return 'SELECT d.*,
                (SELECT COUNT(dossierpj.ID_DOSSIER) FROM dossierpj WHERE dossierpj.ID_DOSSIER = d.ID_DOSSIER) AS NB_PJ,
                (SELECT COUNT(dossierlie.ID_DOSSIER2)
                    FROM dossier
                    INNER JOIN dossierlie ON dossier.ID_DOSSIER = dossierlie.ID_DOSSIER1
                    WHERE dossier.ID_DOSSIER = d.ID_DOSSIER) AS NB_DOSS_LIES,
                (SELECT group_concat(dossierdocurba.NUM_DOCURBA, \', \')
                    FROM dossier
                    INNER JOIN dossierdocurba ON dossierdocurba.ID_DOSSIER = dossier.ID_DOSSIER
                    WHERE dossier.ID_DOSSIER = d.ID_DOSSIER LIMIT 1) AS NB_URBA,
                dossierlie.ID_DOSSIER1, dossierlie.ID_DOSSIER2,
                dossiernatureliste.LIBELLE_DOSSIERNATURE, dossiernatureliste.ID_DOSSIERNATURE,
                dossiertype.LIBELLE_DOSSIERTYPE,
                dossierdocurba.NUM_DOCURBA,
                avis.ID_AVIS, avis.LIBELLE_AVIS,
                utilisateur.ID_UTILISATEUR,
                etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS'
            .$extraSelectCols.'
            FROM dossier d
            LEFT JOIN dossierlie ON d.ID_DOSSIER = dossierlie.ID_DOSSIER2
            JOIN dossiernature ON dossiernature.ID_DOSSIER = d.ID_DOSSIER
            JOIN dossiernatureliste ON dossiernatureliste.ID_DOSSIERNATURE = dossiernature.ID_NATURE
            JOIN dossiertype ON dossiertype.ID_DOSSIERTYPE = dossiernatureliste.ID_DOSSIERTYPE
            LEFT JOIN dossierdocurba ON d.ID_DOSSIER = dossierdocurba.ID_DOSSIER
            LEFT JOIN etablissementdossier e ON d.ID_DOSSIER = e.ID_DOSSIER
            LEFT JOIN avis ON d.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS
            LEFT JOIN dossierpreventionniste ON dossierpreventionniste.ID_DOSSIER = d.ID_DOSSIER
            LEFT JOIN utilisateur ON utilisateur.ID_UTILISATEUR = dossierpreventionniste.ID_PREVENTIONNISTE
            LEFT JOIN etablissementinformations ON e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT
                AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = IFNULL(
                    (CASE
                        WHEN d.DATECOMM_DOSSIER IS NOT NULL THEN (
                            SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                            WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                            AND ei.DATE_ETABLISSEMENTINFORMATIONS <= d.DATEINSERT_DOSSIER)
                        WHEN d.DATEVISITE_DOSSIER IS NOT NULL THEN (
                            SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                            WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                            AND ei.DATE_ETABLISSEMENTINFORMATIONS <= d.DATEVISITE_DOSSIER)
                        WHEN d.DATEINSERT_DOSSIER IS NOT NULL THEN (
                            SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                            WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                            AND ei.DATE_ETABLISSEMENTINFORMATIONS <= d.DATEINSERT_DOSSIER)
                        ELSE (
                            SELECT MIN(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                            WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT)
                    END),
                    (SELECT MIN(ei.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations ei
                    WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT)
                )'
            .$extraJoins.'
            WHERE d.DATESUPPRESSION_DOSSIER IS NULL';
    }

    /**
     * Retourne les dossiers Plat'AU non associés à un établissement.
     * Port direct de Service_Dashboard::getDossiersPlatAUSansEtablissement().
     *
     * @param \DateTimeInterface $lastVisitDate Date de dernière visite de la page (pour IS_NEW)
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossiersPlatauSansEtablissement(\DateTimeInterface $lastVisitDate): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $lastVisitDateStr = $lastVisitDate->format('Y-m-d H:i:s');

        $extraJoins = '
            JOIN platauconsultation ON platauconsultation.ID_PLATAU = d.ID_PLATAU
            LEFT JOIN dossierpj ON dossierpj.ID_DOSSIER = d.ID_DOSSIER
            LEFT JOIN piecejointe ON piecejointe.ID_PIECEJOINTE = dossierpj.ID_PIECEJOINTE';

        $extraSelectCols = ',
            platauconsultation.DATE_REPONSE_ATTENDUE,
            IF(d.DATE_NOTIFICATION IS NOT NULL AND d.DATE_NOTIFICATION >= :lastVisitDate, 1, 0) AS IS_NEW,
            IF(piecejointe.DATE_NOTIFICATION IS NOT NULL AND piecejointe.DATE_NOTIFICATION >= :lastVisitDate, 1, 0) AS HAS_NEW_PJ';

        $sql = $this->buildPlatauDossierSql($extraJoins, $extraSelectCols);
        $sql .= ' AND d.ID_PLATAU IS NOT NULL';
        $sql .= ' AND e.ID_DOSSIER IS NULL';
        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY d.DATEINSERT_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['lastVisitDate' => $lastVisitDateStr]);

        return $rows;
    }

    /**
     * Retourne les dossiers Plat'AU avec des pièces jointes en erreur d'envoi.
     * Port direct de Service_Dashboard::getDossiersPlatauPjsEnErreur().
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossiersPlatauPjsEnErreur(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $extraJoins = '
            JOIN platauconsultation ON platauconsultation.ID_PLATAU = d.ID_PLATAU
            JOIN dossierpj ON dossierpj.ID_DOSSIER = d.ID_DOSSIER
            JOIN piecejointe ON piecejointe.ID_PIECEJOINTE = dossierpj.ID_PIECEJOINTE
            JOIN piecejointestatut ON piecejointestatut.ID_PIECEJOINTESTATUT = piecejointe.ID_PIECEJOINTESTATUT';

        $extraSelectCols = ', platauconsultation.DATE_REPONSE_ATTENDUE, piecejointe.ID_PIECEJOINTESTATUT';

        $sql = $this->buildPlatauDossierSql($extraJoins, $extraSelectCols);
        $sql .= ' AND d.ID_PLATAU IS NOT NULL';
        $sql .= ' AND d.ID_DOSSIER = e.ID_DOSSIER';
        $sql .= " AND platauconsultation.STATUT_AVIS = 'treated'";
        $sql .= " AND piecejointestatut.NOM_STATUT IN ('to_be_exported', 'on_error', 'awaiting_status')";
        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY d.DATEINSERT_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, []);

        return $rows;
    }

    /**
     * Retourne les dossiers Plat'AU avec de nouvelles pièces jointes.
     * Port direct de Service_Dashboard::getDossiersPlatauNouvellesPjs().
     *
     * @param \DateTimeInterface $lastPiecesVisitDate Date de dernière visite de la page pièces jointes
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossiersPlatauNouvellesPjs(\DateTimeInterface $lastPiecesVisitDate): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $lastPiecesVisitDateStr = $lastPiecesVisitDate->format('Y-m-d H:i:s');

        $extraJoins = '
            JOIN platauconsultation ON platauconsultation.ID_PLATAU = d.ID_PLATAU
            JOIN dossierpj ON dossierpj.ID_DOSSIER = d.ID_DOSSIER
            JOIN piecejointe ON piecejointe.ID_PIECEJOINTE = dossierpj.ID_PIECEJOINTE';

        $extraSelectCols = ', platauconsultation.DATE_REPONSE_ATTENDUE';

        $sql = $this->buildPlatauDossierSql($extraJoins, $extraSelectCols);
        $sql .= ' AND d.ID_PLATAU IS NOT NULL';
        $sql .= ' AND d.ID_DOSSIER = e.ID_DOSSIER';
        $sql .= ' AND piecejointe.DATE_NOTIFICATION >= :lastPiecesVisitDate';
        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY d.DATEINSERT_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['lastPiecesVisitDate' => $lastPiecesVisitDateStr]);

        return $rows;
    }

    /**
     * Retourne les liaisons d'un dossier (dossier lié).
     * Port direct de Model_DbTable_DossierLie::getDossierLie().
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossiersLies(int $idDossier): array
    {
        $conn = $this->getEntityManager()->getConnection();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative(
            'SELECT * FROM dossierlie WHERE ID_DOSSIER1 = :id OR ID_DOSSIER2 = :id',
            ['id' => $idDossier]
        );

        return $rows;
    }

    /**
     * Retourne l'identifiant de la nature d'un dossier.
     * Port direct de Model_DbTable_DossierNature::getDossierNaturesId().
     */
    public function getDossierNatureId(int $idDossier): ?int
    {
        $conn = $this->getEntityManager()->getConnection();
        $row = $conn->fetchAssociative(
            'SELECT ID_NATURE FROM dossiernature WHERE id_dossier = :id',
            ['id' => $idDossier]
        );

        if (!is_array($row) || !isset($row['ID_NATURE'])) {
            return null;
        }

        return is_numeric($row['ID_NATURE']) ? (int) $row['ID_NATURE'] : null;
    }

    /**
     * Retourne le type d'un dossier (Etude=1/Visite=2,3/Courrier=5…).
     * Port direct de Model_DbTable_Dossier::getTypeDossier() — retourne int ou null.
     */
    public function getDossierType(int $idDossier): ?int
    {
        $row = $this->getTypeDossier($idDossier);

        if (!is_array($row) || !isset($row['TYPE_DOSSIER'])) {
            return null;
        }

        return is_numeric($row['TYPE_DOSSIER']) ? (int) $row['TYPE_DOSSIER'] : null;
    }

    /**
     * Compte les dossiers suivis non verrouillés pour un utilisateur.
     */
    public function countDossiersSuivisNonVerrouilles(int $userId): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql(true);
        $sql .= ' AND utilisateur.ID_UTILISATEUR = :userId';
        $sql .= ' AND d.VERROU_DOSSIER = 0';
        $count = $conn->fetchOne($sql, ['userId' => $userId]);

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * Compte les dossiers suivis sans avis pour un utilisateur.
     */
    public function countDossiersSuivisSansAvis(int $userId): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql(true);
        $sql .= ' AND utilisateur.ID_UTILISATEUR = :userId';
        $sql .= ' AND ((d.AVIS_DOSSIER IS NULL AND (d.AVIS_DOSSIER_COMMISSION IS NULL OR d.AVIS_DOSSIER_COMMISSION = 0) AND d.TYPE_DOSSIER = 1)'
            .' OR (d.DATEREP_DOSSIER IS NULL AND d.TYPE_DOSSIER = 5))';
        $count = $conn->fetchOne($sql, ['userId' => $userId]);

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * Compte les dossiers avec date de commission échue pour le tableau de bord.
     *
     * @param int[] $idsCommission
     */
    public function countDossierDateCommissionEchu(array $idsCommission, int $sinceDays = 10, int $untilDays = 100): int
    {
        $result = $this->findDossierDateCommissionEchu($idsCommission, $sinceDays, $untilDays, true);

        return is_int($result) ? $result : 0;
    }

    /**
     * Compte les dossiers avec avis différé pour le tableau de bord.
     *
     * @param int[] $idsCommission
     */
    public function countDossierAvecAvisDiffere(array $idsCommission): int
    {
        $result = $this->findDossierAvecAvisDiffere($idsCommission, true);

        return is_int($result) ? $result : 0;
    }

    /**
     * Compte les courriers sans réponse.
     */
    public function countCourrierSansReponse(int $dureeEnJour = 5): int
    {
        $result = $this->findCourrierSansReponse($dureeEnJour, true);

        return is_int($result) ? $result : 0;
    }

    /**
     * Compte les dossiers incomplets.
     */
    public function countDossiersIncomplets(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildSearchDossierBaseSql(true)
            .' AND d.INCOMPLET_DOSSIER = 1';
        $count = $conn->fetchOne($sql, []);

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * Trouve la dernière visite périodique (nature 21 ou 26) d'un établissement.
     *
     * Utilisée lors de la création d'un nouveau dossier VP pour copier les documents
     * consultés et ajoutés de la visite précédente.
     */
    public function findDerniereVP(Etablissement $etablissement): ?Dossier
    {
        return $this->createQueryBuilder('d')
            ->join('d.etablissements', 'e')
            ->join('d.nature', 'n')
            ->where('e.id = :idEtablissement')
            ->andWhere('n.id IN (:natures)')
            ->setParameter('idEtablissement', $etablissement->getId())
            ->setParameter('natures', DossierNature::VISITE_PERIODIQUE)
            ->orderBy('d.dateVisite', 'DESC')
            ->addOrderBy('d.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

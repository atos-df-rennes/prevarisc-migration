<?php

namespace App\Repository;

use App\Constant\DossierNature;
use App\Constant\DossierType;
use App\Dto\Repository\DossierDashboardDto;
use App\Dto\Repository\DossierDateCommissionEchuDto;
use App\Dto\Repository\DossierPlatauDto;
use App\Entity\Dossier;
use App\Entity\DossierAffectation;
use App\Entity\Etablissement;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
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
            ->leftJoin('d.etablissements', 'e')
            ->leftJoin('e.informations', 'ei')
            ->where($queryBuilder->expr()->isNotNull('d.dateSuppression'))
            ->andWhere('e IS NULL OR ei.date = (SELECT MAX(etabInfos.date) from App\Entity\EtablissementInformations etabInfos where etabInfos.etablissement = e)')
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
            ->where('e.id = :id')
            ->andWhere($qb->expr()->isNull('d.dateSuppression'));
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
            ->where('e.id = :id')
            ->andWhere($qb->expr()->isNull('d.dateSuppression'));

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
     *
     * ⚠️ Méthode réservée aux requêtes de données (find*). Les comptages dashboard utilisent
     * des méthodes count* dédiées (QueryBuilder lean) pour éviter le chargement de toutes les données.
     */
    private function buildSearchDossierBaseSql(): string
    {
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
                e.ID_ETABLISSEMENT,
                etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS,
                etablissementinformations.ID_TYPE,
                type.LIBELLE_TYPE,
                genre.LIBELLE_GENRE,
                platauconsultation.DATE_REPONSE_ATTENDUE';

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
            LEFT JOIN type ON etablissementinformations.ID_TYPE = type.ID_TYPE
            LEFT JOIN genre ON etablissementinformations.ID_GENRE = genre.ID_GENRE
            LEFT JOIN platauconsultation ON d.ID_PLATAU = platauconsultation.ID_PLATAU
            WHERE d.DATESUPPRESSION_DOSSIER IS NULL';
    }

    /**
     * Retourne les dossiers dont la date de commission est échue et sans avis pour le tableau de bord.
     * Port direct de Model_DbTable_Dossier::listeDesDossierDateCommissionEchu().
     *
     * @param int[] $idsCommission
     *
     * @return DossierDateCommissionEchuDto[]
     */
    public function findDossierDateCommissionEchu(array $idsCommission, int $sinceDays = 10, int $untilDays = 100): array
    {
        $extraColumns = [
            'ID_DOSSIER1', 'ID_DOSSIER2',
            'LIBELLE_DOSSIERNATURE', 'ID_DOSSIERNATURE',
            'LIBELLE_DOSSIERTYPE',
            'NUM_DOCURBA',
            'ID_AVIS', 'LIBELLE_AVIS',
            'ID_UTILISATEUR',
            'ID_ETABLISSEMENT',
            'LIBELLE_ETABLISSEMENTINFORMATIONS', 'ID_TYPE',
            'LIBELLE_TYPE',
            'LIBELLE_GENRE',
            'DATE_REPONSE_ATTENDUE',
            'HEURE_DEB_AFFECT', 'HEURE_FIN_AFFECT', 'NUM_DOSSIER',
            'ID_DATECOMMISSION_AFFECT', 'ID_DOSSIER_AFFECT',
            'ID_DATECOMMISSION', 'DATE_COMMISSION', 'HEUREDEB_COMMISSION', 'HEUREFIN_COMMISSION',
            'LIBELLE_DATECOMMISSION', 'GESTION_HEURES', 'DATECOMMISSION_LIEES',
            'ID_COMMISSIONTYPEEVENEMENT', 'COMMISSION_CONCERNE',
        ];
        $rsm = $this->buildNativeQueryRsm($extraColumns);

        $sql = 'SELECT d.*, dossierlie.*,
                dossiernatureliste.LIBELLE_DOSSIERNATURE, dossiernatureliste.ID_DOSSIERNATURE,
                dossiertype.LIBELLE_DOSSIERTYPE,
                dossierdocurba.NUM_DOCURBA,
                avis.*,
                utilisateur.ID_UTILISATEUR,
                e.ID_ETABLISSEMENT,
                etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS,
                etablissementinformations.ID_TYPE,
                type.LIBELLE_TYPE,
                genre.LIBELLE_GENRE,
                platauconsultation.DATE_REPONSE_ATTENDUE,
                dossieraffectation.*,
                datecommission.*
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
            LEFT JOIN type ON etablissementinformations.ID_TYPE = type.ID_TYPE
            LEFT JOIN genre ON etablissementinformations.ID_GENRE = genre.ID_GENRE
            LEFT JOIN platauconsultation ON d.ID_PLATAU = platauconsultation.ID_PLATAU
            LEFT JOIN dossieraffectation ON dossieraffectation.ID_DOSSIER_AFFECT = d.ID_DOSSIER
            LEFT JOIN datecommission ON dossieraffectation.ID_DATECOMMISSION_AFFECT = datecommission.ID_DATECOMMISSION
            WHERE DATEDIFF(CURDATE(), datecommission.DATE_COMMISSION) >= :sinceDays
            AND DATEDIFF(CURDATE(), datecommission.DATE_COMMISSION) <= :untilDays
            AND (d.AVIS_DOSSIER_COMMISSION IS NULL OR d.AVIS_DOSSIER_COMMISSION = 0)';

        if ([] !== $idsCommission) {
            $inList = implode(',', array_map('intval', $idsCommission));
            $sql .= ' AND datecommission.COMMISSION_CONCERNE IN ('.$inList.')';
        }

        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY datecommission.DATE_COMMISSION DESC';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('sinceDays', $sinceDays);
        $query->setParameter('untilDays', $untilDays);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $query->getResult();

        return array_map(static function (array $row): DossierDateCommissionEchuDto {
            return DossierDateCommissionEchuDto::fromArray($row);
        }, $rows);
    }

    /**
     * Retourne les dossiers avec avis différé pour le tableau de bord.
     * Port direct de Model_DbTable_Dossier::listeDossierAvecAvisDiffere().
     *
     * @param int[] $idsCommission
     *
     * @return DossierDashboardDto[]
     */
    public function findDossierAvecAvisDiffere(array $idsCommission): array
    {
        $rsm = $this->buildDashboardResultSetMapping();
        $sql = $this->buildSearchDossierBaseSql();

        if ([] !== $idsCommission) {
            $inList = implode(',', array_map('intval', $idsCommission));
            $sql .= ' AND d.COMMISSION_DOSSIER IN ('.$inList.')';
        }

        $sql .= ' AND d.DIFFEREAVIS_DOSSIER = 1';

        $sql .= ' GROUP BY d.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->getEntityManager()->createNativeQuery($sql, $rsm)->getResult();

        return array_map(static function (array $row): DossierDashboardDto {
            return DossierDashboardDto::fromArray($row);
        }, $rows);
    }

    /**
     * Retourne les courriers sans réponse pour le tableau de bord.
     * Port direct de Model_DbTable_Dossier::listeDesCourrierSansReponse().
     *
     * @return DossierDashboardDto[]
     */
    public function findCourrierSansReponse(int $dureeEnJour = 5): array
    {
        $rsm = $this->buildDashboardResultSetMapping();
        $sql = $this->buildSearchDossierBaseSql();

        $sql .= ' AND d.TYPE_DOSSIER = 5';
        $sql .= ' AND d.DATEREP_DOSSIER IS NULL';
        $sql .= ' AND d.OBJET_DOSSIER IS NOT NULL';
        $sql .= ' AND DATEDIFF(CURDATE(), d.DATEINSERT_DOSSIER) > :dureeEnJour';

        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY d.DATEINSERT_DOSSIER DESC';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('dureeEnJour', $dureeEnJour);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $query->getResult();

        return array_map(static function (array $row): DossierDashboardDto {
            return DossierDashboardDto::fromArray($row);
        }, $rows);
    }

    /**
     * Retourne le type d'un dossier (Etude=1 / Visite=2,3 / Courrier=5 etc.).
     * Port direct de Model_DbTable_Dossier::getTypeDossier().
     *
     * @return array<string, mixed>|false
     */
    public function getTypeDossier(int $idDossier)
    {
        $result = $this->createQueryBuilder('d')
            ->select('IDENTITY(d.type) as TYPE_DOSSIER')
            ->where('d.id = :id')
            ->setParameter('id', $idDossier)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $result) {
            return false;
        }

        /* @var array<string, mixed> $result */
        return $result;
    }

    /**
     * Retourne les dossiers avec délai de prescription à lever.
     * Port direct de Service_Dashboard::getLeveePresc().
     * Les dossiers ayant un dossier lié de nature 7 (levée prescriptions), 19 (levée avis déf.) ou 46 (échéancier travaux) sont exclus.
     * Le filtrage est intégré directement en SQL via NOT EXISTS (UNION ALL sur les deux sens du lien)
     * pour éviter les requêtes N+1.
     *
     * @return DossierDashboardDto[]
     */
    public function findLeveePresc(): array
    {
        $natures = DossierNature::LEVEE_PRESCRIPTIONS.', '.DossierNature::LEVEE_AVIS_DEFAVORABLE.', '.DossierNature::ECHEANCIER_TRAVAUX;
        $rsm = $this->buildDashboardResultSetMapping();
        $sql = $this->buildSearchDossierBaseSql()
            .' AND d.DELAIPRESC_DOSSIER IS NOT NULL'
            .$this->buildNotExistsDossierLieParNatureSql($natures)
            .' GROUP BY d.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $dossiers */
        $dossiers = $this->getEntityManager()->createNativeQuery($sql, $rsm)->getResult();

        return array_map(static function (array $row): DossierDashboardDto {
            return DossierDashboardDto::fromArray($row);
        }, $dossiers);
    }

    /**
     * Retourne les dossiers avec absence de quorum.
     * Port direct de Service_Dashboard::getAbsenceQuorum().
     * Les dossiers ayant un dossier lié de type 0 ou 5 (courrier) sont exclus.
     * Le filtrage est intégré directement en SQL via NOT EXISTS (UNION ALL sur les deux sens du lien)
     * pour éviter les requêtes N+1.
     *
     * @return DossierDashboardDto[]
     */
    public function findAbsenceQuorum(): array
    {
        $types = '0, '.DossierType::COURRIER;
        $rsm = $this->buildDashboardResultSetMapping();
        $sql = $this->buildSearchDossierBaseSql()
            .' AND d.ABSQUORUM_DOSSIER = 1'
            .$this->buildNotExistsDossierLieParTypeSql($types)
            .' GROUP BY d.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $dossiers */
        $dossiers = $this->getEntityManager()->createNativeQuery($sql, $rsm)->getResult();

        return array_map(static function (array $row): DossierDashboardDto {
            return DossierDashboardDto::fromArray($row);
        }, $dossiers);
    }

    /**
     * Retourne les dossiers NPSP (Ne Peut Se Prononcer).
     * Port direct de Service_Dashboard::getNpsp().
     * Les dossiers ayant un dossier lié de type 0 ou 5 (courrier) sont exclus.
     * Le filtrage est intégré directement en SQL via NOT EXISTS (UNION ALL sur les deux sens du lien)
     * pour éviter les requêtes N+1.
     *
     * @return DossierDashboardDto[]
     */
    public function findNpsp(): array
    {
        $types = '0, '.DossierType::COURRIER;
        $rsm = $this->buildDashboardResultSetMapping();
        $sql = $this->buildSearchDossierBaseSql()
            .' AND d.NPSP_DOSSIER = 1'
            .$this->buildNotExistsDossierLieParTypeSql($types)
            .' GROUP BY d.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $dossiers */
        $dossiers = $this->getEntityManager()->createNativeQuery($sql, $rsm)->getResult();

        return array_map(static function (array $row): DossierDashboardDto {
            return DossierDashboardDto::fromArray($row);
        }, $dossiers);
    }

    /**
     * Génère la clause NOT EXISTS bidirectionnelle sur dossierlie filtrée par type de dossier.
     * Couvre les deux sens de la relation (ID_DOSSIER1 et ID_DOSSIER2) via UNION ALL.
     *
     * @param string $inClause Valeurs SQL pour IN (...), ex: "0, 5"
     */
    private function buildNotExistsDossierLieParTypeSql(string $inClause): string
    {
        return ' AND NOT EXISTS ('
            .'     SELECT 1 FROM dossierlie dl'
            .'     JOIN dossier d_lie ON dl.ID_DOSSIER2 = d_lie.ID_DOSSIER'
            .'     WHERE dl.ID_DOSSIER1 = d.ID_DOSSIER'
            .'       AND d_lie.TYPE_DOSSIER IN ('.$inClause.')'
            .'     UNION ALL'
            .'     SELECT 1 FROM dossierlie dl2'
            .'     JOIN dossier d_lie2 ON dl2.ID_DOSSIER1 = d_lie2.ID_DOSSIER'
            .'     WHERE dl2.ID_DOSSIER2 = d.ID_DOSSIER'
            .'       AND d_lie2.TYPE_DOSSIER IN ('.$inClause.')'
            .' )';
    }

    /**
     * Génère la clause NOT EXISTS bidirectionnelle sur dossierlie filtrée par nature de dossier.
     * Couvre les deux sens de la relation (ID_DOSSIER1 et ID_DOSSIER2) via UNION ALL.
     *
     * @param string $inClause Valeurs SQL pour IN (...), ex: "7, 19, 46"
     */
    private function buildNotExistsDossierLieParNatureSql(string $inClause): string
    {
        return ' AND NOT EXISTS ('
            .'     SELECT 1 FROM dossierlie dl'
            .'     JOIN dossiernature dn_lie ON dl.ID_DOSSIER2 = dn_lie.ID_DOSSIER'
            .'     WHERE dl.ID_DOSSIER1 = d.ID_DOSSIER'
            .'       AND dn_lie.ID_NATURE IN ('.$inClause.')'
            .'     UNION ALL'
            .'     SELECT 1 FROM dossierlie dl2'
            .'     JOIN dossiernature dn_lie2 ON dl2.ID_DOSSIER1 = dn_lie2.ID_DOSSIER'
            .'     WHERE dl2.ID_DOSSIER2 = d.ID_DOSSIER'
            .'       AND dn_lie2.ID_NATURE IN ('.$inClause.')'
            .' )';
    }

    /**
     * Retourne les dossiers incomplets pour le tableau de bord.
     * Port direct de Service_Dashboard::getDossiersIncomplets().
     *
     * @return DossierDashboardDto[]
     */
    public function findDossiersIncomplets(): array
    {
        $rsm = $this->buildDashboardResultSetMapping();
        $sql = $this->buildSearchDossierBaseSql()
            .' AND d.INCOMPLET_DOSSIER = 1'
            .' GROUP BY d.ID_DOSSIER'
            .' ORDER BY d.DATEINSERT_DOSSIER DESC';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->getEntityManager()->createNativeQuery($sql, $rsm)->getResult();

        return array_map(static function (array $row): DossierDashboardDto {
            return DossierDashboardDto::fromArray($row);
        }, $rows);
    }

    /**
     * Retourne les dossiers suivis par un utilisateur et non verrouillés.
     * Port direct de Service_Dashboard::getDossiersSuivisNonVerrouilles().
     *
     * @return DossierDashboardDto[]
     */
    public function findDossiersSuivisNonVerrouilles(int $userId): array
    {
        $rsm = $this->buildDashboardResultSetMapping();
        $sql = $this->buildSearchDossierBaseSql();
        $sql .= ' AND utilisateur.ID_UTILISATEUR = :userId';
        $sql .= ' AND d.VERROU_DOSSIER = 0';
        $sql .= ' GROUP BY d.ID_DOSSIER';
        $sql .= ' ORDER BY IFNULL(d.DATEVISITE_DOSSIER, d.DATEINSERT_DOSSIER) DESC';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $userId);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $query->getResult();

        return array_map(static function (array $row): DossierDashboardDto {
            return DossierDashboardDto::fromArray($row);
        }, $rows);
    }

    /**
     * Retourne les dossiers suivis par un utilisateur et sans avis du rapporteur.
     * Port direct de Service_Dashboard::getDossiersSuivisSansAvis().
     *
     * @return DossierDashboardDto[]
     */
    public function findDossiersSuivisSansAvis(int $userId): array
    {
        $rsm = $this->buildDashboardResultSetMapping();
        $sql = $this->buildSearchDossierBaseSql();
        $sql .= ' AND utilisateur.ID_UTILISATEUR = :userId';
        $sql .= ' AND ((d.AVIS_DOSSIER IS NULL AND (d.AVIS_DOSSIER_COMMISSION IS NULL OR d.AVIS_DOSSIER_COMMISSION = 0) AND d.TYPE_DOSSIER = 1)'
            .' OR (d.DATEREP_DOSSIER IS NULL AND d.TYPE_DOSSIER = 5))';
        $sql .= ' GROUP BY d.ID_DOSSIER';
        $sql .= ' ORDER BY d.DATEINSERT_DOSSIER DESC';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $userId);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $query->getResult();

        return array_map(static function (array $row): DossierDashboardDto {
            return DossierDashboardDto::fromArray($row);
        }, $rows);
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
                e.ID_ETABLISSEMENT,
                etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS,
                etablissementinformations.ID_TYPE,
                type.LIBELLE_TYPE,
                genre.LIBELLE_GENRE'
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
                )
            LEFT JOIN type ON etablissementinformations.ID_TYPE = type.ID_TYPE
            LEFT JOIN genre ON etablissementinformations.ID_GENRE = genre.ID_GENRE'
            .$extraJoins.'
            WHERE d.DATESUPPRESSION_DOSSIER IS NULL';
    }

    /**
     * Retourne les dossiers Plat'AU non associés à un établissement.
     * Port direct de Service_Dashboard::getDossiersPlatAUSansEtablissement().
     *
     * @param \DateTimeInterface $lastVisitDate Date de dernière visite de la page (pour IS_NEW)
     *
     * @return DossierPlatauDto[]
     */
    public function findDossiersPlatauSansEtablissement(\DateTimeInterface $lastVisitDate): array
    {
        $lastVisitDateStr = $lastVisitDate->format('Y-m-d H:i:s');

        $extraJoins = '
            JOIN platauconsultation ON platauconsultation.ID_PLATAU = d.ID_PLATAU
            LEFT JOIN dossierpj ON dossierpj.ID_DOSSIER = d.ID_DOSSIER
            LEFT JOIN piecejointe ON piecejointe.ID_PIECEJOINTE = dossierpj.ID_PIECEJOINTE';

        $extraSelectCols = ',
            platauconsultation.DATE_REPONSE_ATTENDUE,
            IF(d.DATE_NOTIFICATION IS NOT NULL AND d.DATE_NOTIFICATION >= :lastVisitDate, 1, 0) AS IS_NEW,
            IF(piecejointe.DATE_NOTIFICATION IS NOT NULL AND piecejointe.DATE_NOTIFICATION >= :lastVisitDate, 1, 0) AS HAS_NEW_PJ';

        $rsm = $this->buildDashboardResultSetMapping(['IS_NEW', 'HAS_NEW_PJ']);

        $sql = $this->buildPlatauDossierSql($extraJoins, $extraSelectCols);
        $sql .= ' AND d.ID_PLATAU IS NOT NULL';
        $sql .= ' AND e.ID_DOSSIER IS NULL';
        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY d.DATEINSERT_DOSSIER';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('lastVisitDate', $lastVisitDateStr);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $query->getResult();

        return array_map(static function (array $row): DossierPlatauDto {
            return DossierPlatauDto::fromArray($row);
        }, $rows);
    }

    /**
     * Retourne les dossiers Plat'AU avec des pièces jointes en erreur d'envoi.
     * Port direct de Service_Dashboard::getDossiersPlatauPjsEnErreur().
     *
     * @return DossierPlatauDto[]
     */
    public function findDossiersPlatauPjsEnErreur(): array
    {
        $extraJoins = '
            JOIN platauconsultation ON platauconsultation.ID_PLATAU = d.ID_PLATAU
            JOIN dossierpj ON dossierpj.ID_DOSSIER = d.ID_DOSSIER
            JOIN piecejointe ON piecejointe.ID_PIECEJOINTE = dossierpj.ID_PIECEJOINTE
            JOIN piecejointestatut ON piecejointestatut.ID_PIECEJOINTESTATUT = piecejointe.ID_PIECEJOINTESTATUT';

        $extraSelectCols = ', platauconsultation.DATE_REPONSE_ATTENDUE, piecejointe.ID_PIECEJOINTESTATUT';

        $rsm = $this->buildDashboardResultSetMapping(['ID_PIECEJOINTESTATUT']);

        $sql = $this->buildPlatauDossierSql($extraJoins, $extraSelectCols);
        $sql .= ' AND d.ID_PLATAU IS NOT NULL';
        $sql .= ' AND d.ID_DOSSIER = e.ID_DOSSIER';
        $sql .= " AND platauconsultation.STATUT_AVIS = 'treated'";
        $sql .= " AND piecejointestatut.NOM_STATUT IN ('to_be_exported', 'on_error', 'awaiting_status')";
        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY d.DATEINSERT_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->getEntityManager()->createNativeQuery($sql, $rsm)->getResult();

        return array_map(static function (array $row): DossierPlatauDto {
            return DossierPlatauDto::fromArray($row);
        }, $rows);
    }

    /**
     * Retourne les dossiers Plat'AU avec de nouvelles pièces jointes.
     * Port direct de Service_Dashboard::getDossiersPlatauNouvellesPjs().
     *
     * @param \DateTimeInterface $lastPiecesVisitDate Date de dernière visite de la page pièces jointes
     *
     * @return DossierPlatauDto[]
     */
    public function findDossiersPlatauNouvellesPjs(\DateTimeInterface $lastPiecesVisitDate): array
    {
        $lastPiecesVisitDateStr = $lastPiecesVisitDate->format('Y-m-d H:i:s');

        $extraJoins = '
            JOIN platauconsultation ON platauconsultation.ID_PLATAU = d.ID_PLATAU
            JOIN dossierpj ON dossierpj.ID_DOSSIER = d.ID_DOSSIER
            JOIN piecejointe ON piecejointe.ID_PIECEJOINTE = dossierpj.ID_PIECEJOINTE';

        $extraSelectCols = ', platauconsultation.DATE_REPONSE_ATTENDUE';

        $rsm = $this->buildDashboardResultSetMapping();

        $sql = $this->buildPlatauDossierSql($extraJoins, $extraSelectCols);
        $sql .= ' AND d.ID_PLATAU IS NOT NULL';
        $sql .= ' AND d.ID_DOSSIER = e.ID_DOSSIER';
        $sql .= ' AND piecejointe.DATE_NOTIFICATION >= :lastPiecesVisitDate';
        $sql .= ' GROUP BY d.ID_DOSSIER ORDER BY d.DATEINSERT_DOSSIER';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('lastPiecesVisitDate', $lastPiecesVisitDateStr);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $query->getResult();

        return array_map(static function (array $row): DossierPlatauDto {
            return DossierPlatauDto::fromArray($row);
        }, $rows);
    }

    /**
     * Compte les dossiers Plat'AU non associés à un établissement.
     * Requête COUNT lean : uniquement les JOINs nécessaires aux conditions WHERE.
     */
    public function countDossiersPlatauSansEtablissement(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->join('d.nature', 'n')
            ->join('d.consultationPlatau', 'cp')
            ->leftJoin('d.etablissements', 'e')
            ->where('d.dateSuppression IS NULL')
            ->andWhere('d.consultationPlatau IS NOT NULL')
            ->andWhere('e.id IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les dossiers Plat'AU avec des pièces jointes en erreur d'envoi.
     * Requête COUNT lean : uniquement les JOINs nécessaires aux conditions WHERE.
     */
    public function countDossiersPlatauPjsEnErreur(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->join('d.nature', 'n')
            ->join('d.consultationPlatau', 'cp')
            ->join('d.piecesJointes', 'dpj')
            ->join('dpj.pieceJointe', 'pj')
            ->join('pj.statut', 'pjs')
            ->join('d.etablissements', 'e')
            ->where('d.dateSuppression IS NULL')
            ->andWhere('d.consultationPlatau IS NOT NULL')
            ->andWhere('cp.statutAvis = :statutAvis')
            ->andWhere('pjs.nomStatut IN (:nomStatuts)')
            ->setParameter('statutAvis', 'treated')
            ->setParameter('nomStatuts', ['to_be_exported', 'on_error', 'awaiting_status'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les dossiers Plat'AU avec de nouvelles pièces jointes.
     * Requête COUNT lean : uniquement les JOINs nécessaires aux conditions WHERE.
     *
     * @param \DateTimeInterface $lastPiecesVisitDate Date de dernière visite de la page pièces jointes
     */
    public function countDossiersPlatauNouvellesPjs(\DateTimeInterface $lastPiecesVisitDate): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->join('d.nature', 'n')
            ->join('d.consultationPlatau', 'cp')
            ->join('d.piecesJointes', 'dpj')
            ->join('dpj.pieceJointe', 'pj')
            ->join('d.etablissements', 'e')
            ->where('d.dateSuppression IS NULL')
            ->andWhere('d.consultationPlatau IS NOT NULL')
            ->andWhere('pj.dateNotification >= :lastPiecesVisitDate')
            ->setParameter('lastPiecesVisitDate', $lastPiecesVisitDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne les liaisons d'un dossier (dossier lié).
     * Port direct de Model_DbTable_DossierLie::getDossierLie().
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossiersLies(int $idDossier): array
    {
        // Links where dossier is ID_DOSSIER1 (owning side: dossiersLies)
        /** @var array<int, array<string, mixed>> $asOwner */
        $asOwner = $this->createQueryBuilder('d')
            ->select('d.id as ID_DOSSIER1, dl.id as ID_DOSSIER2')
            ->join('d.dossiersLies', 'dl')
            ->where('d.id = :id')
            ->setParameter('id', $idDossier)
            ->getQuery()
            ->getArrayResult();

        // Links where dossier is ID_DOSSIER2 (inverse side: dossiersAyantLie)
        /** @var array<int, array<string, mixed>> $asLinked */
        $asLinked = $this->createQueryBuilder('d')
            ->select('dal.id as ID_DOSSIER1, d.id as ID_DOSSIER2')
            ->join('d.dossiersAyantLie', 'dal')
            ->where('d.id = :id')
            ->setParameter('id', $idDossier)
            ->getQuery()
            ->getArrayResult();

        return array_merge($asOwner, $asLinked);
    }

    /**
     * Retourne l'identifiant de la nature d'un dossier.
     * Port direct de Model_DbTable_DossierNature::getDossierNaturesId().
     */
    public function getDossierNatureId(int $idDossier): ?int
    {
        /** @var array<string, mixed>|null $result */
        $result = $this->createQueryBuilder('d')
            ->select('n.id')
            ->join('d.nature', 'n')
            ->where('d.id = :id')
            ->setParameter('id', $idDossier)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $result || !isset($result['id'])) {
            return null;
        }

        return is_numeric($result['id']) ? (int) $result['id'] : null;
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
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->join('d.nature', 'n')
            ->join('d.preventionnistes', 'u')
            ->where('d.dateSuppression IS NULL')
            ->andWhere('u.id = :userId')
            ->andWhere('d.verrou = 0')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les dossiers suivis sans avis pour un utilisateur.
     */
    public function countDossiersSuivisSansAvis(int $userId): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->join('d.nature', 'n')
            ->join('d.preventionnistes', 'u')
            ->where('d.dateSuppression IS NULL')
            ->andWhere('u.id = :userId')
            ->andWhere(
                '(d.avis IS NULL AND (d.avisCommission IS NULL OR IDENTITY(d.avisCommission) = 0) AND IDENTITY(d.type) = :typeEtude)'
                .' OR (d.dateReponse IS NULL AND IDENTITY(d.type) = :typeCourrier)'
            )
            ->setParameter('userId', $userId)
            ->setParameter('typeEtude', DossierType::ETUDE)
            ->setParameter('typeCourrier', DossierType::COURRIER)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les dossiers avec date de commission échue pour le tableau de bord.
     * Requête COUNT lean : uniquement les JOINs nécessaires aux conditions WHERE.
     *
     * @param int[] $idsCommission
     */
    public function countDossierDateCommissionEchu(array $idsCommission, int $sinceDays = 10, int $untilDays = 100): int
    {
        // Seuils calculés côté PHP — le timezone PHP doit être aligné avec le timezone MySQL.
        $sinceThreshold = new \DateTimeImmutable(sprintf('-%d days', $sinceDays));
        $untilThreshold = new \DateTimeImmutable(sprintf('-%d days', $untilDays));

        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->join('d.nature', 'n')
            ->leftJoin(DossierAffectation::class, 'da', 'WITH', 'da.dossier = d')
            ->leftJoin('da.dateCommission', 'dc')
            ->where('dc.date <= :sinceThreshold')
            ->andWhere('dc.date >= :untilThreshold')
            ->andWhere('(d.avisCommission IS NULL OR IDENTITY(d.avisCommission) = 0)')
            ->andWhere('d.dateSuppression IS NULL')
            ->setParameter('sinceThreshold', $sinceThreshold)
            ->setParameter('untilThreshold', $untilThreshold);

        if ([] !== $idsCommission) {
            $qb->andWhere('IDENTITY(dc.commission) IN (:idsCommission)')
                ->setParameter('idsCommission', $idsCommission);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Compte les dossiers avec avis différé pour le tableau de bord.
     * Requête COUNT lean via QueryBuilder.
     *
     * @param int[] $idsCommission
     */
    public function countDossierAvecAvisDiffere(array $idsCommission): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->join('d.nature', 'n')
            ->where('d.dateSuppression IS NULL')
            ->andWhere('d.avisDiffere = 1');

        if ([] !== $idsCommission) {
            $qb->andWhere('IDENTITY(d.commission) IN (:idsCommission)')
                ->setParameter('idsCommission', $idsCommission);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Compte les courriers sans réponse.
     * Requête COUNT lean via QueryBuilder.
     */
    public function countCourrierSansReponse(int $dureeEnJour = 5): int
    {
        $cutoffDate = new \DateTime('today');
        $cutoffDate->modify(sprintf('-%d days', $dureeEnJour));

        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->join('d.nature', 'n')
            ->where('d.dateSuppression IS NULL')
            ->andWhere('IDENTITY(d.type) = :typeCourrier')
            ->andWhere('d.dateReponse IS NULL')
            ->andWhere('d.objet IS NOT NULL')
            ->andWhere('d.dateInsert < :cutoffDate')
            ->setParameter('typeCourrier', DossierType::COURRIER)
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les dossiers incomplets.
     */
    public function countDossiersIncomplets(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->join('d.nature', 'n')
            ->where('d.dateSuppression IS NULL')
            ->andWhere('d.incomplet = 1')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Construit un ResultSetMapping pour les requêtes natives qui sélectionnent d.* + colonnes jointes.
     * Utilise l'introspection des métadonnées Doctrine pour mapper automatiquement toutes les colonnes
     * de la table dossier, puis ajoute les colonnes supplémentaires (computed, joined).
     *
     * Note : le mode scalaire (addScalarResult) est intentionnel — il conserve le format
     * array<string, mixed> attendu par les templates Twig du dashboard. Une hydratation vers
     * des objets Doctrine typés nécessiterait d'adapter l'ensemble des templates concernés.
     *
     * @param string[] $extraColumns Colonnes supplémentaires (computed, tables jointes)
     */
    private function buildNativeQueryRsm(array $extraColumns = []): ResultSetMapping
    {
        $rsm = new ResultSetMapping();
        $metadata = $this->getEntityManager()->getClassMetadata(Dossier::class);

        // Map all scalar fields from dossier table (d.*)
        foreach ($metadata->fieldMappings as $mapping) {
            $rsm->addScalarResult($mapping['columnName'], $mapping['columnName']);
        }

        // Map FK columns from ManyToOne/OneToOne associations
        foreach ($metadata->associationMappings as $mapping) {
            if (isset($mapping['joinColumns'])) {
                foreach ($mapping['joinColumns'] as $joinColumn) {
                    $columnName = $joinColumn['name'];
                    if (!isset($rsm->scalarMappings[$columnName])) {
                        $rsm->addScalarResult($columnName, $columnName);
                    }
                }
            }
        }

        // Map extra columns (computed subqueries, joined table columns)
        foreach ($extraColumns as $column) {
            if (!isset($rsm->scalarMappings[$column])) {
                $rsm->addScalarResult($column, $column);
            }
        }

        return $rsm;
    }

    /**
     * Construit un ResultSetMapping pour les requêtes dashboard (buildSearchDossierBaseSql / buildPlatauDossierSql).
     *
     * @param string[] $additionalColumns Colonnes spécifiques à ajouter en plus des colonnes dashboard standard
     */
    private function buildDashboardResultSetMapping(array $additionalColumns = []): ResultSetMapping
    {
        $dashboardColumns = [
            'NB_PJ', 'NB_DOSS_LIES', 'NB_URBA', 'ALERTE_RECEPTION_TRAVAUX', 'ECHEANCIER_TRAVAUX',
            'ID_DOSSIER1', 'ID_DOSSIER2',
            'LIBELLE_DOSSIERNATURE', 'ID_DOSSIERNATURE',
            'LIBELLE_DOSSIERTYPE',
            'NUM_DOCURBA',
            'ID_AVIS', 'LIBELLE_AVIS',
            'ID_UTILISATEUR',
            'ID_ETABLISSEMENT',
            'LIBELLE_ETABLISSEMENTINFORMATIONS', 'ID_TYPE',
            'LIBELLE_TYPE',
            'LIBELLE_GENRE',
            'DATE_REPONSE_ATTENDUE',
        ];

        return $this->buildNativeQueryRsm(array_merge($dashboardColumns, $additionalColumns));
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

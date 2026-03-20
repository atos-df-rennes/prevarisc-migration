<?php

namespace App\Repository;

use App\Constant\DossierNature;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function hasTravaux(Dossier $dossier, array $natures): bool
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->select('COUNT(dl.id)')
            ->leftjoin('d.dossiersLies', 'dl')
            ->join('dl.nature', 'dln')
            ->where('dln.id in (:natures)')
            ->andWhere('d.id = :idDossier')
            ->setParameter('natures', $natures)
            ->setParameter('idDossier', $dossier->getId());

        return $query->getQuery()->getSingleScalarResult() > 0;
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
            ->orderBy('dta.type', 'ASC')
            ->setParameter('id', $dossierId)
            ->getQuery();

        return $query->getOneOrNullResult();
    }
}

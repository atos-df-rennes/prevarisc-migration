<?php

namespace App\Repository;

use App\Constant\DossierNature;
use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
            ->andWhere('d.differeAvis = :avisDiffere')
            ->andWhere('d.dateSuppression IS NULL')
            ->setParameter('etablissementId', $etablissement->getId())
            ->setParameter('avisDiffere', true)
            ->orderBy('d.dateInsert', 'DESC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }
}

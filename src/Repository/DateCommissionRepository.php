<?php

namespace App\Repository;

use App\Entity\DateCommission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DateCommission>
 *
 * @method DateCommission|null find($id, $lockMode = null, $lockVersion = null)
 * @method DateCommission|null findOneBy(array $criteria, array $orderBy = null)
 * @method DateCommission[]    findAll()
 * @method DateCommission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DateCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DateCommission::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DateCommission $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(DateCommission $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function getDateLieesV2($idDateComm): array
    {
        return $this->createQueryBuilder('dc')
            ->where('dc.id = :id')
            ->orWhere('dc.dateCommissionLiees = :id')
            ->setParameter('id', $idDateComm)
            ->orderBy('dc.dateCommission', 'ASC')
            ->getQuery()
            ->getResult();
    }


   public function findInfosVisite(int $idDossier): ?array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT dc.*
            FROM dossieraffectation da
            INNER JOIN datecommission dc ON da.ID_DATECOMMISSION_AFFECT = dc.ID_DATECOMMISSION
            WHERE da.ID_DOSSIER_AFFECT = :idDossier
            AND dc.ID_COMMISSIONTYPEEVENEMENT IN (2, 3)
            ORDER BY dc.DATE_COMMISSION DESC
            LIMIT 1
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['idDossier' => $idDossier]);
        $data = $result->fetchAssociative();
        return $data !== false ? $data : null;

    }


    public function findEventsBetween(\DateTime $start, \DateTime $end, ?int $idComm = null, ?int $type = null): array
    {
        $qb = $this->createQueryBuilder('dc')
            ->andWhere('dc.dateCommission BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'));

        if ($idComm) {
            $qb->andWhere('dc.commissionConcerne = :idComm')
            ->setParameter('idComm', $idComm);
        }

        if ($type) {
            $qb->andWhere('dc.commissionTypeEvenement = :type')
            ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    // Méthode pour récupérer les dates liées
    public function getCommissionsQtypListing(int $idDateComm): array
    {
        // Exemple : récupérer toutes les dates liées au master idDateComm ou lui-même
        return $this->createQueryBuilder('d')
            ->andWhere('d.dateCommissionLiees = :id OR d.id = :id')
            ->setParameter('id', $idDateComm)
            ->orderBy('d.dateCommission', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Met à jour le libellé de la date commission
    public function dateCommUpdateLibelle(int $idDateComm, string $libelle): void
    {
        $em = $this->getEntityManager();
        $comm = $this->find($idDateComm);
        if ($comm) {
            $comm->setLibelleDateCommission($libelle);
            $em->flush();
        }
    }



    public function getCommissionsDateLieesMaster($idComm): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT *
            FROM datecommission
            WHERE ID_DATECOMMISSION = :idComm
            OR DATECOMMISSION_LIEES = :idComm
            ORDER BY DATE_COMMISSION
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['idComm' => $idComm]);

        return $result->fetchAllAssociative();
    }


    // Met à jour le type commission
    public function dateCommUpdateType(int $idDateComm, int $typeId): void
    {
        $em = $this->getEntityManager();
        $comm = $this->find($idDateComm);
        $commissionType = $em->getRepository(\App\Entity\CommissionTypeEvenement::class)->find($typeId);

        if ($comm && $commissionType) {
            $comm->setCommissionTypeEvenement($commissionType);
            $em->flush();
        }
    }

    // Sauvegarde un objet DateCommission (insert ou update)
    public function save(DateCommission $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function createDateCommissionLiee(DateCommission $masterComm, \DateTime $date): DateCommission
    {
        $em = $this->getEntityManager();
        $newDateComm = new DateCommission();
        $newDateComm->setDateCommission($date);
        $newDateComm->setDateCommissionLiees($masterComm->getId());
        $newDateComm->setCommissionTypeEvenement($masterComm->getCommissionTypeEvenement());
        // Copier d'autres champs si nécessaire

        $em->persist($newDateComm);
        $em->flush();

        return $newDateComm;
    }

    public function updateDependingDossierDates(DateCommission $dateComm): void
    {
        $em = $this->getEntityManager();

        // Récupérer toutes les affectations liées à cette date commission
        $dossierAffectations = $em->getRepository(\App\Entity\DossierAffectation::class)->findBy(['idDateCommissionAffect' => $dateComm]);
        if (empty($dossierAffectations)) {
            return;
        }
        // Identifier le champ à mettre à jour en fonction du type de commission
        if($dateComm->getCommissionTypeEvenement() != null){
            $typeId = $dateComm->getCommissionTypeEvenement()->getId();
        }

        if (!in_array($typeId, [1, 2, 3])) {
            return;
        }
        foreach ($dossierAffectations as $affectation) {
            $dossier = $affectation->getDossierAffect();
            if (!$dossier) {
                continue;
            }
            if ($typeId === 1) {
                $dossier->setDateCommission($dateComm->getDateCommission());
            } elseif (in_array($typeId, [2, 3])) {
                $dossier->setDateVisite($dateComm->getDateCommission());
            }

            $em->persist($dossier);
        }
        $em->flush();
    }



    // /**
    //  * @return DateCommission[] Returns an array of DateCommission objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DateCommission
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

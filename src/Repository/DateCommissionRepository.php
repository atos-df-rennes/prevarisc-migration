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

        return $result->fetchAssociative();
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

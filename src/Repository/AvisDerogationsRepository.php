<?php

namespace App\Repository;

use App\Entity\AvisDerogations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvisDerogations>
 *
 * @method AvisDerogations|null find($id, $lockMode = null, $lockVersion = null)
 * @method AvisDerogations|null findOneBy(array $criteria, array $orderBy = null)
 * @method AvisDerogations[]    findAll()
 * @method AvisDerogations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvisDerogationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvisDerogations::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(AvisDerogations $entity, bool $flush = true): void
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
    public function remove(AvisDerogations $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return AvisDerogations[] Returns an array of AvisDerogations objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AvisDerogations
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

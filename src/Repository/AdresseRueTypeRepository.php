<?php

namespace App\Repository;

use App\Entity\AdresseRueType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdresseRueType>
 *
 * @method AdresseRueType|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdresseRueType|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdresseRueType[]    findAll()
 * @method AdresseRueType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdresseRueTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdresseRueType::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(AdresseRueType $entity, bool $flush = true): void
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
    public function remove(AdresseRueType $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return AdresseRueType[] Returns an array of AdresseRueType objects
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
    public function findOneBySomeField($value): ?AdresseRueType
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

<?php

namespace App\Repository;

use App\Entity\GroupementType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupementType>
 *
 * @method GroupementType|null find($id, $lockMode = null, $lockVersion = null)
 * @method GroupementType|null findOneBy(array $criteria, array $orderBy = null)
 * @method GroupementType[]    findAll()
 * @method GroupementType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupementTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupementType::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(GroupementType $entity, bool $flush = true): void
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
    public function remove(GroupementType $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Retourne tous les types de groupement, triés par libellé.
     *
     * @return GroupementType[]
     */
    public function findAllGroupementTypes(): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.libelle', 'ASC') 
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return GroupementType[] Returns an array of GroupementType objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GroupementType
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

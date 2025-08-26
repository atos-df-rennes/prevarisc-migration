<?php

namespace App\Repository;

use App\Entity\TypePlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypePlan>
 *
 * @method TypePlan|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypePlan|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypePlan[]    findAll()
 * @method TypePlan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypePlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypePlan::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(TypePlan $entity, bool $flush = true): void
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
    public function remove(TypePlan $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

<?php

namespace App\Repository;

use App\Entity\CommissionTypeEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommissionTypeEvenement>
 *
 * @method CommissionTypeEvenement|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommissionTypeEvenement|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommissionTypeEvenement[]    findAll()
 * @method CommissionTypeEvenement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionTypeEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommissionTypeEvenement::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CommissionTypeEvenement $entity, bool $flush = true): void
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
    public function remove(CommissionTypeEvenement $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

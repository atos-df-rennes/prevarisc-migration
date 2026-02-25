<?php

namespace App\Repository;

use App\Entity\CommissionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommissionType>
 *
 * @method CommissionType|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommissionType|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommissionType[]    findAll()
 * @method CommissionType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommissionType::class);
    }

    public function findWithCommissions()
    {
        return $this->createQueryBuilder('ct')
            ->addSelect('c')
            ->leftJoin('ct.commissions', 'c')
            ->orderBy('ct.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}

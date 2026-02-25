<?php

namespace App\Repository;

use App\Entity\CommissionRegle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommissionRegle>
 *
 * @method CommissionRegle|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommissionRegle|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommissionRegle[]    findAll()
 * @method CommissionRegle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionRegleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommissionRegle::class);
    }
}

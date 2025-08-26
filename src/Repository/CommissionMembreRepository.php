<?php

namespace App\Repository;

use App\Entity\CommissionMembre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommissionMembre>
 *
 * @method CommissionMembre|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommissionMembre|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommissionMembre[]    findAll()
 * @method CommissionMembre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionMembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommissionMembre::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CommissionMembre $entity, bool $flush = true): void
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
    public function remove(CommissionMembre $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

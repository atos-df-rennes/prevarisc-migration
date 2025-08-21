<?php

namespace App\Repository;

use App\Entity\CommissionRegleEtudeVisite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommissionRegleEtudeVisite>
 *
 * @method CommissionRegleEtudeVisite|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommissionRegleEtudeVisite|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommissionRegleEtudeVisite[]    findAll()
 * @method CommissionRegleEtudeVisite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionRegleEtudeVisiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommissionRegleEtudeVisite::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CommissionRegleEtudeVisite $entity, bool $flush = true): void
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
    public function remove(CommissionRegleEtudeVisite $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

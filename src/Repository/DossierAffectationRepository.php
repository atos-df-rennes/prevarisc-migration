<?php

namespace App\Repository;

use App\Entity\DossierAffectation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierAffectation>
 *
 * @method DossierAffectation|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierAffectation|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierAffectation[]    findAll()
 * @method DossierAffectation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierAffectationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierAffectation::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DossierAffectation $entity, bool $flush = true): void
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
    public function remove(DossierAffectation $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

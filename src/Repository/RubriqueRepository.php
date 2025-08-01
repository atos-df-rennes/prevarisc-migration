<?php

namespace App\Repository;

use App\Entity\Rubrique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rubrique>
 *
 * @method Rubrique|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rubrique|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rubrique[]    findAll()
 * @method Rubrique[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RubriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rubrique::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Rubrique $entity, bool $flush = true): void
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
    public function remove(Rubrique $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

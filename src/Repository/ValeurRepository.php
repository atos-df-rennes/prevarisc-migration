<?php

namespace App\Repository;

use App\Entity\Valeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Valeur>
 *
 * @method Valeur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Valeur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Valeur[]    findAll()
 * @method Valeur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ValeurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Valeur::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Valeur $entity, bool $flush = true): void
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
    public function remove(Valeur $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

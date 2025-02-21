<?php

namespace App\Repository;

use App\Entity\Privileges;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Privileges>
 *
 * @method Privileges|null find($id, $lockMode = null, $lockVersion = null)
 * @method Privileges|null findOneBy(array $criteria, array $orderBy = null)
 * @method Privileges[]    findAll()
 * @method Privileges[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrivilegesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Privileges::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Privileges $entity, bool $flush = true): void
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
    public function remove(Privileges $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

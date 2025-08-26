<?php

namespace App\Repository;

use App\Entity\Classement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Classement>
 *
 * @method Classement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Classement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Classement[]    findAll()
 * @method Classement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classement::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Classement $entity, bool $flush = true): void
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
    public function remove(Classement $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

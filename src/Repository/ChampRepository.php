<?php

namespace App\Repository;

use App\Entity\Champ;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Champ>
 *
 * @method Champ|null find($id, $lockMode = null, $lockVersion = null)
 * @method Champ|null findOneBy(array $criteria, array $orderBy = null)
 * @method Champ[]    findAll()
 * @method Champ[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChampRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Champ::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Champ $entity, bool $flush = true): void
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
    public function remove(Champ $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

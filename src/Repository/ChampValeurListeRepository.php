<?php

namespace App\Repository;

use App\Entity\ChampValeurListe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChampValeurListe>
 *
 * @method ChampValeurListe|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChampValeurListe|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChampValeurListe[]    findAll()
 * @method ChampValeurListe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChampValeurListeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChampValeurListe::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ChampValeurListe $entity, bool $flush = true): void
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
    public function remove(ChampValeurListe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

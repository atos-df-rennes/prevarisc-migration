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

    public function findAllInformations(Rubrique $rubrique): Rubrique
    {
        return $this->createQueryBuilder('r')
            ->addSelect('c')
            ->addSelect('ct')
            ->addSelect('cvl')
            ->leftJoin('r.champs', 'c')
            ->leftJoin('c.type', 'ct')
            ->leftJoin('c.valeursListe', 'cvl')
            ->andWhere('r = :rubrique')
            ->setParameter('rubrique', $rubrique)
            ->orderBy('c.idx', 'ASC')
            ->addOrderBy('cvl.idx', 'ASC')
            ->getQuery()
            ->getSingleResult()
        ;
    }
}

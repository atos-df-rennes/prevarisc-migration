<?php

namespace App\Repository;

use App\Entity\Champ;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findAllInformations(Champ $champ): Champ
    {
        return $this->createQueryBuilder('c')
            ->addSelect('e')
            ->addSelect('cvl')
            ->addSelect('e_cvl')
            ->leftJoin('c.enfants', 'e')
            ->leftJoin('c.valeursListe', 'cvl')
            ->leftJoin('e.valeursListe', 'e_cvl')
            ->andWhere('c = :champ')
            ->setParameter('champ', $champ)
            ->orderBy('e.idx', 'ASC')
            ->addOrderBy('cvl.idx', 'ASC')
            ->addOrderBy('e_cvl.idx', 'ASC')
            ->getQuery()
            ->getSingleResult()
        ;
    }
}

<?php

namespace App\Repository;

use App\Entity\DossierNatureListe;
use App\Entity\DossierType;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierNatureListe>
 *
 * @method DossierNatureListe|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierNatureListe|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierNatureListe[]    findAll()
 * @method DossierNatureListe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierNatureListeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierNatureListe::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DossierNatureListe $entity, bool $flush = true): void
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
    public function remove(DossierNatureListe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findByTypeOrdered(DossierType $type): array
    {
        return $this->createQueryBuilder('dnl')
            ->where('dnl.type = :type')
            ->andWhere('dnl.ordre IS NOT NULL')
            ->setParameter('type', $type)
            ->orderBy('dnl.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }


    // /**
    //  * @return DossierNatureListe[] Returns an array of DossierNatureListe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DossierNatureListe
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

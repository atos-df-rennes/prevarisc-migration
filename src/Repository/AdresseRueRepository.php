<?php

namespace App\Repository;

use App\Entity\AdresseRue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdresseRue>
 *
 * @method AdresseRue|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdresseRue|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdresseRue[]    findAll()
 * @method AdresseRue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdresseRueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdresseRue::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(AdresseRue $entity, bool $flush = true): void
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
    public function remove(AdresseRue $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return AdresseRue[] Returns an array of AdresseRue objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AdresseRue
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @return array<int, array{ID_RUE: string, LIBELLE_RUE: string}>
     */
    public function findByCommune(string $codeInsee): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.id AS ID_RUE, r.libelle AS LIBELLE_RUE')
            ->where('r.commune = :commune')
            ->setParameter('commune', $codeInsee)
            ->orderBy('r.libelle', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }
}

<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\CommissionRegle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommissionRegle>
 *
 * @method CommissionRegle|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommissionRegle|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommissionRegle[]    findAll()
 * @method CommissionRegle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionRegleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommissionRegle::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CommissionRegle $entity, bool $flush = true): void
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
    public function remove(CommissionRegle $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return CommissionRegle[] Returns an array of CommissionRegle objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CommissionRegle
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * Récupère toutes les règles d'une commission avec leurs relations chargées.
     *
     * @return CommissionRegle[]
     */
    public function findByCommissionWithRelations(Commission $commission): array
    {
        return $this->createQueryBuilder('cr')
            ->leftJoin('cr.commune', 'commune')->addSelect('commune')
            ->leftJoin('cr.groupement', 'groupement')->addSelect('groupement')
            ->leftJoin('cr.categories', 'categories')->addSelect('categories')
            ->leftJoin('cr.classes', 'classes')->addSelect('classes')
            ->leftJoin('cr.types', 'types')->addSelect('types')
            ->leftJoin('cr.etudeVisite', 'etudeVisite')->addSelect('etudeVisite')
            ->leftJoin('cr.localSommeil', 'localSommeil')->addSelect('localSommeil')
            ->where('cr.commission = :commission')
            ->setParameter('commission', $commission)
            ->orderBy('cr.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}

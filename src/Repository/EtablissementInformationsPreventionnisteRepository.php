<?php

namespace App\Repository;

use App\Entity\EtablissementInformationsPreventionniste;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementInformationsPreventionniste>
 *
 * @method EtablissementInformationsPreventionniste|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementInformationsPreventionniste|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementInformationsPreventionniste[]    findAll()
 * @method EtablissementInformationsPreventionniste[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementInformationsPreventionnisteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementInformationsPreventionniste::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(EtablissementInformationsPreventionniste $entity, bool $flush = true): void
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
    public function remove(EtablissementInformationsPreventionniste $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return EtablissementInformationsPreventionniste[] Returns an array of EtablissementInformationsPreventionniste objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EtablissementInformationsPreventionniste
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

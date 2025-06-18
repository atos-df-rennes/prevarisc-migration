<?php

namespace App\Repository;

use App\Entity\Piecejointe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Piecejointe>
 *
 * @method Piecejointe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Piecejointe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Piecejointe[]    findAll()
 * @method Piecejointe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PiecejointeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Piecejointe::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Piecejointe $entity, bool $flush = true): void
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
    public function remove(Piecejointe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return Piecejointe[]
     */
    public function findWithStatus(int $idDossier, string $status): array
    {
        return $this->createQueryBuilder('pj')
            ->join('App\Entity\DossierPJ', 'dpj', 'WITH', 'dpj.pieceJointe = pj')
            ->join('pj.statut', 'statut')
            ->where('dpj.idDossier = :idDossier')
            ->andWhere('statut.nomStatut = :status')
            ->setParameter('idDossier', $idDossier)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Piecejointe[] Returns an array of Piecejointe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Piecejointe
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

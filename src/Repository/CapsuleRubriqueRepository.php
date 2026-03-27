<?php

namespace App\Repository;

use App\Entity\CapsuleRubrique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CapsuleRubrique>
 *
 * @method CapsuleRubrique|null find($id, $lockMode = null, $lockVersion = null)
 * @method CapsuleRubrique|null findOneBy(array $criteria, array $orderBy = null)
 * @method CapsuleRubrique[]    findAll()
 * @method CapsuleRubrique[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CapsuleRubriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CapsuleRubrique::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CapsuleRubrique $entity, bool $flush = true): void
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
    public function remove(CapsuleRubrique $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return CapsuleRubrique[]
     */
    public function findAllInformations(): array
    {
        return $this->createQueryBuilder('cr')
            ->addSelect('r')
            ->leftJoin('cr.rubriques', 'r')
            ->orderBy('cr.id', 'ASC')
            ->addOrderBy('r.idx', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByNomInterneWithFullData(string $nomInterne): ?CapsuleRubrique
    {
        return $this->createQueryBuilder('cr')
            ->addSelect('r', 'c', 'ct', 'cvl', 'ce', 'cet', 'cevl')
            ->leftJoin('cr.rubriques', 'r')
            ->leftJoin('r.champs', 'c')
            ->leftJoin('c.type', 'ct')
            ->leftJoin('c.valeursListe', 'cvl')
            ->leftJoin('c.enfants', 'ce')
            ->leftJoin('ce.type', 'cet')
            ->leftJoin('ce.valeursListe', 'cevl')
            ->andWhere('cr.nomInterne = :nomInterne')
            ->setParameter('nomInterne', $nomInterne)
            ->orderBy('r.idx', 'ASC')
            ->addOrderBy('c.idx', 'ASC')
            ->addOrderBy('cvl.idx', 'ASC')
            ->addOrderBy('ce.idx', 'ASC')
            ->addOrderBy('cevl.idx', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}

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
    public function findAllElements(): array
    {
        return $this->createQueryBuilder('cr')
            ->join('cr.rubriques', 'r')
            ->join('r.champs', 'c')
            ->join('c.type', 't')
            ->join('c.valeursListe', 'vl')
            ->getQuery()
            ->getResult()
        ;
    }
}

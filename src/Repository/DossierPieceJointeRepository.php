<?php

namespace App\Repository;

use App\Entity\DossierPieceJointe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierPieceJointe>
 *
 * @method DossierPieceJointe|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierPieceJointe|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierPieceJointe[]    findAll()
 * @method DossierPieceJointe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierPieceJointeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierPieceJointe::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DossierPieceJointe $entity, bool $flush = true): void
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
    public function remove(DossierPieceJointe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

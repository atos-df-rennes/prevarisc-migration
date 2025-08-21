<?php

namespace App\Repository;

use App\Entity\DossierDisplayRubrique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierDisplayRubrique>
 *
 * @method DossierDisplayRubrique|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierDisplayRubrique|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierDisplayRubrique[]    findAll()
 * @method DossierDisplayRubrique[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierDisplayRubriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierDisplayRubrique::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DossierDisplayRubrique $entity, bool $flush = true): void
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
    public function remove(DossierDisplayRubrique $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

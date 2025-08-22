<?php

namespace App\Repository;

use App\Entity\DossierDocumentUrbanisme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierDocumentUrbanisme>
 *
 * @method DossierDocumentUrbanisme|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierDocumentUrbanisme|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierDocumentUrbanisme[]    findAll()
 * @method DossierDocumentUrbanisme[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierDocumentUrbanismeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierDocumentUrbanisme::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DossierDocumentUrbanisme $entity, bool $flush = true): void
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
    public function remove(DossierDocumentUrbanisme $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

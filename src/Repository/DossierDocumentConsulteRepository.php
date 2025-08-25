<?php

namespace App\Repository;

use App\Entity\DossierDocumentConsulte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierDocumentConsulte>
 *
 * @method DossierDocumentConsulte|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierDocumentConsulte|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierDocumentConsulte[]    findAll()
 * @method DossierDocumentConsulte[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierDocumentConsulteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierDocumentConsulte::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DossierDocumentConsulte $entity, bool $flush = true): void
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
    public function remove(DossierDocumentConsulte $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

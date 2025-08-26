<?php

namespace App\Repository;

use App\Entity\DocumentManquant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentManquant>
 *
 * @method DocumentManquant|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentManquant|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentManquant[]    findAll()
 * @method DocumentManquant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentManquantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentManquant::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DocumentManquant $entity, bool $flush = true): void
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
    public function remove(DocumentManquant $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

<?php

namespace App\Repository;

use App\Entity\CoucheCarto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CoucheCarto>
 *
 * @method CoucheCarto|null find($id, $lockMode = null, $lockVersion = null)
 * @method CoucheCarto|null findOneBy(array $criteria, array $orderBy = null)
 * @method CoucheCarto[]    findAll()
 * @method CoucheCarto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CoucheCartoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoucheCarto::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CoucheCarto $entity, bool $flush = true): void
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
    public function remove(CoucheCarto $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return CoucheCarto[]
     */
    public function findAllOrdered(): array
    {
        $couchesCarto = $this->findAll();
        usort($couchesCarto, function ($a, $b): int {
            return ($a->getOrdre() ?? 0) - ($b->getOrdre() ?? 0);
        });

        return $couchesCarto;
    }
}

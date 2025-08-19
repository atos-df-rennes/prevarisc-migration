<?php

namespace App\Repository;

use App\Entity\TexteApplicable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TexteApplicable>
 *
 * @method TexteApplicable|null find($id, $lockMode = null, $lockVersion = null)
 * @method TexteApplicable|null findOneBy(array $criteria, array $orderBy = null)
 * @method TexteApplicable[]    findAll()
 * @method TexteApplicable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TexteApplicableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TexteApplicable::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(TexteApplicable $entity, bool $flush = true): void
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
    public function remove(TexteApplicable $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function getAllSorted(): array
    {
        $queryBuilder = $this->createQueryBuilder('ta');
        $query = $queryBuilder
            ->select(['ta.id', 'ta.libelle', 'ta.visible', 'tatype.id as typeid', 'tatype.libelle as type', 'ta.num'])
            ->join('ta.type', 'tatype')
            ->orderBy('tatype.id, ta.num', 'ASC')
            ->getQuery();

        return $query->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\TypeChamp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeChamp>
 *
 * @method TypeChamp|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeChamp|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeChamp[]    findAll()
 * @method TypeChamp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeChampRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeChamp::class);
    }

    /**
     * @return TypeChamp[]
     */
    public function findAllowedTypeForParent(): array
    {
        return $this->createQueryBuilder('tc')
            ->where('tc.type != :type')
            ->setParameter('type', 'Parent')
            ->getQuery()
            ->getResult();
    }
}

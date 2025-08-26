<?php

namespace App\Repository;

use App\Entity\PrescriptionCategorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionCategorie>
 *
 * @method PrescriptionCategorie|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionCategorie|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionCategorie[]    findAll()
 * @method PrescriptionCategorie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionCategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionCategorie::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionCategorie $entity, bool $flush = true): void
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
    public function remove(PrescriptionCategorie $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

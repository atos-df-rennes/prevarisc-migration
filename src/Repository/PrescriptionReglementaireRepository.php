<?php

namespace App\Repository;

use App\Entity\PrescriptionReglementaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionReglementaire>
 *
 * @method PrescriptionReglementaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionReglementaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionReglementaire[]    findAll()
 * @method PrescriptionReglementaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionReglementaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionReglementaire::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionReglementaire $entity, bool $flush = true): void
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
    public function remove(PrescriptionReglementaire $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

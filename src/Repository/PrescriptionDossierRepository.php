<?php

namespace App\Repository;

use App\Entity\PrescriptionDossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionDossier>
 *
 * @method PrescriptionDossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionDossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionDossier[]    findAll()
 * @method PrescriptionDossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionDossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionDossier::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionDossier $entity, bool $flush = true): void
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
    public function remove(PrescriptionDossier $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

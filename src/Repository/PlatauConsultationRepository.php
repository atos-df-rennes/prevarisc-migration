<?php

namespace App\Repository;

use App\Entity\PlatauConsultation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlatauConsultation>
 *
 * @method PlatauConsultation|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlatauConsultation|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlatauConsultation[]    findAll()
 * @method PlatauConsultation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlatauConsultationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlatauConsultation::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PlatauConsultation $entity, bool $flush = true): void
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
    public function remove(PlatauConsultation $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

<?php

namespace App\Repository;

use App\Entity\PrescriptionTexteListe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionTexteListe>
 *
 * @method PrescriptionTexteListe|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionTexteListe|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionTexteListe[]    findAll()
 * @method PrescriptionTexteListe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionTexteListeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionTexteListe::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionTexteListe $entity, bool $flush = true): void
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
    public function remove(PrescriptionTexteListe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Récupère tous les textes, avec une option pour filtrer par visibilité.
     *
     * @return PrescriptionTexteListe[]
     */
    public function findAllTextes(?bool $visible = null): array
    {
        $qb = $this->createQueryBuilder('ptl')
            ->orderBy('ptl.libelle', 'ASC');

        if (null !== $visible) {
            $qb->where('ptl.visible = :visible')
                ->setParameter('visible', $visible);
        }

        return $qb->getQuery()->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\PrescriptionTexte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionTexte>
 *
 * @method PrescriptionTexte|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionTexte|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionTexte[]    findAll()
 * @method PrescriptionTexte[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionTexteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionTexte::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PrescriptionTexte $entity, bool $flush = true): void
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
    public function remove(PrescriptionTexte $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Récupère le numéro maximal.
     *
     * @return int le numéro maximal
     */
    public function recupMaxNumCat(): int
    {
        $qb = $this->createQueryBuilder('texte');
        $qb->select('max(texte.numero)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}

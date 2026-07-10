<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\CommissionMembre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommissionMembre>
 *
 * @method CommissionMembre|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommissionMembre|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommissionMembre[]    findAll()
 * @method CommissionMembre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionMembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommissionMembre::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CommissionMembre $entity, bool $flush = true): void
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
    public function remove(CommissionMembre $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Charge tous les membres d'une commission avec leurs relations pour éviter les requêtes N+1.
     *
     * @return CommissionMembre[]
     */
    public function findByCommissionWithRelations(Commission $commission): array
    {
        $membres = $this->createQueryBuilder('m')
            ->leftJoin('m.groupement', 'g')->addSelect('g')
            ->leftJoin('m.utilisateurInformations', 'ui')->addSelect('ui')
            ->where('m.commission = :commission')
            ->setParameter('commission', $commission)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();

        if ([] === $membres) {
            return [];
        }

        // Initialise les collections ManyToMany pour chaque membre (évite N+1)
        $ids = array_map(static fn (CommissionMembre $m): ?int => $m->getId(), $membres);

        $this->createQueryBuilder('m')
            ->leftJoin('m.categories', 'cat')->addSelect('cat')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $this->createQueryBuilder('m')
            ->leftJoin('m.types', 'ta')->addSelect('ta')
            ->leftJoin('ta.type', 'tat')->addSelect('tat')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $this->createQueryBuilder('m')
            ->leftJoin('m.classes', 'cl')->addSelect('cl')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $this->createQueryBuilder('m')
            ->leftJoin('m.typesDossier', 'td')->addSelect('td')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $this->createQueryBuilder('m')
            ->leftJoin('m.naturesDossier', 'nd')->addSelect('nd')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        return $membres;
    }
}

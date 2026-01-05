<?php

namespace App\Repository;

use App\Entity\DateCommission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DateCommission>
 *
 * @method DateCommission|null find($id, $lockMode = null, $lockVersion = null)
 * @method DateCommission|null findOneBy(array $criteria, array $orderBy = null)
 * @method DateCommission[]    findAll()
 * @method DateCommission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DateCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DateCommission::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DateCommission $entity, bool $flush = true): void
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
    public function remove(DateCommission $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Récupère les événements du calendrier entre deux dates.
     *
     * @param string      $dateDebut Format Y-m-d
     * @param string      $dateFin   Format Y-m-d
     * @param string|null $idComm    ID de la commission (optionnel)
     * @param string|null $type      Type d'événement (optionnel)
     *
     * @return DateCommission[]
     */
    public function findEventsBetweenDates(
        string $dateDebut,
        string $dateFin,
        ?string $idComm = null,
        ?string $type = null
    ): array {
        $qb = $this->createQueryBuilder('dc')
            ->leftJoin('dc.typeEvenement', 'te')->addSelect('te')
            ->leftJoin('dc.commission', 'c')->addSelect('c')
            ->where('dc.date BETWEEN :dateDebut AND :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('dc.date', 'ASC')
            ->addOrderBy('dc.heureDebut', 'ASC');

        if (null !== $idComm) {
            $qb->andWhere('dc.commission = :idComm')
                ->setParameter('idComm', $idComm);
        }

        if (null !== $type) {
            $qb->andWhere('dc.typeEvenement = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }
}

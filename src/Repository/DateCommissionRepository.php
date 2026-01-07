<?php

namespace App\Repository;

use App\Entity\DateCommission;
use App\Entity\Dossier;
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
     * Retourne la date de commission (au sens calendrier : table datecommission et non propriété : dateCommission) d'un dossier.
     * Ce comportement n'est présent que pour les dates de visite.
     * Les dates de visite sont des dates de commission de type 2 (Visite de sécurité) ou 3 (Groupe de visites).
     */
    public function findForDossier(Dossier $dossier, \DateTimeInterface $dateVisite): DateCommission
    {
        $queryBuilder = $this->createQueryBuilder('dc');
        $query = $queryBuilder
            ->addSelect('da')
            ->join('dc.dossierAffectations', 'da')
            ->where('dc.date = :dateVisite')
            ->andWhere('dc.typeEvenement IN (:typeEvenement)')
            ->andWhere('da.dossier = :dossier')
            ->setParameter('dateVisite', $dateVisite)
            ->setParameter('typeEvenement', [2, 3])
            ->setParameter('dossier', $dossier)
            ->getQuery()
        ;

        return $query->getSingleResult();
    }

    /**
     * Retourne les dates de commission liées à la date de commission passée en paramètre.
     *
     * @return DateCommission[]
     */
    public function findDatesLiees(DateCommission $dateCommission): array
    {
        $queryBuilder = $this->createQueryBuilder('dc');
        $query = $queryBuilder
            ->where('dc.dateCommissionLiees = :dateCommissionLiees')
            ->setParameter('dateCommissionLiees', $dateCommission->getId())
            ->getQuery()
        ;

        return $query->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\DateCommission;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        $idLiaison = $dateCommission->getDateCommissionLiees();
        if (null === $idLiaison) {
            $queryBuilder = $queryBuilder
                ->where('dc.dateCommissionLiees = :dateCommissionLiees')
                ->setParameter('dateCommissionLiees', $dateCommission->getId());
        } else {
            $queryBuilder = $queryBuilder
                ->where('dc.id = :idDateCommissionLiaison')
                ->orWhere('dc.dateCommissionLiees = :idDateCommissionLiaison AND dc.id != :idDateCommission')
                ->setParameter('idDateCommissionLiaison', $idLiaison)
                ->setParameter('idDateCommission', $dateCommission->getId());
        }

        $query = $queryBuilder
            ->orderBy('dc.date', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Récupère les événements de commission entre deux dates.
     *
     * @return DateCommission[]
     */
    public function findEventsBetweenDates(
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $idCommission = null,
        ?int $idTypeEvenement = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('dc')
            ->addSelect('te')
            ->leftJoin('dc.typeEvenement', 'te')
            ->where('dc.date BETWEEN :dateDebut AND :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
        ;

        if (null !== $idCommission) {
            $queryBuilder
                ->andWhere('dc.commission = :idCommission')
                ->setParameter('idCommission', $idCommission)
            ;
        }

        if (null !== $idTypeEvenement) {
            $queryBuilder
                ->andWhere('dc.typeEvenement = :idTypeEvenement')
                ->setParameter('idTypeEvenement', $idTypeEvenement)
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }
}

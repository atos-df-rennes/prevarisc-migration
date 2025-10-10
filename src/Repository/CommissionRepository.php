<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commission>
 *
 * @method Commission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commission[]    findAll()
 * @method Commission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionRepository extends ServiceEntityRepository implements HasContacts
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commission::class);
    }

    /**
     * Retourne le nombre de commissions ayant comme contact celui passé en paramètre.
     */
    public function findNumberByContacts(UtilisateurInformations $contact): int
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $query = $queryBuilder
            ->select('COUNT(c.id)')
            ->join('c.contacts', 'cc')
            ->where('cc.id = :id')
            ->setParameter('id', $contact->getId())
            ->getQuery()
        ;

        return $query->getSingleScalarResult();
    }
}

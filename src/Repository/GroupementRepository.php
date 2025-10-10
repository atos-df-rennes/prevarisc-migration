<?php

namespace App\Repository;

use App\Entity\Groupement;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Groupement>
 *
 * @method Groupement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Groupement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Groupement[]    findAll()
 * @method Groupement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupementRepository extends ServiceEntityRepository implements HasContacts
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groupement::class);
    }

    /**
     * Retourne le nombre de groupements ayant comme contact celui passé en paramètre.
     */
    public function findNumberByContacts(UtilisateurInformations $contact): int
    {
        $queryBuilder = $this->createQueryBuilder('g');
        $query = $queryBuilder
            ->select('COUNT(g.id)')
            ->join('g.contacts', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $contact->getId())
            ->getQuery()
        ;

        return $query->getSingleScalarResult();
    }
}

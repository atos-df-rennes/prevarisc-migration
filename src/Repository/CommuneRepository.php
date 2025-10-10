<?php

namespace App\Repository;

use App\Entity\Commune;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commune>
 *
 * @method Commune|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commune|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commune[]    findAll()
 * @method Commune[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommuneRepository extends ServiceEntityRepository implements HasContacts
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commune::class);
    }

    /**
     * Retourne le nombre de communes ayant comme contact celui passé en paramètre.
     */
    public function findNumberByContacts(UtilisateurInformations $contact): int
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $query = $queryBuilder
            ->select('COUNT(c.id)')
            ->join('c.utilisateurInformations', 'ui')
            ->where('ui.id = :id')
            ->setParameter('id', $contact->getId())
            ->getQuery()
        ;

        return $query->getSingleScalarResult();
    }
}

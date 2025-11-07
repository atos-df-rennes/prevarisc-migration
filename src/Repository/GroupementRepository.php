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
 * @method Groupement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupementRepository extends ServiceEntityRepository implements HasContacts
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groupement::class);
    }

    /**
     * Récupère tous les groupements, avec la possibilité de filtrer par numéro INSEE de la ville.
     * Remplace la méthode findAll($num_insee) du legacy (@see ).
     *
     * @see
     *
     * @param string|null $num_insee numéro INSEE pour filtrer les groupements par ville
     *
     * @return Groupement[] liste des groupements triés par libellé en ordre croissant, ou filtrés par ville si le numéro INSEE est fourni
     */
    public function findByNumInsee(?string $num_insee = null): array
    {
        if (null !== $num_insee) {
            return $this->getGroupementParVille($num_insee);
        }

        return $this->findBy([], ['libelle' => 'ASC']);
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

    /**
     * Retourne une liste de groupements associés à une ville identifiée par son code INSEE.
     *
     * @param string $codeInsee code INSEE de la commune
     *
     * @return Groupement[] liste des groupements correspondant, triés par type et libellé
     */
    public function getGroupementParVille(string $codeInsee): array
    {
        $qb = $this->createQueryBuilder('groupement')
            ->leftJoin('groupement.communes', 'communes')
            ->leftJoin('groupement.type', 'type')
            ->addSelect('type.libelle')
            ->where('communes.id = :codeInsee')
            ->setParameter('codeInsee', $codeInsee)
            ->orderBy('type.id', 'ASC')
            ->addOrderBy('groupement.libelle', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }
}

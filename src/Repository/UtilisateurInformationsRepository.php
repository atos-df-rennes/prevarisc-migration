<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use App\Entity\UtilisateurInformations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UtilisateurInformations>
 *
 * @method UtilisateurInformations|null find($id, $lockMode = null, $lockVersion = null)
 * @method UtilisateurInformations|null findOneBy(array $criteria, array $orderBy = null)
 * @method UtilisateurInformations[]    findAll()
 * @method UtilisateurInformations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtilisateurInformationsRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, UtilisateurInformations::class);
        $this->entityManager = $entityManager;
    }

    public function createNotInUserQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('ui');

        return $queryBuilder
            ->where(
                $queryBuilder->expr()->notIn(
                    'ui.id',
                    $this->entityManager->createQueryBuilder()
                        ->select('IDENTITY(u.informations)')
                        ->from(Utilisateur::class, 'u')
                        ->getDQL()
                )
            )
        ;
    }

    public function findByNameNotInUser(string $name): array
    {
        $queryBuilder = $this->createQueryBuilder('ui');

        return $queryBuilder
            ->select('ui.nom', 'ui.prenom', 'ui.societe', 'ui.id')
            ->where('ui.nom LIKE :name OR ui.prenom LIKE :name OR ui.societe LIKE :name')
            ->andWhere(
                $queryBuilder->expr()->notIn(
                    'ui.id',
                    $this->entityManager->createQueryBuilder()
                        ->select('IDENTITY(u.informations)')
                        ->from(Utilisateur::class, 'u')
                        ->getDQL()
                )
            )
            ->setParameter('name', '%'.$name.'%')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult()
        ;
    }
}

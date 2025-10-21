<?php

namespace App\Repository;

use App\Entity\Resources;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resources>
 *
 * @method Resources|null find($id, $lockMode = null, $lockVersion = null)
 * @method Resources|null findOneBy(array $criteria, array $orderBy = null)
 * @method Resources[]    findAll()
 * @method Resources[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResourcesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resources::class);
    }

    /**
     * @return Resources[]
     */
    public function findRessourcesSpecialiseesDossier(): array
    {
        // Utilisation d'une expression régulière pour filtrer les noms du type "dossier_<nombre>"
        return $this->createQueryBuilder('r')
            ->where('r.name REGEXP :pattern')
            ->setParameter('pattern', '^dossier_[0-9]+$')
            ->getQuery()
            ->getResult()
        ;
    }
}

<?php

namespace App\Repository;

use App\Entity\DossierNatureListe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierNatureListe>
 *
 * @method DossierNatureListe|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierNatureListe|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierNatureListe[]    findAll()
 * @method DossierNatureListe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierNatureListeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierNatureListe::class);
    }

    /**
     * Récupère les natures de dossier pour un type donné, triées par ordre.
     *
     * @param int $typeId ID du type de dossier
     *
     * @return DossierNatureListe[]
     */
    public function findByTypeOrderedByOrdre(int $typeId): array
    {
        return $this->createQueryBuilder('dnl')
            ->where('dnl.type = :typeId')
            ->setParameter('typeId', $typeId)
            ->orderBy('dnl.ordre', 'ASC')
            ->addOrderBy('dnl.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

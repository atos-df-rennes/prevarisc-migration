<?php

namespace App\Repository;

use App\Entity\ChampValeurListe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChampValeurListe>
 *
 * @method ChampValeurListe|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChampValeurListe|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChampValeurListe[]    findAll()
 * @method ChampValeurListe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChampValeurListeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChampValeurListe::class);
    }

    /**
     * Retourne la liste distincte des valeurs (toutes listes de champs confondues), triée alphabétiquement.
     * Utilisé pour alimenter l'autocomplétion (datalist) des valeurs de champ, sans charger les entités.
     *
     * @return string[]
     */
    public function findDistinctValeurs(): array
    {
        return array_column(
            $this->createQueryBuilder('cvl')
                ->select('DISTINCT cvl.valeur')
                ->orderBy('cvl.valeur', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'valeur'
        );
    }
}

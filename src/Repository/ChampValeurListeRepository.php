<?php

namespace App\Repository;

use App\Entity\ChampValeurListe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
     * Met à jour les indices de plusieurs valeurs en une seule requête DQL
     *
     * @param non-empty-array<int, array{ID: int, idx: int}> $changes
     */
    public function updateIndexesBatch(array $changes): void
    {
        $connnection = $this->getEntityManager()->getConnection();

        // Construction du CASE WHEN pour UPDATE optimisé
        $ids = [];
        $cases = [];

        foreach ($changes as $change) {
            $id = (int) $change['ID'];
            $idx = (int) $change['idx'];
            $ids[] = $id;
            $cases[] = "WHEN {$id} THEN {$idx}";
        }

        $idList = implode(',', $ids);
        $caseStatement = implode(' ', $cases);

        // Une seule requête UPDATE avec CASE
        $sql = "UPDATE champvaleurliste
               SET idx = CASE ID_VALEURLISTE {$caseStatement} END
               WHERE ID_VALEURLISTE IN ({$idList})";

        $connnection->executeStatement($sql);
    }
}

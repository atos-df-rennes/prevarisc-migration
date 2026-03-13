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
     * Met à jour les indices de plusieurs valeurs en une seule requête DQL.
     *
     * @param non-empty-array<int, array{ID: int, idx: int}> $changes
     */
    public function updateIndexesBatch(array $changes): void
    {
        $connection = $this->getEntityManager()->getConnection();

        $params = [];
        $types = [];
        $cases = [];
        $idPlaceholders = [];

        // Construction du CASE WHEN pour UPDATE optimisé
        foreach ($changes as $i => $change) {
            $idKey  = 'id'  . $i;
            $idxKey = 'idx' . $i;

            $cases[]          = "WHEN :{$idKey} THEN :{$idxKey}";
            $idPlaceholders[] = ":{$idKey}";

            $params[$idKey]  = (int) $change['ID'];
            $params[$idxKey] = (int) $change['idx'];
            $types[$idKey]   = \PDO::PARAM_INT;
            $types[$idxKey]  = \PDO::PARAM_INT;
        }

        // Une seule requête UPDATE avec CASE
        $sql = sprintf(
            'UPDATE champvaleurliste SET idx = CASE ID_VALEURLISTE %s END WHERE ID_VALEURLISTE IN (%s)',
            implode(' ', $cases),
            implode(', ', $idPlaceholders)
        );

        $connection->executeStatement($sql, $params, $types);
    }
}

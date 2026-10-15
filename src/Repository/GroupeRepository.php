<?php

namespace App\Repository;

use App\Entity\Groupe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Groupe>
 *
 * @method Groupe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Groupe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Groupe[]    findAll()
 * @method Groupe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groupe::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Groupe $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Groupe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return list<array{id: int, libelle: string, nombreUtilisateurs: int}>
     */
    public function findAvecNombreUtilisateurs(): array
    {
        /** @var list<array{id: int|string, libelle: string, nombreUtilisateurs: int|string}> $resultats */
        $resultats = $this->createQueryBuilder('g')
            ->select('g.id', 'g.libelle', 'COUNT(u.id) AS nombreUtilisateurs')
            ->leftJoin('g.utilisateurs', 'u')
            ->groupBy('g.id')
            ->addGroupBy('g.libelle')
            ->orderBy('g.libelle')
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map(static fn (array $groupe): array => [
            'id' => (int) $groupe['id'],
            'libelle' => $groupe['libelle'],
            'nombreUtilisateurs' => (int) $groupe['nombreUtilisateurs'],
        ], $resultats);
    }

    public function possedePrivilege(int $groupeId, string $privilege): bool
    {
        $nombre = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->join('g.privileges', 'p')
            ->where('g.id = :groupeId')
            ->andWhere('p.name = :privilege')
            ->setParameter('groupeId', $groupeId)
            ->setParameter('privilege', $privilege)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $nombre > 0;
    }
}

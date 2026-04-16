<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<News>
 *
 * @method News|null find($id, $lockMode = null, $lockVersion = null)
 * @method News|null findOneBy(array $criteria, array $orderBy = null)
 * @method News[]    findAll()
 * @method News[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(News $entity, bool $flush = true): void
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
    public function remove(News $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Récupère les messages visibles pour un groupe donné (flux dashboard).
     * Port direct de Service_Feed::getFeeds() (legacy).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findFeedsByGroupeId(int $groupeId, int $count = 5): array
    {
        return $this->_em->createQuery(
            'SELECT n, u, ui FROM App\Entity\News n
             JOIN n.utilisateur u
             JOIN u.informations ui
             WHERE EXISTS (
                 SELECT 1 FROM App\Entity\Groupe g
                 WHERE g.id = :groupeId AND n MEMBER OF g.news
             )
             ORDER BY n.id DESC'
        )
            ->setParameter('groupeId', $groupeId)
            ->setMaxResults($count)
            ->getArrayResult();
    }

    /**
     * Compte les messages visibles pour un groupe donné.
     * Port direct de Service_Feed::getFeeds($user, null, true) (legacy).
     */
    public function countFeedsByGroupeId(int $groupeId): int
    {
        return (int) $this->_em->createQuery(
            'SELECT COUNT(n.id) FROM App\Entity\News n
             WHERE EXISTS (
                 SELECT 1 FROM App\Entity\Groupe g
                 WHERE g.id = :groupeId AND n MEMBER OF g.news
             )'
        )
            ->setParameter('groupeId', $groupeId)
            ->getSingleScalarResult();
    }
}

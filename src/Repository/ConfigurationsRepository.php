<?php

namespace App\Repository;

use App\Entity\Configurations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Configurations>
 *
 * @method Configurations|null find($id, $lockMode = null, $lockVersion = null)
 * @method Configurations|null findOneBy(array $criteria, array $orderBy = null)
 * @method Configurations[]    findAll()
 * @method Configurations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigurationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Configurations::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Configurations $entity, bool $flush = true): void
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
    public function remove(Configurations $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Vérifie qu'il n'y a qu'une seule configuration en base.
     *
     * @throws \LogicException Si le nombre de lignes est différent de 1.
     */
    public function assertSingleConfigurationExists(): ?Configurations
    {
        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ((int) $count !== 1) {
            throw new \LogicException("La table 'configurations' doit contenir exactement une seule ligne. Actuellement : $count.");
        }

        $config = $this->findOneBy([]);

        return $config;
    }
}

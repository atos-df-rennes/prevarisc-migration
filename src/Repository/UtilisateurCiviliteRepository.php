<?php

namespace App\Repository;

use App\Entity\UtilisateurCivilite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UtilisateurCivilite>
 *
 * @method UtilisateurCivilite|null find($id, $lockMode = null, $lockVersion = null)
 * @method UtilisateurCivilite|null findOneBy(array $criteria, array $orderBy = null)
 * @method UtilisateurCivilite[]    findAll()
 * @method UtilisateurCivilite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtilisateurCiviliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtilisateurCivilite::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(UtilisateurCivilite $entity, bool $flush = true): void
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
    public function remove(UtilisateurCivilite $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

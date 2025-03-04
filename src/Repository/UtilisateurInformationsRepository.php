<?php

namespace App\Repository;

use App\Entity\UtilisateurInformations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtilisateurInformations::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(UtilisateurInformations $entity, bool $flush = true): void
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
    public function remove(UtilisateurInformations $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

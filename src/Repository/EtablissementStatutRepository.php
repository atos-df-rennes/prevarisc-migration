<?php

namespace App\Repository;

use App\Entity\EtablissementStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementStatut>
 *
 * @method EtablissementStatut|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementStatut|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementStatut[]    findAll()
 * @method EtablissementStatut[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementStatutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementStatut::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(EtablissementStatut $entity, bool $flush = true): void
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
    public function remove(EtablissementStatut $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

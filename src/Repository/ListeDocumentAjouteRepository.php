<?php

namespace App\Repository;

use App\Entity\ListeDocumentAjoute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListeDocumentAjoute>
 *
 * @method ListeDocumentAjoute|null find($id, $lockMode = null, $lockVersion = null)
 * @method ListeDocumentAjoute|null findOneBy(array $criteria, array $orderBy = null)
 * @method ListeDocumentAjoute[]    findAll()
 * @method ListeDocumentAjoute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ListeDocumentAjouteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeDocumentAjoute::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ListeDocumentAjoute $entity, bool $flush = true): void
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
    public function remove(ListeDocumentAjoute $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

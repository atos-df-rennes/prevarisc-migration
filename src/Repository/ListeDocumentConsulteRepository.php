<?php

namespace App\Repository;

use App\Entity\ListeDocumentConsulte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListeDocumentConsulte>
 *
 * @method ListeDocumentConsulte|null find($id, $lockMode = null, $lockVersion = null)
 * @method ListeDocumentConsulte|null findOneBy(array $criteria, array $orderBy = null)
 * @method ListeDocumentConsulte[]    findAll()
 * @method ListeDocumentConsulte[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ListeDocumentConsulteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeDocumentConsulte::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ListeDocumentConsulte $entity, bool $flush = true): void
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
    public function remove(ListeDocumentConsulte $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return array<ListeDocumentConsulte>
     */
    public function getDocVisiteRT(): array
    {
        $qb = $this->createQueryBuilder('ldc');
        $query = $qb->where('ldc.typereceptionTravaux = 1')
            ->orderBy('ldc.ordre', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * @return array<ListeDocumentConsulte>
     */
    public function getDocVisite(): array
    {
        $qb = $this->createQueryBuilder('ldc');
        $query = $qb->where('ldc.visite = 1')
            ->orderBy('ldc.ordre', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * @return array<ListeDocumentConsulte>
     */
    public function getDocEtude(): array
    {
        $qb = $this->createQueryBuilder('ldc');
        $query = $qb->where('ldc.etude = 1')
            ->orderBy('ldc.ordre', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * @return array<ListeDocumentConsulte>
     */
    public function getDocVisiteVAO(): array
    {
        $qb = $this->createQueryBuilder('ldc');
        $query = $qb->where('ldc.avantOuverture = 1')
            ->orderBy('ldc.ordre', 'ASC');

        return $query->getQuery()->getResult();
    }
}

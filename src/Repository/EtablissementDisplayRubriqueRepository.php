<?php

namespace App\Repository;

use App\Entity\EtablissementDisplayRubrique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementDisplayRubrique>
 *
 * @method EtablissementDisplayRubrique|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementDisplayRubrique|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementDisplayRubrique[]    findAll()
 * @method EtablissementDisplayRubrique[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementDisplayRubriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementDisplayRubrique::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(EtablissementDisplayRubrique $entity, bool $flush = true): void
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
    public function remove(EtablissementDisplayRubrique $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

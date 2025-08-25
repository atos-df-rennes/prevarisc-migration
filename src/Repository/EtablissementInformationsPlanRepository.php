<?php

namespace App\Repository;

use App\Entity\EtablissementInformationsPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementInformationsPlan>
 *
 * @method EtablissementInformationsPlan|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementInformationsPlan|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementInformationsPlan[]    findAll()
 * @method EtablissementInformationsPlan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementInformationsPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementInformationsPlan::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(EtablissementInformationsPlan $entity, bool $flush = true): void
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
    public function remove(EtablissementInformationsPlan $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}

<?php

namespace App\Repository;

use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dossier>
 *
 * @method Dossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dossier[]    findAll()
 * @method Dossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Dossier $entity, bool $flush = true): void
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
    public function remove(Dossier $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findDeleted(): array
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $query = $queryBuilder
            ->addSelect('u')
            ->addSelect('dt')
            ->addSelect('dn')
            ->addSelect('e')
            ->addSelect('ei')
            ->leftJoin('d.deletedBy', 'u')
            ->join('d.type', 'dt')
            ->join('d.nature', 'dn')
            ->join('d.etablissements', 'e')
            ->join('e.informations', 'ei')
            ->where($queryBuilder->expr()->isNotNull('d.dateSuppression'))
            ->andWhere('ei.date = (SELECT MAX(etabInfos.date) from App\Entity\EtablissementInformations etabInfos where etabInfos.etablissement = e.id)')
            ->orderBy('d.dateSuppression', 'DESC')
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function findDernierDossierDonnantAvisParEtablissement(int $idEtab): ?Dossier
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.etablissements', 'e')
            ->innerJoin('d.avisDossierCommission', 'adc')
            ->innerJoin('d.nature', 'n') 
            ->where('e.id = :idEtab')
            ->andWhere('n.id IN (:natureIds)')
            ->andWhere('adc.id IN (1,2)')
            ->andWhere('d.dateSuppression IS NULL')
            ->setParameter('idEtab', $idEtab)
            ->setParameter('natureIds', [7, 16, 17, 19, 21, 23, 24, 26, 28, 29, 47, 48])
            ->orderBy('d.dateCommission', 'DESC') 
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

}

<?php

namespace App\Repository;

use App\Entity\Etablissement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Etablissement>
 *
 * @method Etablissement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Etablissement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Etablissement[]    findAll()
 * @method Etablissement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etablissement::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Etablissement $entity, bool $flush = true): void
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
    public function remove(Etablissement $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return Etablissement[]
     */
    public function findDeleted(): array
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->addSelect('u')
            ->addSelect('ei')
            ->addSelect('ea')
            ->addSelect('ac')
            ->leftJoin('e.deletedBy', 'u')
            ->join('e.informations', 'ei')
            ->join('e.adresses', 'ea')
            ->join('ea.commune', 'ac')
            ->where($queryBuilder->expr()->isNotNull('e.dateSuppression'))
            ->andWhere('ei.date = (SELECT MAX(etabInfos.date) from App\Entity\EtablissementInformations etabInfos where etabInfos.etablissement = e.id)')
            ->orderBy('e.dateSuppression', 'DESC')
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function getHistoriqueInformationsData(int $id): Etablissement
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->addSelect('ei')
            ->join('e.informations', 'ei')
            ->leftJoin('ei.statut', 'es')
            ->leftJoin('ei.type', 'et')
            ->leftJoin('ei.categorie', 'ec')
            ->leftJoin('ei.classe', 'ecl')
            ->leftJoin('ei.famille', 'ef')
            ->leftJoin('ei.utilisateur', 'eu')
            ->where('e.id = :id')
            ->orderBy('ei.date', 'ASC')
            ->setParameter('id', $id)
            ->getQuery();

        return $query->getSingleResult();
    }

    public function getTextesApplicablesByType(int $id): ?Etablissement
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->addSelect('eta')
            ->addSelect('tta')
            ->join('e.textesApplicables', 'eta')
            ->join('eta.type', 'tta')
            ->where('e.id = :id')
            ->orderBy('eta.type', 'ASC')
            ->setParameter('id', $id)
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param int $id
     * @return array{int,array{'id':int,'dateCommission':string,'dateVisite':string,'idAvisDerogation':int,'titre':string,'type':string}}
     */
    public function getAvisDerogationsEtablissement(int $id): array
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->Select('d.id, d.dateCommission, d.dateVisite')
            ->addSelect('da.id as idAvisDerogation, da.titre, da.type')
            ->join('e.dossiers', 'd')
            ->join('d.avisDerogations', 'da')
            ->where('e.id = :id')
            ->andWhere('da.displayHistorique = 1')
            ->setParameter(':id', $id)
            ->getQuery();

        return $query->getResult();
    }
}

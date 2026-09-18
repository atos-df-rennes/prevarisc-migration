<?php

namespace App\Repository;

use App\Constant\PlacementEtablissementPj;
use App\Entity\Etablissement;
use App\Entity\EtablissementPieceJointe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementPieceJointe>
 *
 * @method EtablissementPieceJointe|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementPieceJointe|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementPieceJointe[]    findAll()
 * @method EtablissementPieceJointe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementPieceJointeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementPieceJointe::class);
    }

    /**
     * @return EtablissementPieceJointe[]
     */
    public function getPjByType(int $id, array $extensions, bool $inverse = false): array
    {
        $queryBuilder = $this->createQueryBuilder('epj');
        $queryBuilder
            ->addSelect('pj')
            ->join('epj.etablissement', 'e')
            ->join('epj.pieceJointe', 'pj')
            ->where('e.id = :id');
        if ($inverse) {
            $queryBuilder->andWhere('pj.extension not in (:extensions)');
        } else {
            $queryBuilder->andWhere('pj.extension in (:extensions)');
        }

        $query = $queryBuilder->setParameter('id', $id)
            ->setParameter('extensions', $extensions)
            ->getQuery();

        return $query->getResult();
    }

    public function getDiaporama(Etablissement $etablissement): array
    {
        $queryBuilder = $this->createQueryBuilder('etablissement_pj');
        $queryBuilder
            ->leftJoin('etablissement_pj.pieceJointe', 'pj')
            ->where('pj.extension IN (:extensions)')
            ->andWhere('etablissement_pj.placement = 1')
            ->andWhere('etablissement_pj.etablissement = :etablissement')
            ->setParameter('etablissement', $etablissement->getId())
            ->setParameter('extensions', ['.jpg', '.JPG', '.jpeg', '.png'])
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    public function getPlans(Etablissement $etablissement): array
    {
        $queryBuilder = $this->createQueryBuilder('etablissement_pj');
        $queryBuilder
            ->leftJoin('etablissement_pj.pieceJointe', 'pj')
            ->where('pj.extension IN (:extensions)')
            ->andWhere('etablissement_pj.placement = :placement')
            ->andWhere('etablissement_pj.etablissement = :etablissement')
            ->setParameter('placement', PlacementEtablissementPj::PLANS)
            ->setParameter('etablissement', $etablissement->getId())
            ->setParameter('extensions', ['.jpg', '.JPG', '.png'])
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}

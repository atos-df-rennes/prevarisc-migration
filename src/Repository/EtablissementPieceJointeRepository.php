<?php

namespace App\Repository;

use App\Entity\EtablissementPieceJointe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(EtablissementPieceJointe $entity, bool $flush = true): void
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
    public function remove(EtablissementPieceJointe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
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
}

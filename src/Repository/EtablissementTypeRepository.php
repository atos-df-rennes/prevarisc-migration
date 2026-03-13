<?php

namespace App\Repository;

use App\Entity\EtablissementActivite;
use App\Entity\EtablissementType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementType>
 *
 * @method EtablissementType|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementType|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementType[]    findAll()
 * @method EtablissementType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementType::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(EtablissementType $entity, bool $flush = true): void
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
    public function remove(EtablissementType $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return EtablissementType[] Returns an array of EtablissementType objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EtablissementType
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * Retourne toutes les activités ERP groupées par libellé de type, triées alphabétiquement.
     *
     * @return array<string, EtablissementActivite[]>
     */
    public function findActivitesGroupedByType(): array
    {
        /** @var EtablissementType[] $types */
        $types = $this->createQueryBuilder('t')
            ->leftJoin('t.activites', 'a')
            ->addSelect('a')
            ->orderBy('t.libelle', 'ASC')
            ->addOrderBy('a.libelle', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($types as $type) {
            /** @var EtablissementActivite[] $activites */
            $activites = $type->getActivites()->toArray();
            if ([] !== $activites) {
                $grouped[(string) $type->getLibelle()] = $activites;
            }
        }

        return $grouped;
    }
}

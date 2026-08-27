<?php

namespace App\Repository;

use App\Entity\EtablissementActivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementActivite>
 *
 * @method EtablissementActivite|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementActivite|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementActivite[]    findAll()
 * @method EtablissementActivite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementActivite::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(EtablissementActivite $entity, bool $flush = true): void
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
    public function remove(EtablissementActivite $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Retourne toutes les activités avec leur type (eager load), triées par type puis libellé.
     *
     * @return EtablissementActivite[]
     */
    public function findAllWithType(): array
    {
        /** @var EtablissementActivite[] $result */
        $result = $this->createQueryBuilder('a')
            ->addSelect('t')
            ->innerJoin('a.type', 't')
            ->orderBy('t.libelle', 'ASC')
            ->addOrderBy('a.libelle', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * @return array<int, array{ID_TYPEACTIVITE: int, LIBELLE_ACTIVITE: string, LIBELLE_TYPE: string}>
     */
    public function findAllWithTypeForSearch(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id AS ID_TYPEACTIVITE, a.libelle AS LIBELLE_ACTIVITE, t.libelle AS LIBELLE_TYPE')
            ->innerJoin('a.type', 't')
            ->orderBy('t.libelle', 'ASC')
            ->addOrderBy('a.libelle', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }
}

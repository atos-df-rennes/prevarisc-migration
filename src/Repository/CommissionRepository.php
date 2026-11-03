<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commission>
 *
 * @method Commission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commission[]    findAll()
 * @method Commission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionRepository extends ServiceEntityRepository implements HasContacts
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commission::class);
    }

    /**
     * Retourne toutes les commissions avec leur type (eager load), triées par type puis libellé.
     *
     * @return Commission[]
     */
    public function findAllWithType(): array
    {
        /** @var Commission[] $result */
        $result = $this->createQueryBuilder('c')
            ->addSelect('ct')
            ->innerJoin('c.type', 'ct')
            ->orderBy('ct.id', 'ASC')
            ->addOrderBy('c.libelle', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * Retourne les commissions groupées par type.
     * Port de Service_Commission::getCommissionsAndTypes().
     *
     * @return array<int, array{LIBELLE: string, ARRAY: array<int, array<string, mixed>>}>
     */
    public function getCommissionsAndTypes(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        /** @var array<int, array<string, mixed>> $types */
        $types = $conn->executeQuery(
            'SELECT ID_COMMISSIONTYPE, LIBELLE_COMMISSIONTYPE FROM commissiontype ORDER BY ID_COMMISSIONTYPE ASC'
        )->fetchAllAssociative();

        /** @var array<int, array{LIBELLE: string, ARRAY: array<int, array<string, mixed>>}> $result */
        $result = [];

        foreach ($types as $type) {
            $typeId = (int) $type['ID_COMMISSIONTYPE'];

            $result[$typeId] = [
                'LIBELLE' => (string) $type['LIBELLE_COMMISSIONTYPE'],
                'ARRAY' => [],
            ];
        }

        /** @var array<int, array<string, mixed>> $commissions */
        $commissions = $conn->executeQuery(
            'SELECT ID_COMMISSIONTYPE, ID_COMMISSION, LIBELLE_COMMISSION FROM commission WHERE ID_COMMISSIONTYPE IS NOT NULL ORDER BY ID_COMMISSIONTYPE ASC, LIBELLE_COMMISSION ASC'
        )->fetchAllAssociative();

        foreach ($commissions as $commission) {
            $typeId = (int) $commission['ID_COMMISSIONTYPE'];

            if (!isset($result[$typeId])) {
                continue;
            }

            $result[$typeId]['ARRAY'][] = [
                'ID_COMMISSION' => $commission['ID_COMMISSION'],
                'LIBELLE_COMMISSION' => $commission['LIBELLE_COMMISSION'],
            ];
        }

        return $result;
    }

    /**
     * Retourne le nombre de commissions ayant comme contact celui passé en paramètre.
     */
    public function findNumberByContacts(UtilisateurInformations $contact): int
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $query = $queryBuilder
            ->select('COUNT(c.id)')
            ->join('c.contacts', 'cc')
            ->where('cc.id = :id')
            ->setParameter('id', $contact->getId())
            ->getQuery()
        ;

        return $query->getSingleScalarResult();
    }

    /**
     * @return array<int, array{ID_COMMISSION: string, LIBELLE_COMMISSION: string}>
     */
    public function findAllForSearch(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id AS ID_COMMISSION, c.libelle AS LIBELLE_COMMISSION')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }
}

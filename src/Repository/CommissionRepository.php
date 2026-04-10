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

            /** @var array<int, array<string, mixed>> $commissions */
            $commissions = $conn->executeQuery(
                'SELECT ID_COMMISSION, LIBELLE_COMMISSION FROM commission WHERE ID_COMMISSIONTYPE = ? ORDER BY LIBELLE_COMMISSION ASC',
                [$typeId]
            )->fetchAllAssociative();

            $result[$typeId] = [
                'LIBELLE' => (string) $type['LIBELLE_COMMISSIONTYPE'],
                'ARRAY' => $commissions,
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
}

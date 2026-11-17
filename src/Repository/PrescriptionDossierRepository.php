<?php

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\PrescriptionDossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrescriptionDossier>
 *
 * @method PrescriptionDossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrescriptionDossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrescriptionDossier[]    findAll()
 * @method PrescriptionDossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionDossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionDossier::class);
    }

    /**
     * Retourne les prescriptions d'un dossier pour un type donné, avec toutes les associations chargées.
     *
     * Reproduit le comportement legacy :
     * - Prescriptions typées   : INNER JOIN sur les associations du type (exclusion si aucune)
     * - Prescriptions libres   : INNER JOIN sur les associations propres (exclusion si aucune)
     *
     * @return PrescriptionDossier[]
     */
    public function findByDossierAndType(Dossier $dossier, int $type): array
    {
        $typePrescriptions = $this->createQueryBuilder('pd')
            ->leftJoin('pd.prescriptionType', 'pt')->addSelect('pt')
            ->innerJoin('pt.associations', 'pta')->addSelect('pta')
            ->leftJoin('pta.texte', 'ptt')->addSelect('ptt')
            ->leftJoin('pta.article', 'ptar')->addSelect('ptar')
            ->where('pd.dossier = :dossier')
            ->andWhere('pd.type = :type')
            ->andWhere('pd.prescriptionType IS NOT NULL')
            ->setParameter('dossier', $dossier)
            ->setParameter('type', $type)
            ->orderBy('pd.numero', 'ASC')
            ->addOrderBy('pta.numero', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $librePrescriptions = $this->createQueryBuilder('pd')
            ->innerJoin('pd.associations', 'a')->addSelect('a')
            ->leftJoin('a.texte', 't')->addSelect('t')
            ->leftJoin('a.article', 'ar')->addSelect('ar')
            ->where('pd.dossier = :dossier')
            ->andWhere('pd.type = :type')
            ->andWhere('pd.prescriptionType IS NULL')
            ->setParameter('dossier', $dossier)
            ->setParameter('type', $type)
            ->orderBy('pd.numero', 'ASC')
            ->addOrderBy('a.numero', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $prescriptions = array_merge($typePrescriptions, $librePrescriptions);
        usort($prescriptions, fn (PrescriptionDossier $a, PrescriptionDossier $b): int => $a->getNumero() <=> $b->getNumero());

        return $prescriptions;
    }

    /**
     * Renumérote les prescriptions dans l'ordre du tableau d'IDs fourni, en commençant à $startAt.
     *
     * @param int[] $orderedIds
     */
    public function renumeroterParIds(Dossier $dossier, array $orderedIds, int $startAt = 1): void
    {
        $numero = $startAt;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            $this->createQueryBuilder('pd')
                ->update()
                ->set('pd.numero', ':numero')
                ->where('pd.id = :id')
                ->andWhere('pd.dossier = :dossier')
                ->setParameter('numero', $numero)
                ->setParameter('id', $id)
                ->setParameter('dossier', $dossier)
                ->getQuery()
                ->execute()
            ;

            ++$numero;
        }
    }

    /**
     * Retourne le numéro maximum de prescription pour un dossier et une liste de types.
     * Utilisé pour attribuer le prochain numéro lors d'une création.
     *
     * @param int[] $types
     */
    public function findMaxNumeroByDossierAndTypes(Dossier $dossier, array $types): int
    {
        $result = $this->createQueryBuilder('pd')
            ->select('MAX(pd.numero)')
            ->where('pd.dossier = :dossier')
            ->andWhere('pd.type IN (:types)')
            ->setParameter('dossier', $dossier)
            ->setParameter('types', $types)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }
}

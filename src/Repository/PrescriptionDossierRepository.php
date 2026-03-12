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
     * @return PrescriptionDossier[]
     */
    public function findByDossierAndType(Dossier $dossier, int $type): array
    {
        return $this->createQueryBuilder('pd')
            ->leftJoin('pd.associations', 'a')->addSelect('a')
            ->leftJoin('a.texte', 't')->addSelect('t')
            ->leftJoin('a.article', 'ar')->addSelect('ar')
            ->leftJoin('pd.prescriptionType', 'pt')->addSelect('pt')
            ->leftJoin('pt.associations', 'pta')->addSelect('pta')
            ->leftJoin('pta.texte', 'ptt')->addSelect('ptt')
            ->leftJoin('pta.article', 'ptar')->addSelect('ptar')
            ->where('pd.dossier = :dossier')
            ->andWhere('pd.type = :type')
            ->setParameter('dossier', $dossier)
            ->setParameter('type', $type)
            ->orderBy('pd.numero', 'ASC')
            ->addOrderBy('a.numero', 'ASC')
            ->addOrderBy('pta.numero', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}

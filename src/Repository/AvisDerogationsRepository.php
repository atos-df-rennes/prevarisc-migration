<?php

namespace App\Repository;

use App\Entity\AvisDerogations;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AvisDerogations|null find($id, $lockMode = null, $lockVersion = null)
 * @method AvisDerogations|null findOneBy(array $criteria, array $orderBy = null)
 * @method AvisDerogations[]    findAll()
 * @method AvisDerogations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<AvisDerogations>
 */
class AvisDerogationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvisDerogations::class);
    }

    /**
     * Retourne les avis et dérogations des dossiers d'un établissement ayant été indiqués comme historisés.
     *
     * @return AvisDerogations[]
     */
    public function findForEtablissement(int $idEtablissement): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('d')
            ->addSelect('e')
            ->addSelect('dl')
            ->addSelect('t')
            ->join('a.dossier', 'd')
            ->join('d.etablissements', 'e')
            ->leftJoin('a.dossierLie', 'dl')
            ->leftJoin('dl.type', 't')
            ->where('e.id = :idEtablissement')
            ->andWhere('a.displayHistorique = 1')
            ->setParameter('idEtablissement', $idEtablissement)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne les avis et dérogations d'un dossier, avec avis et dossier lié pré-chargés.
     *
     * @return AvisDerogations[]
     */
    public function findByDossier(Dossier $dossier): array
    {
        return $this->createQueryBuilder('ad')
            ->addSelect('av')
            ->addSelect('dl')
            ->leftJoin('ad.avis', 'av')
            ->leftJoin('ad.dossierLie', 'dl')
            ->leftJoin('dl.type', 'dlt')
            ->addSelect('dlt')
            ->where('ad.dossier = :dossier')
            ->setParameter('dossier', $dossier)
            ->orderBy('ad.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}

<?php

namespace App\Repository;

use App\Entity\AvisDerogations;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
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
     * Retourne les avis et dérogations des dossiers d'une liste d'établissements (parent + enfants)
     * ayant été indiqués comme historisés — en une seule requête (évite le N+1).
     *
     * @param int[] $idsEtablissements
     *
     * @return AvisDerogations[]
     */
    public function findForEtablissements(array $idsEtablissements): array
    {
        if (empty($idsEtablissements)) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->addSelect('d')
            ->addSelect('e')
            ->addSelect('dl')
            ->addSelect('t')
            ->join('a.dossier', 'd')
            ->join('d.etablissements', 'e')
            ->leftJoin('a.dossierLie', 'dl')
            ->leftJoin('dl.type', 't')
            ->where('e.id IN (:ids)')
            ->andWhere('a.displayHistorique = 1')
            ->setParameter('ids', $idsEtablissements, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Vérifie si au moins un avis/dérogation historisé existe pour une liste d'établissements
     * (parent + enfants). Doit rester cohérent avec findForEtablissements(), utilisée pour
     * l'affichage, afin que le témoin (pastille verte) reflète bien les données affichées.
     *
     * @param int[] $idsEtablissements
     */
    public function hasForEtablissements(array $idsEtablissements): bool
    {
        if ([] === $idsEtablissements) {
            return false;
        }

        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.dossier', 'd')
            ->join('d.etablissements', 'e')
            ->where('e.id IN (:ids)')
            ->andWhere('a.displayHistorique = 1')
            ->setParameter('ids', $idsEtablissements, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
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

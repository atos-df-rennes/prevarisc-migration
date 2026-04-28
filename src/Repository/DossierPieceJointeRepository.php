<?php

namespace App\Repository;

use App\Entity\DossierPieceJointe;
use App\Entity\PieceJointe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierPieceJointe>
 *
 * @method DossierPieceJointe|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierPieceJointe|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierPieceJointe[]    findAll()
 * @method DossierPieceJointe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierPieceJointeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierPieceJointe::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DossierPieceJointe $entity, bool $flush = true): void
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
    public function remove(DossierPieceJointe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Récupère toutes les pièces jointes d'un dossier, ordonnées par ID décroissant (plus récente en premier).
     *
     * @return DossierPieceJointe[]
     */
    public function findAllByDossier(int $idDossier): array
    {
        return $this->createQueryBuilder('dpj')
            ->addSelect('pj', 'pjs')
            ->innerJoin('dpj.pieceJointe', 'pj')
            ->leftJoin('pj.statut', 'pjs')
            ->where('dpj.dossier = :idDossier')
            ->setParameter('idDossier', $idDossier)
            ->orderBy('pj.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Récupère les pièces jointes d'un dossier par statut.
     *
     * @param int    $idDossier ID du dossier
     * @param string $statut    Nom du statut (ex: 'not_exported')
     *
     * @return PieceJointe[]
     */
    public function findPiecesJointesByStatut(int $idDossier, string $statut): array
    {
        $dossierPiecesJointes = $this->createQueryBuilder('dpj')
            ->addSelect('pj')
            ->innerJoin('dpj.pieceJointe', 'pj')
            ->innerJoin('pj.statut', 'pjs')
            ->where('dpj.dossier = :idDossier')
            ->andWhere('pjs.nomStatut = :statut')
            ->setParameter('idDossier', $idDossier)
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_filter(array_map(function (DossierPieceJointe $dpj): ?PieceJointe {
            return $dpj->getPieceJointe();
        }, $dossierPiecesJointes)));
    }

    /**
     * Récupère les pièces jointes exportables vers Plat'AU pour un dossier.
     *
     * Sont exportables :
     * - les PJ avec statut 'not_exported' (reçues via Plat'AU, pas encore envoyées)
     * - les PJ sans statut (ajoutées manuellement via l'onglet PJ)
     *
     * Le legacy exclut par inadvertance les PJ sans statut (INNER JOIN). Cette méthode
     * corrige ce comportement pour permettre l'envoi de toutes les PJ pertinentes.
     *
     * @param int $idDossier ID du dossier
     *
     * @return PieceJointe[]
     */
    public function findPiecesJointesExportables(int $idDossier): array
    {
        $dossierPiecesJointes = $this->createQueryBuilder('dpj')
            ->addSelect('pj', 'pjs')
            ->innerJoin('dpj.pieceJointe', 'pj')
            ->leftJoin('pj.statut', 'pjs')
            ->where('dpj.dossier = :idDossier')
            ->andWhere('pjs.nomStatut = :statut OR pjs.id IS NULL')
            ->setParameter('idDossier', $idDossier)
            ->setParameter('statut', 'not_exported')
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_filter(array_map(function (DossierPieceJointe $dpj): ?PieceJointe {
            return $dpj->getPieceJointe();
        }, $dossierPiecesJointes)));
    }
}

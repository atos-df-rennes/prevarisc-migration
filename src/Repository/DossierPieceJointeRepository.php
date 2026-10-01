<?php

namespace App\Repository;

use App\Entity\DossierPieceJointe;
use App\Entity\PieceJointe;
use App\Enum\PieceJointeStatutEnum;
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
     * Il y a une table de jointure (dossierpj) entre le dossier et les pièces jointes,
     * bien qu'une pièce jointe ne puisse en réalité être que sur un seul dossier.
     * On ne peut donc pas faire la requête depuis le Repository PieceJointe.
     *
     * @param int                   $idDossier ID du dossier
     * @param PieceJointeStatutEnum $statut    Statut recherché
     *
     * @return PieceJointe[]
     */
    public function findPiecesJointesByStatut(int $idDossier, PieceJointeStatutEnum $statut): array
    {
        $dossierPiecesJointes = $this->createQueryBuilder('dpj')
            ->addSelect('pj')
            ->innerJoin('dpj.pieceJointe', 'pj')
            ->innerJoin('pj.statut', 'pjs')
            ->where('dpj.dossier = :idDossier')
            ->andWhere('pjs.nomStatut = :statut')
            ->setParameter('idDossier', $idDossier)
            ->setParameter('statut', $statut->value)
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_filter(array_map(fn (DossierPieceJointe $dpj): ?PieceJointe => $dpj->getPieceJointe(), $dossierPiecesJointes)));
    }
}

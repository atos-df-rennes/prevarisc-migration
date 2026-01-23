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
     * Récupère les pièces jointes d'un dossier par statut.
     * Utilisé notamment pour récupérer les pièces jointes exportables vers Plat'AU.
     *
     * Il y a une table de jointure (dossierpj) entre le dossier et les pièces jointes,
     * bien qu'une pièce jointe ne puisse en réalité être que sur un seul dossier.
     * On ne peut donc pas faire la requête depuis le Repository PieceJointe.
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

        // Extrait les PieceJointe depuis les DossierPieceJointe
        return array_map(function (DossierPieceJointe $dpj): ?PieceJointe {
            return $dpj->getPieceJointe();
        }, $dossierPiecesJointes);
    }
}

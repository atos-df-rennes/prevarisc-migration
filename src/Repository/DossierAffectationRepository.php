<?php

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\DossierAffectation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierAffectation>
 *
 * @method DossierAffectation|null find($id, $lockMode = null, $lockVersion = null)
 * @method DossierAffectation|null findOneBy(array $criteria, array $orderBy = null)
 * @method DossierAffectation[]    findAll()
 * @method DossierAffectation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierAffectationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierAffectation::class);
    }

    /**
     * Récupère l'affectation d'un dossier pour un ou plusieurs types d'événement.
     *
     * @param Dossier   $dossier          Dossier concerné
     * @param int|int[] $idTypesEvenement ID(s) type événement (1=salle, 2=visite, 3=groupe)
     *
     * @return DossierAffectation|null Affectation trouvée ou null
     */
    public function findAffectationByDossierAndType(Dossier $dossier, $idTypesEvenement): ?DossierAffectation
    {
        $types = is_array($idTypesEvenement) ? $idTypesEvenement : [$idTypesEvenement];

        return $this->createQueryBuilder('da')
            ->join('da.dateCommission', 'dc')
            ->join('dc.typeEvenement', 'te')
            ->where('da.dossier = :dossier')
            ->andWhere('te.id IN (:typesEvenement)')
            ->setParameter('dossier', $dossier)
            ->setParameter('typesEvenement', $types)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime toutes les affectations d'un dossier.
     *
     * @param Dossier $dossier Dossier dont on veut supprimer les affectations
     *
     * @return int Nombre d'affectations supprimées
     */
    public function deleteAllAffectations(Dossier $dossier): int
    {
        return $this->createQueryBuilder('da')
            ->delete()
            ->where('da.dossier = :dossier')
            ->setParameter('dossier', $dossier)
            ->getQuery()
            ->execute();
    }
}

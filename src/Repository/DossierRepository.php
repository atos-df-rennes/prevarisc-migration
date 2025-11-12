<?php

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\Etablissement;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dossier>
 *
 * @method Dossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dossier[]    findAll()
 * @method Dossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierRepository extends ServiceEntityRepository implements HasContacts
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    public function findDeleted(): array
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $query = $queryBuilder
            ->addSelect('u')
            ->addSelect('dt')
            ->addSelect('dn')
            ->addSelect('e')
            ->addSelect('ei')
            ->leftJoin('d.deletedBy', 'u')
            ->join('d.type', 'dt')
            ->join('d.nature', 'dn')
            ->join('d.etablissements', 'e')
            ->join('e.informations', 'ei')
            ->where($queryBuilder->expr()->isNotNull('d.dateSuppression'))
            ->andWhere('ei.date = (SELECT MAX(etabInfos.date) from App\Entity\EtablissementInformations etabInfos where etabInfos.etablissement = e.id)')
            ->orderBy('d.dateSuppression', 'DESC')
            ->getQuery()
        ;

        return $query->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function getHistoriqueDossiersData(int $idEtablissement): array
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $query = $queryBuilder
            ->addSelect('e')
            ->addSelect('COALESCE(d.dateCommission, d.dateVisite) as hidden columnOrder')
            ->addSelect('dn')
            ->addSelect('da')
            ->addSelect('c')
            ->join('d.etablissements', 'e')
            ->join('d.nature', 'dn')
            ->join('d.avisCommission', 'da')
            ->leftJoin('d.createur', 'c')
            ->where('e.id = :id')
            ->andWhere('dn.id in (:nature)')
            ->andWhere('da.id in (1,2)')
            ->andWhere('d.dateCommission is not null OR d.dateVisite is not null')
            ->orderBy('columnOrder', 'ASC')
            ->setParameter('id', $idEtablissement)
            ->setParameter('nature', DossierNatureListe::NATURE_DOSSIERS_DONNANT_AVIS)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Retourne le nombre de dossiers ayant comme contact celui passé en paramètre.
     */
    public function findNumberByContacts(UtilisateurInformations $contact): int
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $query = $queryBuilder
            ->select('COUNT(d.id)')
            ->join('d.contacts', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $contact->getId())
            ->getQuery()
        ;

        return $query->getSingleScalarResult();
    }

    /**
     * Récupère la dernière visite périodique d'un établissement.
     */
    public function getLastVisitePeriodique(int $idEtablissement, bool $useDateCommissionForPeriodicity = false): ?array
    {
        $this->getEntityManager()->getFilters()->enable('dossier_non_supprime');

        $queryBuilder = $this->createQueryBuilder('dossier');

        if ($useDateCommissionForPeriodicity) {
            $queryBuilder->addSelect(
                'CASE WHEN dossier.dateCommission >= dossier.dateVisite THEN dossier.dateCommission ELSE dossier.dateVisite END AS date_relance'
            );
            $queryBuilder->orderBy('date_relance', 'DESC');
        } else {
            $queryBuilder->addSelect('dossier.dateVisite AS date_relance');
            $queryBuilder->orderBy('dossier.dateVisite', 'DESC');
        }

        $queryBuilder
            ->join('dossier.etablissements', 'etablissement')
            ->join('dossier.type', 'type')
            ->join('dossier.nature', 'nature')
            ->where('etablissement.id = :idEtablissement')
            ->andWhere('type.id IN (:types)')
            ->andWhere('dossier.avisCommission > 0')
            ->andWhere('nature.id IN (:natures)')
            ->setParameter('idEtablissement', $idEtablissement)
            ->setParameter('types', [2, 3]) // Visite et Groupe de visite
            ->setParameter('natures', [21, 26, 47, 48]) // Natures périodiques
        ;

        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère le PC initial d'un établissement.
     */
    public function getPCInitial(Etablissement $etablissement): ?\DateTime
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder
            ->join('d.etablissements', 'e')
            ->join('d.type', 'dt')
            ->join('d.nature', 'dn')
            ->where('e.id = :idEtablissement')
            ->andWhere('dt.id = 1') // Type étude
            ->andWhere('dn.id = 1') // Nature PC
            ->setParameter('idEtablissement', $etablissement->getId())
            ->orderBy('d.dateInsert', 'ASC')
            ->setMaxResults(1)
        ;

        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return $result === null ? null : $result->getDateInsert();
    }

    /**
     * Vérifie si un établissement a un dossier avec avis différé.
     */
    public function hasAvisDiffere(int $idEtablissement): bool
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder
            ->select('COUNT(d.id)')
            ->join('d.etablissements', 'e')
            ->where('e.id = :idEtablissement')
            ->andWhere('d.avisDiffere = 1')
            ->setParameter('idEtablissement', $idEtablissement)
        ;

        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Vérifie si un établissement a un dossier de type écheancier.
     */
    public function hasEcheancier(int $idEtablissement): bool
    {
        $queryBuilder = $this->createQueryBuilder('d');

        $queryBuilder
            ->select('COUNT(d.id)')
            ->join('d.etablissements', 'e')
            ->join('d.nature', 'dn')
            ->where('e.id = :idEtablissement')
            ->andWhere('dn.id = 46') // Nature échéancier
            ->setParameter('idEtablissement', $idEtablissement)
        ;

        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        return $count > 0;
    }
}

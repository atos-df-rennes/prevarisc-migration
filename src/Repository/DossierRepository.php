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
     * Recherche la dernière visite d'un établissement en fonction des critères spécifiés.
     *
     * @param Etablissement $etablissement L'établissement pour lequel la dernière visite est recherchée.
     *
     * @return \DateTime|null Retourne l'objet correspondant à la dernière visite trouvée ou null si aucune visite ne correspond.
     */
    public function findLastVisite(Etablissement $etablissement) {
        $queryBuilder = $this->createQueryBuilder('d')
            ->join('d.etablissements', 'etablissement')
            ->join('d.type', 'type')
            ->join('d.nature', 'nature')
            ->andWhere('etablissement.id = :etablissement')
            ->andWhere('type.id IN (:types)')
            ->andWhere('nature.id IN (:natures)')
            ->andWhere('d.avisCommission > 0')
            ->setParameter('etablissement', $etablissement->getId())
            ->setParameter('types', [2, 3]) // 2: Visite, 3: Groupe de visite
            ->setParameter('natures', [21, 26, 47, 48])
            ->orderBy('d.dateVisite', 'DESC')
            ->setMaxResults(1)
        ;

        $lastVisite = $queryBuilder->getQuery()->getOneOrNullResult();

        return $lastVisite ? $lastVisite->getDateVisite() : null;
    }

}

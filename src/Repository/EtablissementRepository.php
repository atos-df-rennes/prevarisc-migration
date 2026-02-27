<?php

namespace App\Repository;

use App\Entity\Etablissement;
use App\Entity\UtilisateurInformations;
use App\Interfaces\HasContacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Etablissement>
 *
 * @method Etablissement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Etablissement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Etablissement[]    findAll()
 * @method Etablissement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementRepository extends ServiceEntityRepository implements HasContacts
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etablissement::class);
    }

    /**
     * @return Etablissement[]
     */
    public function findDeleted(): array
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->addSelect('u')
            ->addSelect('ei')
            ->addSelect('ea')
            ->addSelect('ac')
            ->join('e.informations', 'ei')
            ->leftJoin('e.deletedBy', 'u')
            ->leftJoin('e.adresses', 'ea')
            ->leftJoin('ea.commune', 'ac')
            ->where($queryBuilder->expr()->isNotNull('e.dateSuppression'))
            ->andWhere('ei.date = (SELECT MAX(etabInfos.date) from App\Entity\EtablissementInformations etabInfos where etabInfos.etablissement = e)')
            ->orderBy('e.dateSuppression', 'DESC')
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function getHistoriqueInformationsData(int $id): Etablissement
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->addSelect('ei')
            ->addSelect('es')
            ->addSelect('et')
            ->addSelect('ec')
            ->addSelect('ecl')
            ->addSelect('ef')
            ->addSelect('eu')
            ->join('e.informations', 'ei')
            ->leftJoin('ei.statut', 'es')
            ->leftJoin('ei.type', 'et')
            ->leftJoin('ei.categorie', 'ec')
            ->leftJoin('ei.classe', 'ecl')
            ->leftJoin('ei.famille', 'ef')
            ->leftJoin('ei.utilisateur', 'eu')
            ->where('e.id = :id')
            ->orderBy('ei.date', 'ASC')
            ->setParameter('id', $id)
            ->getQuery();

        return $query->getSingleResult();
    }

    public function getTextesApplicablesByType(int $id): ?Etablissement
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->addSelect('eta')
            ->addSelect('tta')
            ->join('e.textesApplicables', 'eta')
            ->join('eta.type', 'tta')
            ->where('e.id = :id')
            ->orderBy('eta.type', 'ASC')
            ->setParameter('id', $id)
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * Retourne l'établissement demandé avec la liste des contacts ordonnée par nom.
     */
    public function findContactsOrderedByNom(int $id): ?Etablissement
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->addSelect('c')
            ->addSelect('f')
            ->join('e.contacts', 'c')
            ->join('c.fonction', 'f')
            ->where('e.id = :id')
            ->orderBy('c.nom', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }

    /**
     * Retourne le nombre d'établissements ayant comme contact celui passé en paramètre.
     */
    public function findNumberByContacts(UtilisateurInformations $contact): int
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->select('COUNT(e.id)')
            ->join('e.contacts', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $contact->getId())
            ->getQuery()
        ;

        return $query->getSingleScalarResult();
    }

    /**
     * Vérifie si un établissement possède au moins un avis de dérogation visible dans l'historique.
     */
    public function hasAvisDerogationsVisibles(int $etablissementId): bool
    {
        $count = $this->createQueryBuilder('e')
            ->select('COUNT(ad.id)')
            ->join('e.dossiers', 'd')
            ->join('d.avisDerogations', 'ad')
            ->where('e.id = :id')
            ->andWhere('ad.displayHistorique = true')
            ->setParameter('id', $etablissementId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}

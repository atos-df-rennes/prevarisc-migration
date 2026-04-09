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
            ->addOrderBy('c.prenom', 'ASC')
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

    /**
     * Retourne les établissements fils triés par libellé de leur dernière information (ID max).
     *
     * La sous-requête DQL isole la dernière EtablissementInformations de chaque fils,
     * et addSelect('info') permet un eager load évitant les requêtes N+1 dans le template.
     *
     * @return Etablissement[]
     */
    public function findFilsOrderedByLibelle(Etablissement $etablissement): array
    {
        return $this->createQueryBuilder('fils')
            ->innerJoin('fils.parents', 'parent')
            ->innerJoin(
                'fils.informations',
                'info',
                'WITH',
                'info.id = (
                    SELECT MAX(info_sub.id)
                    FROM App\Entity\EtablissementInformations info_sub
                    WHERE info_sub.etablissement = fils
                )'
            )
            ->addSelect('info')
            ->where('parent = :parent')
            ->orderBy('info.libelle', 'ASC')
            ->setParameter('parent', $etablissement)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des établissements par libellé (dernières informations) pour autocomplete.
     *
     * @return Etablissement[]
     */
    public function searchByLibelle(string $query, int $limit = 50): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin(
                'e.informations',
                'info',
                'WITH',
                'info.id = (
                    SELECT MAX(info_sub.id)
                    FROM App\Entity\EtablissementInformations info_sub
                    WHERE info_sub.etablissement = e
                )'
            )
            ->addSelect('info')
            ->where('info.libelle LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->andWhere('e.dateSuppression IS NULL')
            ->orderBy('info.libelle', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

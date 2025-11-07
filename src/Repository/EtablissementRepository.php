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
            ->leftJoin('e.deletedBy', 'u')
            ->join('e.informations', 'ei')
            ->join('e.adresses', 'ea')
            ->join('ea.commune', 'ac')
            ->where($queryBuilder->expr()->isNotNull('e.dateSuppression'))
            ->andWhere('ei.date = (SELECT MAX(etabInfos.date) from App\Entity\EtablissementInformations etabInfos where etabInfos.etablissement = e.id)')
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
     * @return array<int, array{'id': int, 'dateCommission': ?string, 'dateVisite': ?string, 'idAvisDerogation': int, 'titre': string, 'type': string}>
     */
    public function getAvisDerogationsEtablissement(int $id): array
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->select('d.id, d.dateCommission, d.dateVisite')
            ->addSelect('da.id as idAvisDerogation, da.titre, da.type')
            ->join('e.dossiers', 'd')
            ->join('d.avisDerogations', 'da')
            ->where('e.id = :id')
            ->andWhere('da.displayHistorique = 1')
            ->setParameter(':id', $id)
            ->getQuery();

        return $query->getResult();
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

    public function findByIdWithJoinedData(int $id): ?Etablissement
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->addSelect('ei')
            ->addSelect('ea')
            ->addSelect('ac')
            ->addSelect('es')
            ->addSelect('et')
            ->addSelect('ec')
            ->addSelect('ecl')
            ->addSelect('ef')
            ->addSelect('c')
            ->addSelect('cf')
            ->addSelect('ta')
            ->addSelect('tat')
            ->addSelect('pj')
            ->addSelect('dr')
            ->addSelect('fils')
            ->addSelect('parents')
            ->addSelect('dossiers')
            ->leftJoin('e.informations', 'ei')
            ->leftJoin('e.adresses', 'ea')
            ->leftJoin('ea.commune', 'ac')
            ->leftJoin('ei.statut', 'es')
            ->leftJoin('ei.type', 'et')
            ->leftJoin('ei.categorie', 'ec')
            ->leftJoin('ei.classe', 'ecl')
            ->leftJoin('ei.famille', 'ef')
            ->leftJoin('e.contacts', 'c')
            ->leftJoin('c.fonction', 'cf')
            ->leftJoin('e.textesApplicables', 'ta')
            ->leftJoin('ta.type', 'tat')
            ->leftJoin('e.piecesJointes', 'pj')
            ->leftJoin('e.displayRubriques', 'dr')
            ->leftJoin('e.fils', 'fils')
            ->leftJoin('e.parents', 'parents')
            ->leftJoin('e.dossiers', 'dossiers')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }
}

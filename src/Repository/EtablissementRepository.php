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
    public function findContactsOrderedByNom(int $id): Etablissement
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->addSelect('c')
            ->addSelect('f')
            ->leftJoin('e.contacts', 'c')
            ->leftJoin('c.fonction', 'f')
            ->where('e.id = :id')
            ->orderBy('c.nom', 'ASC')
            ->addOrderBy('c.prenom', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
        ;

        return $query->getSingleResult();
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

    /**
     * Construit le SQL de base pour la recherche d'établissements (port de Model_DbTable_Search::setItem('etablissement')).
     * Sélectionne tous les champs attendus par les templates de tableau de bord.
     */
    private function buildEtablissementBaseSql(bool $getCount): string
    {
        if ($getCount) {
            $select = 'SELECT COUNT(DISTINCT e.ID_ETABLISSEMENT) as count';
        } else {
            $select = 'SELECT
                e.ID_ETABLISSEMENT, e.NUMEROID_ETABLISSEMENT, e.DUREEVISITE_ETABLISSEMENT, e.NBPREV_ETABLISSEMENT,
                (SELECT COUNT(el2.ID_FILS_ETABLISSEMENT)
                    FROM etablissement etmp
                    INNER JOIN etablissementlie el2 ON etmp.ID_ETABLISSEMENT = el2.ID_ETABLISSEMENT
                    WHERE etmp.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT) AS NB_ENFANTS,
                etablissementinformations.*,
                dossier.DATEVISITE_DOSSIER, dossier.DATECOMM_DOSSIER, dossier.DATEINSERT_DOSSIER,
                avis.ID_AVIS, avis.LIBELLE_AVIS,
                type.LIBELLE_TYPE,
                genre.LIBELLE_GENRE,
                etablissementlie.ID_ETABLISSEMENT AS pere, etablissementlie.ID_FILS_ETABLISSEMENT,
                utilisateur.ID_UTILISATEUR,
                etablissementadresse.NUMINSEE_COMMUNE, etablissementadresse.LON_ETABLISSEMENTADRESSE,
                etablissementadresse.LAT_ETABLISSEMENTADRESSE, etablissementadresse.ID_ADRESSE, etablissementadresse.ID_RUE,
                adressecommune.LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_DEFAULT,
                etablissementadressesite.ID_RUE AS ID_RUE_SITE,
                adressecommunesite.LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_SITE,
                etablissementadressecell.ID_RUE AS ID_RUE_CELL,
                adressecommunecell.LIBELLE_COMMUNE AS LIBELLE_COMMUNE_ADRESSE_CELLULE';
        }

        return $select.'
            FROM etablissement e
            JOIN etablissementinformations ON e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT
                AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei
                    WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                )
            LEFT JOIN dossier ON e.ID_DOSSIER_DONNANT_AVIS = dossier.ID_DOSSIER
            LEFT JOIN avis ON dossier.AVIS_DOSSIER_COMMISSION = avis.ID_AVIS
            LEFT JOIN type ON etablissementinformations.ID_TYPE = type.ID_TYPE
            JOIN genre ON etablissementinformations.ID_GENRE = genre.ID_GENRE
            LEFT JOIN etablissementlie ON e.ID_ETABLISSEMENT = etablissementlie.ID_FILS_ETABLISSEMENT
            LEFT JOIN etablissementinformationspreventionniste
                ON etablissementinformationspreventionniste.ID_ETABLISSEMENTINFORMATIONS = etablissementinformations.ID_ETABLISSEMENTINFORMATIONS
            LEFT JOIN utilisateur ON utilisateur.ID_UTILISATEUR = etablissementinformationspreventionniste.ID_UTILISATEUR
            LEFT JOIN etablissementadresse ON e.ID_ETABLISSEMENT = etablissementadresse.ID_ETABLISSEMENT
            LEFT JOIN adressecommune ON etablissementadresse.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE
            LEFT JOIN etablissementadresse etablissementadressesite
                ON etablissementadressesite.ID_ETABLISSEMENT = (
                    SELECT ID_FILS_ETABLISSEMENT FROM etablissementlie el3
                    WHERE el3.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT LIMIT 1
                )
            LEFT JOIN adressecommune adressecommunesite
                ON etablissementadressesite.NUMINSEE_COMMUNE = adressecommunesite.NUMINSEE_COMMUNE
            LEFT JOIN etablissementadresse etablissementadressecell
                ON etablissementadressecell.ID_ETABLISSEMENT = (
                    SELECT el4.ID_ETABLISSEMENT FROM etablissementlie el4
                    WHERE el4.ID_FILS_ETABLISSEMENT = e.ID_ETABLISSEMENT LIMIT 1
                )
            LEFT JOIN adressecommune adressecommunecell
                ON etablissementadressecell.NUMINSEE_COMMUNE = adressecommunecell.NUMINSEE_COMMUNE
            WHERE e.DATESUPPRESSION_ETABLISSEMENT IS NULL';
    }

    /**
     * Retourne les ERP ouverts sous avis défavorable pour le tableau de bord.
     * Port direct de Model_DbTable_Etablissement::listeDesERPOuvertsSousAvisDefavorable().
     *
     * @param int[]|null $commissionIds
     *
     * @return array<int, array<string, mixed>>|int
     */
    public function findERPOuvertsSousAvisDefavorable(?array $commissionIds = null, ?string $numInseeCommune = null, ?int $idUtilisateur = null, bool $getCount = false)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildEtablissementBaseSql($getCount);
        $params = [];

        $sql .= ' AND avis.ID_AVIS = 2';
        $sql .= ' AND etablissementinformations.ID_GENRE = 2';
        $sql .= ' AND etablissementinformations.ID_STATUT = 2';

        if (null !== $numInseeCommune) {
            $sql .= ' AND etablissementadresse.NUMINSEE_COMMUNE = :numInseeCommune';
            $params['numInseeCommune'] = $numInseeCommune;
        }

        if (null !== $commissionIds && [] !== $commissionIds) {
            $inList = implode(',', array_map('intval', $commissionIds));
            $sql .= ' AND etablissementinformations.ID_COMMISSION IN ('.$inList.')';
        }

        if (null !== $idUtilisateur) {
            $sql .= ' AND utilisateur.ID_UTILISATEUR = :idUtilisateur';
            $params['idUtilisateur'] = $idUtilisateur;
        }

        if ($getCount) {
            $count = $conn->fetchOne($sql, $params);

            return is_numeric($count) ? (int) $count : 0;
        }

        $sql .= ' GROUP BY e.ID_ETABLISSEMENT
            ORDER BY CAST(etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS AS UNSIGNED),
                     etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, $params);

        return $rows;
    }

    /**
     * Retourne les ERP ouverts sans prochaines visites périodiques pour le tableau de bord.
     * Port direct de Model_DbTable_Etablissement::listeErpOuvertsSansProchainesVisitePeriodiques().
     *
     * @param int[] $commissionsUser
     *
     * @return array<int, array<string, mixed>>
     */
    public function findERPOuvertsSansProchainesVisitePeriodiques(array $commissionsUser): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $useDateCommissionForPeriodicity = filter_var(
            (string) getenv('PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE'),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($useDateCommissionForPeriodicity) {
            $periodicityCondition = "IF(
                dossiers.DATECOMM_DOSSIER >= dossiers.DATEVISITE_DOSSIER,
                DATE_FORMAT(DATE_ADD(dossiers.DATECOMM_DOSSIER, INTERVAL etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS MONTH), '%Y-%m'),
                DATE_FORMAT(DATE_ADD(dossiers.DATEVISITE_DOSSIER, INTERVAL etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS MONTH), '%Y-%m')
            )";
        } else {
            $periodicityCondition = "DATE_FORMAT(DATE_ADD(dossiers.DATEVISITE_DOSSIER, INTERVAL etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS MONTH), '%Y-%m')";
        }

        $sql = $this->buildEtablissementBaseSql(false);

        // joinEtablissementDossier() equivalent
        $sql .= '
            LEFT JOIN etablissementdossier ON etablissementdossier.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
            LEFT JOIN dossier dossiers ON dossiers.ID_DOSSIER = etablissementdossier.ID_DOSSIER
            LEFT JOIN dossiernature ON dossiernature.ID_DOSSIER = dossiers.ID_DOSSIER';

        $sql .= ' AND dossiers.DATEVISITE_DOSSIER = (
                SELECT MAX(dos.DATEVISITE_DOSSIER) FROM dossier AS dos
                LEFT JOIN etablissementdossier etabdoss ON etabdoss.ID_DOSSIER = dos.ID_DOSSIER
                LEFT JOIN dossiernature dn ON dn.ID_DOSSIER = dos.ID_DOSSIER
                WHERE etabdoss.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                AND dos.TYPE_DOSSIER IN(2,3)
                AND dn.ID_NATURE IN (21,23,24,26,28,29,47,48))';
        $sql .= ' AND etablissementinformations.ID_STATUT = 2';
        $sql .= ' AND etablissementinformations.ID_GENRE = 2';
        $sql .= ' AND etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS > 0';

        $params = [];
        if ([] !== $commissionsUser) {
            $inList = implode(',', array_map('intval', $commissionsUser));
            $sql .= ' AND etablissementinformations.ID_COMMISSION IN ('.$inList.')';
        }

        $sql .= sprintf(" AND %s < DATE_FORMAT(CURDATE(), '%%Y-%%m')", $periodicityCondition);
        $sql .= ' GROUP BY e.ID_ETABLISSEMENT
            ORDER BY CAST(etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS AS UNSIGNED),
                     etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, $params);

        return $rows;
    }

    /**
     * Retourne les ERP ouverts sans préventionniste assigné pour le tableau de bord.
     * Port direct de Model_DbTable_Etablissement::listeERPSansPreventionniste().
     *
     * @return array<int, array<string, mixed>>|int
     */
    public function findERPSansPreventionniste(bool $getCount = false)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildEtablissementBaseSql($getCount);

        $sql .= ' AND etablissementinformations.ID_STATUT = 2';
        $sql .= ' AND utilisateur.ID_UTILISATEUR IS NULL';
        $sql .= ' AND etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS > 0';

        if ($getCount) {
            $count = $conn->fetchOne($sql, []);

            return is_numeric($count) ? (int) $count : 0;
        }

        $sql .= ' GROUP BY e.ID_ETABLISSEMENT
            ORDER BY CAST(etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS AS UNSIGNED),
                     etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, []);

        return $rows;
    }

    /**
     * Retourne les ERP suivis par un utilisateur (préventionniste).
     * Port direct de Service_Dashboard::getERPSuivis().
     * Union des 4 catégories : ERP 1-4, ERP 5e cat défavorable, ERP 5e cat local sommeil, BUP/IGH/HAB.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findERPSuivisByUser(int $idUtilisateur): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildEtablissementBaseSql(false);
        $sql .= ' AND utilisateur.ID_UTILISATEUR = :idUtilisateur';
        $sql .= ' AND etablissementinformations.ID_STATUT IN (2,4)';
        $sql .= ' AND etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS > 0';
        $sql .= ' AND (
            (etablissementinformations.ID_CATEGORIE IN (1,2,3,4) AND etablissementinformations.ID_GENRE = 2)
            OR (etablissementinformations.ID_CATEGORIE = 5 AND etablissementinformations.ID_GENRE = 2 AND avis.ID_AVIS = 2)
            OR (etablissementinformations.ID_CATEGORIE = 5 AND etablissementinformations.ID_GENRE = 2 AND etablissementinformations.LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS = 1)
            OR (etablissementinformations.ID_GENRE IN (4,5,6,7,8,9,10))
        )';
        $sql .= ' GROUP BY e.ID_ETABLISSEMENT
            ORDER BY CAST(etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS AS UNSIGNED),
                     etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['idUtilisateur' => $idUtilisateur]);

        return $rows;
    }

    /**
     * Compte les ERP suivis par un utilisateur (préventionniste).
     */
    public function countERPSuivisByUser(int $idUtilisateur): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->buildEtablissementBaseSql(true);
        $sql .= ' AND utilisateur.ID_UTILISATEUR = :idUtilisateur';
        $sql .= ' AND etablissementinformations.ID_STATUT IN (2,4)';
        $sql .= ' AND etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS > 0';
        $sql .= ' AND (
            (etablissementinformations.ID_CATEGORIE IN (1,2,3,4) AND etablissementinformations.ID_GENRE = 2)
            OR (etablissementinformations.ID_CATEGORIE = 5 AND etablissementinformations.ID_GENRE = 2 AND avis.ID_AVIS = 2)
            OR (etablissementinformations.ID_CATEGORIE = 5 AND etablissementinformations.ID_GENRE = 2 AND etablissementinformations.LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS = 1)
            OR (etablissementinformations.ID_GENRE IN (4,5,6,7,8,9,10))
        )';
        $count = $conn->fetchOne($sql, ['idUtilisateur' => $idUtilisateur]);

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * Compte les ERP ouverts sous avis défavorable.
     *
     * @param int[]|null $commissionIds
     */
    public function countERPOuvertsSousAvisDefavorable(?array $commissionIds = null, ?string $numInseeCommune = null, ?int $idUtilisateur = null): int
    {
        $result = $this->findERPOuvertsSousAvisDefavorable($commissionIds, $numInseeCommune, $idUtilisateur, true);

        return is_int($result) ? $result : 0;
    }

    /**
     * Compte les ERP sans préventionniste assigné.
     */
    public function countERPSansPreventionniste(): int
    {
        $result = $this->findERPSansPreventionniste(true);

        return is_int($result) ? $result : 0;
    }
}

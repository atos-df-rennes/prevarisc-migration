<?php

namespace App\Repository;

use App\Constant\GenreEtablissement;
use App\Entity\EtablissementInformations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtablissementInformations>
 *
 * @method EtablissementInformations|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtablissementInformations|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtablissementInformations[]    findAll()
 * @method EtablissementInformations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtablissementInformationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtablissementInformations::class);
    }

    /**
     * Supprime en masse les préventionnistes des établissements d'une ou plusieurs communes.
     *
     * @param array<string> $communesNumInsee
     */
    public function deletePreventionnistesByCommunes(array $communesNumInsee): int
    {
        if (empty($communesNumInsee)) {
            return 0;
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = 'DELETE eip FROM etablissementinformationspreventionniste eip
                INNER JOIN etablissementinformations ei ON eip.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS
                INNER JOIN etablissement e ON ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                INNER JOIN etablissementadresse ea ON e.ID_ETABLISSEMENT = ea.ID_ETABLISSEMENT
                INNER JOIN (
                    SELECT ID_ETABLISSEMENT, MAX(DATE_ETABLISSEMENTINFORMATIONS) as MAX_DATE
                    FROM etablissementinformations
                    GROUP BY ID_ETABLISSEMENT
                ) ei_max ON ei.ID_ETABLISSEMENT = ei_max.ID_ETABLISSEMENT
                    AND ei.DATE_ETABLISSEMENTINFORMATIONS = ei_max.MAX_DATE
                WHERE ea.NUMINSEE_COMMUNE IN (:communes)';

        return $conn->executeStatement($sql, ['communes' => $communesNumInsee], ['communes' => ArrayParameterType::INTEGER]);
    }

    /**
     * Supprime en masse les préventionnistes des cellules d'une ou plusieurs communes.
     *
     * @param array<string> $communesNumInsee
     */
    public function deleteCellulesPreventionnistesByCommunes(array $communesNumInsee): int
    {
        if (empty($communesNumInsee)) {
            return 0;
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = 'DELETE eip FROM etablissementinformationspreventionniste eip
                INNER JOIN etablissementinformations ei ON eip.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS
                INNER JOIN etablissement e ON ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                INNER JOIN etablissementlie el ON e.ID_ETABLISSEMENT = el.ID_FILS_ETABLISSEMENT
                INNER JOIN etablissement parent ON el.ID_ETABLISSEMENT = parent.ID_ETABLISSEMENT
                INNER JOIN etablissementadresse ea ON parent.ID_ETABLISSEMENT = ea.ID_ETABLISSEMENT
                INNER JOIN (
                    SELECT ID_ETABLISSEMENT, MAX(DATE_ETABLISSEMENTINFORMATIONS) as MAX_DATE
                    FROM etablissementinformations
                    GROUP BY ID_ETABLISSEMENT
                ) ei_max ON ei.ID_ETABLISSEMENT = ei_max.ID_ETABLISSEMENT
                    AND ei.DATE_ETABLISSEMENTINFORMATIONS = ei_max.MAX_DATE
                WHERE ea.NUMINSEE_COMMUNE IN (:communes)';

        return $conn->executeStatement($sql, ['communes' => $communesNumInsee], ['communes' => ArrayParameterType::INTEGER]);
    }

    /**
     * Supprime en masse les préventionnistes des sites d'une ou plusieurs communes.
     *
     * @param array<string> $communesNumInsee
     */
    public function deleteSitesPreventionnistesByCommunes(array $communesNumInsee): int
    {
        if (empty($communesNumInsee)) {
            return 0;
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = 'DELETE eip FROM etablissementinformationspreventionniste eip
                INNER JOIN etablissementinformations ei ON eip.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS
                INNER JOIN etablissement e ON ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                INNER JOIN etablissementlie el ON e.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT
                INNER JOIN etablissement fils ON el.ID_FILS_ETABLISSEMENT = fils.ID_ETABLISSEMENT
                INNER JOIN etablissementadresse ea ON fils.ID_ETABLISSEMENT = ea.ID_ETABLISSEMENT
                INNER JOIN (
                    SELECT ID_ETABLISSEMENT, MAX(DATE_ETABLISSEMENTINFORMATIONS) as MAX_DATE
                    FROM etablissementinformations
                    GROUP BY ID_ETABLISSEMENT
                ) ei_max ON ei.ID_ETABLISSEMENT = ei_max.ID_ETABLISSEMENT
                    AND ei.DATE_ETABLISSEMENTINFORMATIONS = ei_max.MAX_DATE
                WHERE ea.NUMINSEE_COMMUNE IN (:communes)';

        return $conn->executeStatement($sql, ['communes' => $communesNumInsee], ['communes' => ArrayParameterType::INTEGER]);
    }

    /**
     * Insert en masse les préventionnistes pour les établissements d'un groupement.
     * Utilise une requête INSERT...SELECT pour des performances optimales.
     */
    public function insertEtablissementsPreventionnistes(string $groupement): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO etablissementinformationspreventionniste (ID_ETABLISSEMENTINFORMATIONS, ID_UTILISATEUR)
                SELECT DISTINCT ei.ID_ETABLISSEMENTINFORMATIONS, p.ID_UTILISATEUR
                FROM etablissementinformations ei
                INNER JOIN etablissement e ON ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                INNER JOIN etablissementadresse ea ON e.ID_ETABLISSEMENT = ea.ID_ETABLISSEMENT
                INNER JOIN adressecommune c ON ea.NUMINSEE_COMMUNE = c.NUMINSEE_COMMUNE
                INNER JOIN groupementcommune gc ON c.NUMINSEE_COMMUNE = gc.NUMINSEE_COMMUNE
                INNER JOIN groupement g ON gc.ID_GROUPEMENT = g.ID_GROUPEMENT
                INNER JOIN groupementpreventionniste gp ON g.ID_GROUPEMENT = gp.ID_GROUPEMENT
                INNER JOIN utilisateur p ON gp.ID_UTILISATEUR = p.ID_UTILISATEUR
                INNER JOIN (
                    SELECT ID_ETABLISSEMENT, MAX(DATE_ETABLISSEMENTINFORMATIONS) as MAX_DATE
                    FROM etablissementinformations
                    GROUP BY ID_ETABLISSEMENT
                ) ei_max ON ei.ID_ETABLISSEMENT = ei_max.ID_ETABLISSEMENT
                    AND ei.DATE_ETABLISSEMENTINFORMATIONS = ei_max.MAX_DATE
                WHERE ei.ID_GENRE = :genre
                AND g.LIBELLE_GROUPEMENT = :groupement';

        return $conn->executeStatement($sql, ['genre' => GenreEtablissement::ERP, 'groupement' => $groupement]);
    }

    /**
     * Insert en masse les préventionnistes pour les cellules d'un groupement.
     * Utilise une requête INSERT...SELECT pour des performances optimales.
     */
    public function insertCellulesPreventionnistes(string $groupement): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO etablissementinformationspreventionniste (ID_ETABLISSEMENTINFORMATIONS, ID_UTILISATEUR)
                SELECT DISTINCT ei.ID_ETABLISSEMENTINFORMATIONS, p.ID_UTILISATEUR
                FROM etablissementinformations ei
                INNER JOIN etablissement e ON ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                INNER JOIN etablissementlie el ON e.ID_ETABLISSEMENT = el.ID_FILS_ETABLISSEMENT
                INNER JOIN etablissement parent ON el.ID_ETABLISSEMENT = parent.ID_ETABLISSEMENT
                INNER JOIN etablissementadresse ea ON parent.ID_ETABLISSEMENT = ea.ID_ETABLISSEMENT
                INNER JOIN adressecommune c ON ea.NUMINSEE_COMMUNE = c.NUMINSEE_COMMUNE
                INNER JOIN groupementcommune gc ON c.NUMINSEE_COMMUNE = gc.NUMINSEE_COMMUNE
                INNER JOIN groupement g ON gc.ID_GROUPEMENT = g.ID_GROUPEMENT
                INNER JOIN groupementpreventionniste gp ON g.ID_GROUPEMENT = gp.ID_GROUPEMENT
                INNER JOIN utilisateur p ON gp.ID_UTILISATEUR = p.ID_UTILISATEUR
                INNER JOIN (
                    SELECT ID_ETABLISSEMENT, MAX(DATE_ETABLISSEMENTINFORMATIONS) as MAX_DATE
                    FROM etablissementinformations
                    GROUP BY ID_ETABLISSEMENT
                ) ei_max ON ei.ID_ETABLISSEMENT = ei_max.ID_ETABLISSEMENT
                    AND ei.DATE_ETABLISSEMENTINFORMATIONS = ei_max.MAX_DATE
                WHERE ei.ID_GENRE = :genre
                AND g.LIBELLE_GROUPEMENT = :groupement';

        return $conn->executeStatement($sql, ['genre' => GenreEtablissement::CELLULE, 'groupement' => $groupement]);
    }

    /**
     * Insert en masse les préventionnistes pour les sites d'un groupement.
     * Utilise une requête INSERT...SELECT pour des performances optimales.
     */
    public function insertSitesPreventionnistes(string $groupement): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO etablissementinformationspreventionniste (ID_ETABLISSEMENTINFORMATIONS, ID_UTILISATEUR)
                SELECT DISTINCT ei.ID_ETABLISSEMENTINFORMATIONS, p.ID_UTILISATEUR
                FROM etablissementinformations ei
                INNER JOIN etablissement e ON ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                INNER JOIN etablissementlie el ON e.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT
                INNER JOIN etablissement fils ON el.ID_FILS_ETABLISSEMENT = fils.ID_ETABLISSEMENT
                INNER JOIN etablissementadresse ea ON fils.ID_ETABLISSEMENT = ea.ID_ETABLISSEMENT
                INNER JOIN adressecommune c ON ea.NUMINSEE_COMMUNE = c.NUMINSEE_COMMUNE
                INNER JOIN groupementcommune gc ON c.NUMINSEE_COMMUNE = gc.NUMINSEE_COMMUNE
                INNER JOIN groupement g ON gc.ID_GROUPEMENT = g.ID_GROUPEMENT
                INNER JOIN groupementpreventionniste gp ON g.ID_GROUPEMENT = gp.ID_GROUPEMENT
                INNER JOIN utilisateur p ON gp.ID_UTILISATEUR = p.ID_UTILISATEUR
                INNER JOIN (
                    SELECT ID_ETABLISSEMENT, MAX(DATE_ETABLISSEMENTINFORMATIONS) as MAX_DATE
                    FROM etablissementinformations
                    GROUP BY ID_ETABLISSEMENT
                ) ei_max ON ei.ID_ETABLISSEMENT = ei_max.ID_ETABLISSEMENT
                    AND ei.DATE_ETABLISSEMENTINFORMATIONS = ei_max.MAX_DATE
                WHERE ei.ID_GENRE = :genre
                AND g.LIBELLE_GROUPEMENT = :groupement';

        return $conn->executeStatement($sql, ['genre' => GenreEtablissement::SITE, 'groupement' => $groupement]);
    }

    /**
     * Retourne uniquement les colonnes nécessaires à l'application des règles de compétence,
     * pour les établissements ERP (genre=2) et IGH (genre=5) ayant une commune renseignée.
     * Seule la fiche d'informations la plus récente par établissement est retournée.
     *
     * @return array<int, array{ID_ETABLISSEMENTINFORMATIONS: string, ID_GENRE: string, NUMINSEE_COMMUNE: string, LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS: bool|null, ID_TYPE: string|null, ID_CATEGORIE: string|null, ID_CLASSE: string|null}>
     */
    public function findForCompetenceApplication(): array
    {
        return $this->createQueryBuilder('ei')
            ->select([
                'ei.id as ID_ETABLISSEMENTINFORMATIONS',
                'IDENTITY(ei.genre) as ID_GENRE',
                'IDENTITY(ea.commune) as NUMINSEE_COMMUNE',
                'ei.localSommeil as LOCALSOMMEIL_ETABLISSEMENTINFORMATIONS',
                'IDENTITY(ei.type) as ID_TYPE',
                'IDENTITY(ei.categorie) as ID_CATEGORIE',
                'IDENTITY(ei.classe) as ID_CLASSE',
            ])
            ->innerJoin('ei.etablissement', 'e')
            ->innerJoin('e.adresses', 'ea')
            ->where('IDENTITY(ei.genre) IN (:genres)')
            ->andWhere('ei.date = (SELECT MAX(ei2.date) FROM App\Entity\EtablissementInformations ei2 WHERE ei2.etablissement = ei.etablissement)')
            ->setParameter('genres', [GenreEtablissement::ERP, GenreEtablissement::IGH])
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Met à jour en masse la commission par défaut des établissements dont les IDs sont fournis.
     *
     * @param int[] $ids
     */
    public function updateCommissionForEtablissements(int $commissionId, array $ids): int
    {
        if ([] === $ids) {
            return 0;
        }

        $conn = $this->getEntityManager()->getConnection();

        return $conn->executeStatement(
            'UPDATE etablissementinformations SET ID_COMMISSION = :commissionId WHERE ID_ETABLISSEMENTINFORMATIONS IN (:ids)',
            ['commissionId' => $commissionId, 'ids' => $ids],
            ['ids' => ArrayParameterType::INTEGER]
        );
    }
}

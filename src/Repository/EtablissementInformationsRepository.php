<?php

namespace App\Repository;

use App\Entity\EtablissementInformations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
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
                WHERE ea.NUMINSEE_COMMUNE IN (:communes)
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                )';

        return $conn->executeStatement($sql, ['communes' => $communesNumInsee], ['communes' => Connection::PARAM_STR_ARRAY]);
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
                WHERE ea.NUMINSEE_COMMUNE IN (:communes)
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                )';

        return $conn->executeStatement($sql, ['communes' => $communesNumInsee], ['communes' => Connection::PARAM_STR_ARRAY]);
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
                WHERE ea.NUMINSEE_COMMUNE IN (:communes)
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                )';

        return $conn->executeStatement($sql, ['communes' => $communesNumInsee], ['communes' => Connection::PARAM_STR_ARRAY]);
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
                WHERE ei.ID_GENRE = 2
                AND g.LIBELLE_GROUPEMENT = :groupement
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                )';

        return $conn->executeStatement($sql, ['groupement' => $groupement]);
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
                WHERE ei.ID_GENRE = 3
                AND g.LIBELLE_GROUPEMENT = :groupement
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                )';

        return $conn->executeStatement($sql, ['groupement' => $groupement]);
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
                WHERE ei.ID_GENRE = 1
                AND g.LIBELLE_GROUPEMENT = :groupement
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                )';

        return $conn->executeStatement($sql, ['groupement' => $groupement]);
    }
}

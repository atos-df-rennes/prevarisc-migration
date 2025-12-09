<?php

namespace App\Repository;

use App\Entity\EtablissementInformations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
     * @return array<int, array{ID_ETABLISSEMENTINFORMATIONS: int, ID_UTILISATEUR: int}>
     */
    public function getEtablissementsPreventioniste(string $groupement): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT ei.ID_ETABLISSEMENTINFORMATIONS, u.ID_UTILISATEUR
                FROM etablissementinformations ei
                INNER JOIN etablissement e ON e.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                INNER JOIN etablissementadresse ea ON ea.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                INNER JOIN groupementcommune gc ON gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
                INNER JOIN groupement g ON g.ID_GROUPEMENT = gc.ID_GROUPEMENT
                INNER JOIN groupementpreventionniste gp ON gp.ID_GROUPEMENT = g.ID_GROUPEMENT
                INNER JOIN utilisateur u ON u.ID_UTILISATEUR = gp.ID_UTILISATEUR
                WHERE ei.ID_GENRE = 2
                AND g.LIBELLE_GROUPEMENT = :groupement
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                )
                GROUP BY ei.ID_ETABLISSEMENTINFORMATIONS, u.ID_UTILISATEUR';

        return $conn->fetchAllAssociative($sql, ['groupement' => $groupement]);
    }

    /**
     * @return array<int, array{ID_ETABLISSEMENTINFORMATIONS: int, ID_UTILISATEUR: int}>
     */
    public function getCellulesPreventioniste(string $groupement): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT ei.ID_ETABLISSEMENTINFORMATIONS, u.ID_UTILISATEUR
                FROM etablissementinformations ei
                INNER JOIN etablissement e ON e.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                INNER JOIN etablissementlie el ON el.ID_FILS_ETABLISSEMENT = e.ID_ETABLISSEMENT
                INNER JOIN etablissement pere ON pere.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT
                INNER JOIN etablissementadresse ea ON ea.ID_ETABLISSEMENT = pere.ID_ETABLISSEMENT
                INNER JOIN groupementcommune gc ON gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
                INNER JOIN groupement g ON g.ID_GROUPEMENT = gc.ID_GROUPEMENT
                INNER JOIN groupementpreventionniste gp ON gp.ID_GROUPEMENT = g.ID_GROUPEMENT
                INNER JOIN utilisateur u ON u.ID_UTILISATEUR = gp.ID_UTILISATEUR
                WHERE ei.ID_GENRE = 3
                AND g.LIBELLE_GROUPEMENT = :groupement
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                )
                GROUP BY ei.ID_ETABLISSEMENTINFORMATIONS, u.ID_UTILISATEUR';

        return $conn->fetchAllAssociative($sql, ['groupement' => $groupement]);
    }

    /**
     * @return array<int, array{ID_ETABLISSEMENTINFORMATIONS: int, ID_UTILISATEUR: int}>
     */
    public function getSitesPreventioniste(string $groupement): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT ei.ID_ETABLISSEMENTINFORMATIONS, u.ID_UTILISATEUR
                FROM etablissementinformations ei
                INNER JOIN etablissement e ON e.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                INNER JOIN etablissementlie el ON el.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                INNER JOIN etablissement fils ON fils.ID_ETABLISSEMENT = el.ID_FILS_ETABLISSEMENT
                INNER JOIN etablissementadresse ea ON ea.ID_ETABLISSEMENT = fils.ID_ETABLISSEMENT
                INNER JOIN groupementcommune gc ON gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
                INNER JOIN groupement g ON g.ID_GROUPEMENT = gc.ID_GROUPEMENT
                INNER JOIN groupementpreventionniste gp ON gp.ID_GROUPEMENT = g.ID_GROUPEMENT
                INNER JOIN utilisateur u ON u.ID_UTILISATEUR = gp.ID_UTILISATEUR
                WHERE ei.ID_GENRE = 1
                AND g.LIBELLE_GROUPEMENT = :groupement
                AND ei.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei2.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei2
                    WHERE ei2.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                )
                GROUP BY ei.ID_ETABLISSEMENTINFORMATIONS, u.ID_UTILISATEUR';

        return $conn->fetchAllAssociative($sql, ['groupement' => $groupement]);
    }

    public function deleteEtablissementsPreventioniste(string $groupement): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'DELETE ep.* FROM etablissementinformationspreventionniste ep
        INNER JOIN etablissementinformations ei ON ep.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS AND ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT)
        INNER JOIN `etablissementadresse` AS `ea` ON ea.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
        INNER JOIN `groupementcommune` AS `gc` ON gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
        INNER JOIN `groupement` AS `g` ON g.ID_GROUPEMENT = gc.ID_GROUPEMENT
        WHERE g.LIBELLE_GROUPEMENT = :groupement';

        $conn->executeStatement($sql, ['groupement' => $groupement]);
    }

    public function deleteCellulesPreventioniste(string $groupement): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'DELETE ep.* FROM etablissementinformationspreventionniste ep
        INNER JOIN etablissementinformations ei ON ep.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS AND ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT)
        INNER JOIN `etablissementlie` AS `el` ON el.ID_FILS_ETABLISSEMENT = ei.ID_ETABLISSEMENT
        INNER JOIN `etablissement` AS `pere` ON pere.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT
        INNER JOIN `etablissementadresse` AS `ea` ON ea.ID_ETABLISSEMENT = pere.ID_ETABLISSEMENT
        INNER JOIN `groupementcommune` AS `gc` ON gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
        INNER JOIN `groupement` AS `g` ON g.ID_GROUPEMENT = gc.ID_GROUPEMENT
        WHERE g.LIBELLE_GROUPEMENT = :groupement';

        $conn->executeStatement($sql, ['groupement' => $groupement]);
    }

    public function deleteSitesPreventioniste(string $groupement): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'DELETE ep.* FROM etablissementinformationspreventionniste ep
        INNER JOIN etablissementinformations ei ON ep.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS AND ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT)
        INNER JOIN `etablissementlie` AS `el` ON el.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
        INNER JOIN `etablissement` AS `fils` ON fils.ID_ETABLISSEMENT = el.ID_FILS_ETABLISSEMENT
        INNER JOIN `etablissementadresse` AS `ea` ON ea.ID_ETABLISSEMENT = fils.ID_ETABLISSEMENT
        INNER JOIN `groupementcommune` AS `gc` ON gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
        INNER JOIN `groupement` AS `g` ON g.ID_GROUPEMENT = gc.ID_GROUPEMENT
        WHERE g.LIBELLE_GROUPEMENT = :groupement';

        $conn->executeStatement($sql, ['groupement' => $groupement]);
    }

    /**
     * @param array<int, array{ID_ETABLISSEMENTINFORMATIONS: int, ID_UTILISATEUR: int}> $valeur
     */
    public function addPreventioniste(array $valeur): void
    {
        if (empty($valeur)) {
            return;
        }

        $conn = $this->getEntityManager()->getConnection();
        foreach ($valeur as $val) {
            $conn->insert('etablissementinformationspreventionniste', $val);
        }
    }
}

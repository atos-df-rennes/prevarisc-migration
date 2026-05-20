<?php

namespace App\Repository;

use App\Entity\DateCommission;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DateCommission>
 *
 * @method DateCommission|null find($id, $lockMode = null, $lockVersion = null)
 * @method DateCommission|null findOneBy(array $criteria, array $orderBy = null)
 * @method DateCommission[]    findAll()
 * @method DateCommission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DateCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DateCommission::class);
    }

    /**
     * Retourne la date de commission (au sens calendrier : table datecommission et non propriété : dateCommission) d'un dossier.
     * Ce comportement n'est présent que pour les dates de visite.
     * Les dates de visite sont des dates de commission de type 2 (Visite de sécurité) ou 3 (Groupe de visites).
     */
    public function findForDossier(Dossier $dossier, \DateTimeInterface $dateVisite): ?DateCommission
    {
        $queryBuilder = $this->createQueryBuilder('dc');
        $query = $queryBuilder
            ->addSelect('da')
            ->join('dc.dossierAffectations', 'da')
            ->where('dc.date = :dateVisite')
            ->andWhere('dc.typeEvenement IN (:typeEvenement)')
            ->andWhere('da.dossier = :dossier')
            ->setParameter('dateVisite', $dateVisite)
            ->setParameter('typeEvenement', [2, 3])
            ->setParameter('dossier', $dossier)
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }

    /**
     * Retourne les dates de commission liées à la date de commission passée en paramètre.
     *
     * @return DateCommission[]
     */
    public function findDatesLiees(DateCommission $dateCommission): array
    {
        $queryBuilder = $this->createQueryBuilder('dc');

        $idLiaison = $dateCommission->getDateCommissionLiees();
        if (null === $idLiaison) {
            $queryBuilder = $queryBuilder
                ->where('dc.dateCommissionLiees = :dateCommissionLiees')
                ->setParameter('dateCommissionLiees', $dateCommission->getId());
        } else {
            $queryBuilder = $queryBuilder
                ->where('dc.id = :idDateCommissionLiaison')
                ->orWhere('dc.dateCommissionLiees = :idDateCommissionLiaison AND dc.id != :idDateCommission')
                ->setParameter('idDateCommissionLiaison', $idLiaison)
                ->setParameter('idDateCommission', $dateCommission->getId());
        }

        $query = $queryBuilder
            ->orderBy('dc.date', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Retourne les prochaines séances de commission pour le tableau de bord.
     * Port direct de Model_DbTable_DateCommission::getNextCommission().
     *
     * @param int[] $commissionIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function findNextCommissionsForDashboard(array $commissionIds, int $fromTimestamp, int $toTimestamp): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $fromDate = date('Y-m-d', $fromTimestamp);
        $toDate = date('Y-m-d', $toTimestamp);

        $select = 'SELECT * FROM datecommission d
            LEFT JOIN commission c ON d.COMMISSION_CONCERNE = c.ID_COMMISSION
            WHERE DATE_COMMISSION BETWEEN :fromDate AND :toDate';

        $params = ['fromDate' => $fromDate, 'toDate' => $toDate];
        $types = [];

        if ([] !== $commissionIds) {
            $select .= ' AND d.COMMISSION_CONCERNE IN (:commissionIds)';
            $params['commissionIds'] = array_map('intval', $commissionIds);
            $types['commissionIds'] = Connection::PARAM_INT_ARRAY;
        }

        $select .= ' ORDER BY DATE_COMMISSION, HEUREDEB_COMMISSION';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($select, $params, $types);

        return $rows;
    }

    /**
     * Compte les prochaines séances de commission pour le tableau de bord.
     * Requête COUNT lean extraite de findNextCommissionsForDashboard.
     *
     * @param int[] $commissionIds
     */
    public function countNextCommissionsForDashboard(array $commissionIds, int $fromTimestamp, int $toTimestamp): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $fromDate = date('Y-m-d', $fromTimestamp);
        $toDate = date('Y-m-d', $toTimestamp);

        $sql = 'SELECT COUNT(*) as count FROM datecommission d
            LEFT JOIN commission c ON d.COMMISSION_CONCERNE = c.ID_COMMISSION
            WHERE DATE_COMMISSION BETWEEN :fromDate AND :toDate';

        $params = ['fromDate' => $fromDate, 'toDate' => $toDate];
        $types = [];

        if ([] !== $commissionIds) {
            $sql .= ' AND d.COMMISSION_CONCERNE IN (:commissionIds)';
            $params['commissionIds'] = array_map('intval', $commissionIds);
            $types['commissionIds'] = Connection::PARAM_INT_ARRAY;
        }

        $count = $conn->fetchOne($sql, $params, $types);

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * Retourne les dossiers affectés (avec horaire précisé) à une date de commission (ODJ).
     * Port direct de Model_DbTable_DossierAffectation::getDossierAffect().
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossierAffectForCommission(int $idDateCommission): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT doss.*, dossAffect.ID_DOSSIER_AFFECT, dossAffect.HEURE_DEB_AFFECT, dossAffect.HEURE_FIN_AFFECT, dossAffect.NUM_DOSSIER, dossAffect.ID_DATECOMMISSION_AFFECT, dossNatListe.LIBELLE_DOSSIERNATURE, dossType.LIBELLE_DOSSIERTYPE, etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS
            FROM dossier doss
            JOIN dossieraffectation dossAffect ON doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT
            JOIN datecommission dateComm ON dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION
            JOIN dossiernature dossNat ON dossNat.ID_DOSSIER = doss.ID_DOSSIER
            JOIN dossiernatureliste dossNatListe ON dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE
            JOIN dossiertype dossType ON doss.TYPE_DOSSIER = dossType.ID_DOSSIERTYPE
            LEFT JOIN etablissementdossier e ON doss.ID_DOSSIER = e.ID_DOSSIER
            LEFT JOIN etablissementinformations ON e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT
                AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei
                    WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                )
            WHERE dateComm.ID_DATECOMMISSION = :idDateCommission
            AND dossAffect.HEURE_DEB_AFFECT IS NOT NULL
            AND dossAffect.HEURE_FIN_AFFECT IS NOT NULL
            GROUP BY doss.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['idDateCommission' => $idDateCommission]);

        return $rows;
    }

    /**
     * Retourne les dossiers non-affectés (sans horaire précisé) à une date de commission (ODJ).
     * Port direct de Model_DbTable_DossierAffectation::getDossierNonAffect().
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossierNonAffectForCommission(int $idDateCommission): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT doss.*, dossAffect.ID_DOSSIER_AFFECT, dossAffect.HEURE_DEB_AFFECT, dossAffect.HEURE_FIN_AFFECT, dossAffect.NUM_DOSSIER, dossAffect.ID_DATECOMMISSION_AFFECT, dossNatListe.LIBELLE_DOSSIERNATURE, dossType.LIBELLE_DOSSIERTYPE, etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS
            FROM dossier doss
            JOIN dossieraffectation dossAffect ON doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT
            JOIN datecommission dateComm ON dossAffect.ID_DATECOMMISSION_AFFECT = dateComm.ID_DATECOMMISSION
            JOIN dossiernature dossNat ON dossNat.ID_DOSSIER = doss.ID_DOSSIER
            JOIN dossiernatureliste dossNatListe ON dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE
            JOIN dossiertype dossType ON doss.TYPE_DOSSIER = dossType.ID_DOSSIERTYPE
            LEFT JOIN etablissementdossier e ON doss.ID_DOSSIER = e.ID_DOSSIER
            LEFT JOIN etablissementinformations ON e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT
                AND etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = (
                    SELECT MAX(ei.DATE_ETABLISSEMENTINFORMATIONS)
                    FROM etablissementinformations ei
                    WHERE ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                )
            WHERE dateComm.ID_DATECOMMISSION = :idDateCommission
            AND dossAffect.HEURE_DEB_AFFECT IS NULL
            AND dossAffect.HEURE_FIN_AFFECT IS NULL
            GROUP BY doss.ID_DOSSIER
            ORDER BY dossAffect.NUM_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['idDateCommission' => $idDateCommission]);

        return $rows;
    }

    /**
     * Retourne la liste des dates de commission liées à une date maître (y compris elle-même).
     * Port direct de Model_DbTable_DateCommission::getCommissionsQtypListing().
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCommissionsQtypListing(int $idDateCommission): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT * FROM datecommission
            WHERE (ID_DATECOMMISSION = :id OR DATECOMMISSION_LIEES = :id)
            ORDER BY DATE_COMMISSION';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['id' => $idDateCommission]);

        return $rows;
    }

    /**
     * Met à jour le libellé d'une date de commission et de toutes ses dates liées.
     * Port direct de Model_DbTable_DateCommission::dateCommUpdateLibelle().
     */
    public function updateLibelle(int $idDateCommission, string $libelle): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'UPDATE datecommission
            SET LIBELLE_DATECOMMISSION = :libelle
            WHERE (ID_DATECOMMISSION = :id OR DATECOMMISSION_LIEES = :id)';

        $conn->executeStatement($sql, ['libelle' => $libelle, 'id' => $idDateCommission]);
    }

    /**
     * Met à jour le type d'une date de commission et de toutes ses dates liées.
     * Port direct de Model_DbTable_DateCommission::dateCommUpdateType().
     */
    public function updateType(int $idDateCommission, int $idNewType): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'UPDATE datecommission
            SET ID_COMMISSIONTYPEEVENEMENT = :idNewType
            WHERE (ID_DATECOMMISSION = :id OR DATECOMMISSION_LIEES = :id)';

        $conn->executeStatement($sql, ['idNewType' => $idNewType, 'id' => $idDateCommission]);
    }

    /**
     * Change la date maître d'un groupe de dates liées.
     * Port direct de Model_DbTable_DateCommission::changeMasterDateComm().
     */
    public function changeMasterDateComm(int $oldMasterId, int $newMasterId): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'UPDATE datecommission
            SET DATECOMMISSION_LIEES = :newMasterId
            WHERE (ID_DATECOMMISSION = :oldMasterId OR DATECOMMISSION_LIEES = :oldMasterId)';

        $conn->executeStatement($sql, ['newMasterId' => $newMasterId, 'oldMasterId' => $oldMasterId]);
    }

    /**
     * Met à jour les dates de commission dans les dossiers liés à une date de commission.
     * Port direct de Model_DbTable_DateCommission::updateDependingDossierDates().
     */
    public function updateDependingDossierDates(int $idDateCommission, string $dateCommission, int $idTypeEvenement): void
    {
        $conn = $this->getEntityManager()->getConnection();

        /** @var array<int, array<string, mixed>> $dossiers */
        $dossiers = $conn->fetchAllAssociative(
            'SELECT ID_DOSSIER_AFFECT FROM dossieraffectation WHERE ID_DATECOMMISSION_AFFECT = :id',
            ['id' => $idDateCommission]
        );

        if ([] === $dossiers) {
            return;
        }

        $dossierIds = array_map(function (array $row): int {
            return (int) $row['ID_DOSSIER_AFFECT'];
        }, $dossiers);

        if (1 === $idTypeEvenement) {
            $conn->executeStatement(
                'UPDATE dossier SET DATECOMM_DOSSIER = :date WHERE ID_DOSSIER IN (:ids)',
                ['date' => $dateCommission, 'ids' => $dossierIds],
                ['ids' => Connection::PARAM_INT_ARRAY]
            );
        } elseif (in_array($idTypeEvenement, [2, 3], true)) {
            $conn->executeStatement(
                'UPDATE dossier SET DATEVISITE_DOSSIER = :date WHERE ID_DOSSIER IN (:ids)',
                ['date' => $dateCommission, 'ids' => $dossierIds],
                ['ids' => Connection::PARAM_INT_ARRAY]
            );
        }
    }

    /**
     * Retourne les DateCommissions d'un mois/année pour une commission donnée.
     * Port direct de Model_DbTable_DateCommission::getMonthCommission().
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByMonthAndCommission(int $mois, int $annee, int $idCommission): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $start = (new \DateTime(sprintf('%04d-%02d-01', $annee, $mois)))->format('Y-m-d');
        $end = (new \DateTime(sprintf('%04d-%02d-01', $annee, $mois)))->modify('+1 month')->format('Y-m-d');

        $sql = 'SELECT * FROM datecommission
            WHERE DATE_COMMISSION >= :start
            AND DATE_COMMISSION < :end
            AND COMMISSION_CONCERNE = :idCommission';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, [
            'start' => $start,
            'end' => $end,
            'idCommission' => $idCommission,
        ]);

        return $rows;
    }

    /**
     * Retourne les DateCommissions pour la synchronisation webcal.
     * Port simplifié de Model_DbTable_DateCommission::getEventInCommission().
     *
     * - Si userId non null : joint avec dossieraffectation + dossierpreventionniste
     *   pour ne retourner que les DateCommissions des dossiers du préventionniste.
     * - Si commissionId non null : filtre par commission.
     * - Si les deux null (admin) : retourne tout.
     * - fromDate : filtre dc.DATE_COMMISSION >= fromDate (date glissante, 1 an en arrière).
     * - Joint avec dossieraffectation pour récupérer OBJET_DOSSIER pour la DESCRIPTION.
     *
     * Retourne des lignes brutes : plusieurs lignes par DateCommission si plusieurs dossiers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForWebcalSync(?int $userId, ?int $commissionId, string $fromDate): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $params = ['fromDate' => $fromDate];

        if (null !== $userId) {
            // Filtre par préventionniste : joint dossieraffectation → dossier → dossierpreventionniste
            // INNER JOINs explicites car le WHERE sur dp.ID_PREVENTIONNISTE exige que d et dp existent.
            $sql = 'SELECT dc.ID_DATECOMMISSION, dc.DATE_COMMISSION, dc.HEUREDEB_COMMISSION,
                           dc.HEUREFIN_COMMISSION, dc.LIBELLE_DATECOMMISSION,
                           c.LIBELLE_COMMISSION,
                           d.OBJET_DOSSIER, d.ID_DOSSIER
                    FROM datecommission dc
                    JOIN commission c ON dc.COMMISSION_CONCERNE = c.ID_COMMISSION
                    JOIN dossieraffectation da ON da.ID_DATECOMMISSION_AFFECT = dc.ID_DATECOMMISSION
                    JOIN dossier d ON da.ID_DOSSIER_AFFECT = d.ID_DOSSIER
                    JOIN dossierpreventionniste dp ON dp.ID_DOSSIER = d.ID_DOSSIER
                    WHERE dp.ID_PREVENTIONNISTE = :userId
                    AND dc.DATE_COMMISSION >= :fromDate';
            $params['userId'] = $userId;
        } else {
            // Admin : toutes les DateCommissions avec leurs dossiers (LEFT JOIN)
            $sql = 'SELECT dc.ID_DATECOMMISSION, dc.DATE_COMMISSION, dc.HEUREDEB_COMMISSION,
                           dc.HEUREFIN_COMMISSION, dc.LIBELLE_DATECOMMISSION,
                           c.LIBELLE_COMMISSION,
                           d.OBJET_DOSSIER, d.ID_DOSSIER
                    FROM datecommission dc
                    JOIN commission c ON dc.COMMISSION_CONCERNE = c.ID_COMMISSION
                    LEFT JOIN dossieraffectation da ON da.ID_DATECOMMISSION_AFFECT = dc.ID_DATECOMMISSION
                    LEFT JOIN dossier d ON da.ID_DOSSIER_AFFECT = d.ID_DOSSIER
                    WHERE dc.DATE_COMMISSION >= :fromDate';
        }

        if (null !== $commissionId) {
            $sql .= ' AND dc.COMMISSION_CONCERNE = :commissionId';
            $params['commissionId'] = $commissionId;
        }

        $sql .= ' ORDER BY dc.DATE_COMMISSION ASC, dc.ID_DATECOMMISSION ASC';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, $params);

        return $rows;
    }

    /**
     * Récupère les événements de commission entre deux dates.
     *
     * @return DateCommission[]
     */
    public function findEventsBetweenDates(
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $idCommission = null,
        ?int $idTypeEvenement = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('dc')
            ->addSelect('te')
            ->leftJoin('dc.typeEvenement', 'te')
            ->where('dc.date BETWEEN :dateDebut AND :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
        ;

        if (null !== $idCommission) {
            $queryBuilder
                ->andWhere('dc.commission = :idCommission')
                ->setParameter('idCommission', $idCommission)
            ;
        }

        if (null !== $idTypeEvenement) {
            $queryBuilder
                ->andWhere('dc.typeEvenement = :idTypeEvenement')
                ->setParameter('idTypeEvenement', $idTypeEvenement)
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Retourne tous les dossiers affectés à une dateCommission pour la génération de documents.
     *
     * Port direct des queries getDossiersInfosByHour / getDossiersInfosByOrder du legacy.
     * Contrairement à findDossierAffectForCommission, ne filtre pas sur la présence d'heure :
     * retourne tous les dossiers affectés, triés selon le mode de gestion des heures.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDossiersForGeneration(int $dateCommId, int $gestionHeures): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $orderBy = 1 === $gestionHeures ? 'dossAffect.HEURE_DEB_AFFECT' : 'dossAffect.ORDRE_AFFECTATION';

        $sql = 'SELECT doss.*, dossAffect.ID_DOSSIER_AFFECT, dossAffect.HEURE_DEB_AFFECT,
                       dossAffect.HEURE_FIN_AFFECT, dossAffect.ORDRE_AFFECTATION,
                       dossNatListe.LIBELLE_DOSSIERNATURE
                FROM dossieraffectation dossAffect
                JOIN dossier doss ON doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT
                LEFT JOIN dossiernature dossNat ON dossNat.ID_DOSSIER = doss.ID_DOSSIER
                LEFT JOIN dossiernatureliste dossNatListe ON dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE
                WHERE dossAffect.ID_DATECOMMISSION_AFFECT = :dateCommId
                ORDER BY '.$orderBy;

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['dateCommId' => $dateCommId]);

        return $rows;
    }
}

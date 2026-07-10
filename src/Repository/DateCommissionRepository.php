<?php

namespace App\Repository;

use App\Dto\Repository\DossierAffectationDto;
use App\Dto\Repository\NextCommissionDto;
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
     * Retourne la date de commission passée en paramètre uniquement si la pièce jointe
     * lui est bien rattachée (relation ManyToMany DateCommission <-> PieceJointe).
     */
    public function findOneByPieceJointe(int $dateCommissionId, int $pieceJointeId): ?DateCommission
    {
        $queryBuilder = $this->createQueryBuilder('dc');

        return $queryBuilder
            ->join('dc.piecesJointes', 'pj')
            ->where('dc.id = :dateCommissionId')
            ->andWhere('pj.id = :pieceJointeId')
            ->setParameter('dateCommissionId', $dateCommissionId)
            ->setParameter('pieceJointeId', $pieceJointeId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
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
     * @return NextCommissionDto[]
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

        return array_map(static fn (array $row): NextCommissionDto => NextCommissionDto::fromArray($row), $rows);
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
     * @return DossierAffectationDto[]
     */
    public function findDossierAffectForCommission(int $idDateCommission): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT doss.*, dossAffect.ID_DOSSIER_AFFECT, dossAffect.HEURE_DEB_AFFECT, dossAffect.HEURE_FIN_AFFECT, dossAffect.NUM_DOSSIER, dossAffect.ID_DATECOMMISSION_AFFECT, dossNatListe.LIBELLE_DOSSIERNATURE, dossType.LIBELLE_DOSSIERTYPE, etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS, GROUP_CONCAT(dossierdocurba.NUM_DOCURBA SEPARATOR \', \') AS NUM_DOCURBA
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
            LEFT JOIN dossierdocurba ON doss.ID_DOSSIER = dossierdocurba.ID_DOSSIER
            WHERE dateComm.ID_DATECOMMISSION = :idDateCommission
            AND dossAffect.HEURE_DEB_AFFECT IS NOT NULL
            AND dossAffect.HEURE_FIN_AFFECT IS NOT NULL
            GROUP BY doss.ID_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['idDateCommission' => $idDateCommission]);

        return array_map(static fn (array $row): DossierAffectationDto => DossierAffectationDto::fromArray($row), $rows);
    }

    /**
     * Retourne les dossiers non-affectés (sans horaire précisé) à une date de commission (ODJ).
     * Port direct de Model_DbTable_DossierAffectation::getDossierNonAffect().
     *
     * @return DossierAffectationDto[]
     */
    public function findDossierNonAffectForCommission(int $idDateCommission): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT doss.*, dossAffect.ID_DOSSIER_AFFECT, dossAffect.HEURE_DEB_AFFECT, dossAffect.HEURE_FIN_AFFECT, dossAffect.NUM_DOSSIER, dossAffect.ID_DATECOMMISSION_AFFECT, dossNatListe.LIBELLE_DOSSIERNATURE, dossType.LIBELLE_DOSSIERTYPE, etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS, GROUP_CONCAT(dossierdocurba.NUM_DOCURBA SEPARATOR \', \') AS NUM_DOCURBA
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
            LEFT JOIN dossierdocurba ON doss.ID_DOSSIER = dossierdocurba.ID_DOSSIER
            WHERE dateComm.ID_DATECOMMISSION = :idDateCommission
            AND dossAffect.HEURE_DEB_AFFECT IS NULL
            AND dossAffect.HEURE_FIN_AFFECT IS NULL
            GROUP BY doss.ID_DOSSIER
            ORDER BY dossAffect.NUM_DOSSIER';

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['idDateCommission' => $idDateCommission]);

        return array_map(static fn (array $row): DossierAffectationDto => DossierAffectationDto::fromArray($row), $rows);
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

        $dossierIds = array_map(fn (array $row): int => (int) $row['ID_DOSSIER_AFFECT'], $dossiers);

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
     * Port de Model_DbTable_DateCommission::getEventInCommission() + Api_Service_Calendar::getEventCorps().
     *
     * - Si userId non null : retourne les dossiers du préventionniste + les dossiers sans préventionniste.
     * - Si commissionId non null : filtre par commission.
     * - Si les deux null (admin) : retourne tout.
     * - fromDate : filtre dc.DATE_COMMISSION >= fromDate (date glissante, 1 an en arrière).
     *
     * Retourne une ligne par couple DateCommission × Dossier.
     * Les natures, préventionnistes et contacts sont regroupés via GROUP_CONCAT.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForWebcalSync(?int $userId, ?int $commissionId, string $fromDate, bool $ignoreEts = false): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $params = ['fromDate' => $fromDate];

        // Sous-requête corrélée : libellés des natures de dossier regroupés
        $natureSubquery = "(SELECT GROUP_CONCAT(DISTINCT dnl2.LIBELLE_DOSSIERNATURE
                                ORDER BY dnl2.LIBELLE_DOSSIERNATURE SEPARATOR ', ')
                            FROM dossiernature dn2
                            JOIN dossiernatureliste dnl2 ON dn2.ID_NATURE = dnl2.ID_DOSSIERNATURE
                            WHERE dn2.ID_DOSSIER = d.ID_DOSSIER)";

        // Sous-requête corrélée : préventionnistes regroupés (grade + prenom + nom)
        // Include all preventionnistes, with NULL grade for those missing one
        $preventionnisteSubquery = "(SELECT GROUP_CONCAT(CONCAT(
                                            CASE
                                                WHEN COALESCE(ui2.GRADE_UTILISATEURINFORMATIONS, '') = ''
                                                THEN 'INCOMPLETE'
                                                ELSE TRIM(CONCAT_WS(' ',
                                                    NULLIF(ui2.GRADE_UTILISATEURINFORMATIONS, ''),
                                                    ui2.PRENOM_UTILISATEURINFORMATIONS,
                                                    ui2.NOM_UTILISATEURINFORMATIONS))
                                            END)
                                        ORDER BY ui2.NOM_UTILISATEURINFORMATIONS SEPARATOR ', ')
                                     FROM dossierpreventionniste dp2
                                     JOIN utilisateurinformations ui2
                                          ON dp2.ID_PREVENTIONNISTE = ui2.ID_UTILISATEURINFORMATIONS
                                     WHERE dp2.ID_DOSSIER = d.ID_DOSSIER
                                     AND ui2.NOM_UTILISATEURINFORMATIONS IS NOT NULL)";

        // Sous-requête corrélée : contacts du dossier (format "- FONCTION : NOM PRENOM, adresse, tel, mail")
        $contactFmt = $this->buildContactFormatSql('ui_dc', 'f_dc');
        $contactsDossierSubquery = "(SELECT GROUP_CONCAT({$contactFmt}
                                         ORDER BY ui_dc.NOM_UTILISATEURINFORMATIONS SEPARATOR '\n')
                                      FROM dossiercontact dc_sub
                                      JOIN utilisateurinformations ui_dc
                                           ON dc_sub.ID_UTILISATEURINFORMATIONS = ui_dc.ID_UTILISATEURINFORMATIONS
                                      LEFT JOIN fonction f_dc ON ui_dc.ID_FONCTION = f_dc.ID_FONCTION
                                      WHERE dc_sub.ID_DOSSIER = d.ID_DOSSIER
                                      AND ui_dc.NOM_UTILISATEURINFORMATIONS IS NOT NULL)";

        // Sous-requête corrélée : contacts de l'établissement
        $contactFmtEts = $this->buildContactFormatSql('ui_ec', 'f_ec');
        $contactsEtsSubquery = "(SELECT GROUP_CONCAT({$contactFmtEts}
                                      ORDER BY ui_ec.NOM_UTILISATEURINFORMATIONS SEPARATOR '\n')
                                   FROM etablissementcontact ec_sub
                                   JOIN utilisateurinformations ui_ec
                                        ON ec_sub.ID_UTILISATEURINFORMATIONS = ui_ec.ID_UTILISATEURINFORMATIONS
                                   LEFT JOIN fonction f_ec ON ui_ec.ID_FONCTION = f_ec.ID_FONCTION
                                   WHERE ec_sub.ID_ETABLISSEMENT = ets.ID_ETABLISSEMENT
                                   AND ui_ec.NOM_UTILISATEURINFORMATIONS IS NOT NULL)";

        // Sous-requête corrélée : coordonnées du maire (via adressecommune liée à l'adresse de l'établissement)
        $contactFmtMaire = $this->buildContactFormatSql('ui_m', 'f_m');
        $maireSubquery = "(SELECT {$contactFmtMaire}
                            FROM adressecommune ac_m
                            JOIN utilisateurinformations ui_m
                                 ON ac_m.ID_UTILISATEURINFORMATIONS = ui_m.ID_UTILISATEURINFORMATIONS
                            LEFT JOIN fonction f_m ON ui_m.ID_FONCTION = f_m.ID_FONCTION
                            WHERE ac_m.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
                            AND ui_m.NOM_UTILISATEURINFORMATIONS IS NOT NULL
                            LIMIT 1)";

        // Sous-requête corrélée : coordonnées du service instructeur (groupement par libellé)
        $contactFmtSi = $this->buildContactFormatSql('ui_si', 'f_si');
        $serviceInstructeurSubquery = "(SELECT {$contactFmtSi}
                                         FROM groupement g_si
                                         JOIN utilisateurinformations ui_si
                                              ON g_si.ID_UTILISATEURINFORMATIONS = ui_si.ID_UTILISATEURINFORMATIONS
                                         LEFT JOIN fonction f_si ON ui_si.ID_FONCTION = f_si.ID_FONCTION
                                         WHERE g_si.LIBELLE_GROUPEMENT = d.SERVICEINSTRUC_DOSSIER
                                         AND ui_si.NOM_UTILISATEURINFORMATIONS IS NOT NULL
                                         LIMIT 1)";

        // Sous-requête corrélée : avis d'exploitation (via le dossier donnant avis sur l'établissement)
        $avisSubquery = '(SELECT d_avis.AVIS_DOSSIER_COMMISSION
                           FROM dossier d_avis
                           WHERE d_avis.ID_DOSSIER = ets.ID_DOSSIER_DONNANT_AVIS
                           LIMIT 1)';

        // Filtre utilisateur : dossiers du préventionniste OU dossiers sans préventionniste assigné
        $userFilter = '';
        if (null !== $userId) {
            $userFilter = 'AND (
                EXISTS (
                    SELECT 1 FROM dossierpreventionniste dp_u
                    JOIN utilisateur u_f ON dp_u.ID_PREVENTIONNISTE = u_f.ID_UTILISATEUR
                    WHERE dp_u.ID_DOSSIER = d.ID_DOSSIER AND u_f.ID_UTILISATEUR = :userId
                )
                OR NOT EXISTS (
                    SELECT 1 FROM dossierpreventionniste dp_n WHERE dp_n.ID_DOSSIER = d.ID_DOSSIER
                )
            )';
            $params['userId'] = $userId;
        }

        $etablissementDossierJoin = $ignoreEts ? 'LEFT JOIN' : 'JOIN';

        $sql = "SELECT DISTINCT
                    dc.ID_DATECOMMISSION,
                    dc.DATE_COMMISSION,
                    dc.HEUREDEB_COMMISSION,
                    dc.HEUREFIN_COMMISSION,
                    dc.LIBELLE_DATECOMMISSION,
                    dc.ID_COMMISSIONTYPEEVENEMENT,
                    c.LIBELLE_COMMISSION,
                    d.ID_DOSSIER,
                    d.OBJET_DOSSIER,
                    d.TYPE_DOSSIER,
                    d.TYPESERVINSTRUC_DOSSIER,
                    d.SERVICEINSTRUC_DOSSIER,
                    da.HEURE_DEB_AFFECT,
                    da.HEURE_FIN_AFFECT,
                    dt.LIBELLE_DOSSIERTYPE,
                    {$natureSubquery} AS LIBELLE_DOSSIERNATURE,
                    ets.ID_ETABLISSEMENT,
                    ets.NUMEROID_ETABLISSEMENT,
                    ets.ID_DOSSIER_DONNANT_AVIS,
                    ei.LIBELLE_ETABLISSEMENTINFORMATIONS,
                    ei.ID_GENRE,
                    ea.NUMERO_ADRESSE,
                    ar.LIBELLE_RUE,
                    art.LIBELLE_RUETYPE,
                    com.LIBELLE_COMMUNE,
                    com.CODEPOSTAL_COMMUNE,
                    {$preventionnisteSubquery} AS PREVENTIONNISTES,
                    {$contactsDossierSubquery} AS CONTACTS_DOSSIER,
                    {$contactsEtsSubquery} AS CONTACTS_ETABLISSEMENT,
                    {$maireSubquery} AS MAIRE_CONTACT,
                    {$serviceInstructeurSubquery} AS SERVICE_INSTRUCTEUR_CONTACT,
                    drv.DATEVISITE_DOSSIER AS DERNIERE_VISITE,
                    {$avisSubquery} AS AVIS_DOSSIER_COMMISSION_VAL
                FROM dossier d
                JOIN dossieraffectation da ON d.ID_DOSSIER = da.ID_DOSSIER_AFFECT
                JOIN datecommission dc ON da.ID_DATECOMMISSION_AFFECT = dc.ID_DATECOMMISSION
                JOIN commission c ON dc.COMMISSION_CONCERNE = c.ID_COMMISSION
                {$etablissementDossierJoin} etablissementdossier edos ON d.ID_DOSSIER = edos.ID_DOSSIER
                JOIN dossiernature dn ON d.ID_DOSSIER = dn.ID_DOSSIER
                JOIN dossiertype dt ON d.TYPE_DOSSIER = dt.ID_DOSSIERTYPE
                JOIN dossierpreventionniste dp ON d.ID_DOSSIER = dp.ID_DOSSIER
                LEFT JOIN etablissement ets ON edos.ID_ETABLISSEMENT = ets.ID_ETABLISSEMENT
                LEFT JOIN etablissementinformations ei
                    ON ets.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
                    AND ei.ID_ETABLISSEMENTINFORMATIONS = (
                        SELECT MIN(ei2.ID_ETABLISSEMENTINFORMATIONS)
                        FROM etablissementinformations ei2
                        WHERE ei2.ID_ETABLISSEMENT = ets.ID_ETABLISSEMENT
                    )
                LEFT JOIN etablissementadresse ea
                    ON ets.ID_ETABLISSEMENT = ea.ID_ETABLISSEMENT
                    AND ea.ID_ADRESSE = (
                        SELECT MIN(ea2.ID_ADRESSE)
                        FROM etablissementadresse ea2
                        WHERE ea2.ID_ETABLISSEMENT = ets.ID_ETABLISSEMENT
                    )
                LEFT JOIN adresserue ar ON ea.ID_RUE = ar.ID_RUE
                LEFT JOIN adresseruetype art ON ar.ID_RUETYPE = art.ID_RUETYPE
                LEFT JOIN adressecommune com ON ea.NUMINSEE_COMMUNE = com.NUMINSEE_COMMUNE
                LEFT JOIN dossierdernierevisite drv ON ets.ID_ETABLISSEMENT = drv.ID_ETABLISSEMENT
                WHERE dc.DATE_COMMISSION >= :fromDate
                {$userFilter}";

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
     * Retourne le fragment SQL formatant un utilisateurinformations en ligne de contact.
     * Format : "- LIBELLE_FONCTION : NOM PRENOM[, adresse][, tel][, fax][, mail]".
     *
     * @param string $uiAlias  Alias de la table utilisateurinformations (ex: "ui_dc")
     * @param string $fonAlias Alias de la table fonction (ex: "f_dc")
     */
    private function buildContactFormatSql(string $uiAlias, string $fonAlias): string
    {
        return "CONCAT(
            COALESCE(CONCAT('- ', {$fonAlias}.LIBELLE_FONCTION, ' : '), '- : '),
            COALESCE({$uiAlias}.NOM_UTILISATEURINFORMATIONS, ''),
            COALESCE(CONCAT(' ', {$uiAlias}.PRENOM_UTILISATEURINFORMATIONS), ''),
            IF({$uiAlias}.NUMEROADRESSE_UTILISATEURINFORMATIONS IS NOT NULL
               AND {$uiAlias}.RUEADRESSE_UTILISATEURINFORMATIONS IS NOT NULL
               AND {$uiAlias}.CPADRESSE_UTILISATEURINFORMATIONS IS NOT NULL
               AND {$uiAlias}.VILLEADRESSE_UTILISATEURINFORMATIONS IS NOT NULL,
               CONCAT(', ', {$uiAlias}.NUMEROADRESSE_UTILISATEURINFORMATIONS, ' ',
                      {$uiAlias}.RUEADRESSE_UTILISATEURINFORMATIONS, ', ',
                      {$uiAlias}.CPADRESSE_UTILISATEURINFORMATIONS, ' ',
                      {$uiAlias}.VILLEADRESSE_UTILISATEURINFORMATIONS),
               ''),
            COALESCE(CONCAT(', ', {$uiAlias}.TELFIXE_UTILISATEURINFORMATIONS), ''),
            COALESCE(CONCAT(', ', {$uiAlias}.TELFAX_UTILISATEURINFORMATIONS), ''),
            COALESCE(CONCAT(', ', {$uiAlias}.MAIL_UTILISATEURINFORMATIONS), '')
        )";
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
    public function findDossiersForGeneration(int $dateCommId, int $gestionHeures, bool $filterNullHeure = false): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $orderBy = 1 === $gestionHeures ? 'dossAffect.HEURE_DEB_AFFECT' : 'dossAffect.NUM_DOSSIER';

        $heureFilter = '';
        if ($filterNullHeure && 1 === $gestionHeures) {
            $heureFilter = 'AND dossAffect.HEURE_DEB_AFFECT IS NOT NULL';
        }

        $sql = 'SELECT doss.*, dossAffect.ID_DOSSIER_AFFECT, dossAffect.HEURE_DEB_AFFECT,
                       dossAffect.HEURE_FIN_AFFECT, dossAffect.NUM_DOSSIER,
                       dossNatListe.LIBELLE_DOSSIERNATURE
                FROM dossieraffectation dossAffect
                JOIN dossier doss ON doss.ID_DOSSIER = dossAffect.ID_DOSSIER_AFFECT
                JOIN dossiernature dossNat ON dossNat.ID_DOSSIER = doss.ID_DOSSIER
                JOIN dossiernatureliste dossNatListe ON dossNat.ID_NATURE = dossNatListe.ID_DOSSIERNATURE
                WHERE dossAffect.ID_DATECOMMISSION_AFFECT = :dateCommId
                '.$heureFilter.'
                GROUP BY doss.ID_DOSSIER
                ORDER BY '.$orderBy;

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($sql, ['dateCommId' => $dateCommId]);

        return $rows;
    }
}

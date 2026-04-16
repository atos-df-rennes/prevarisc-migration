<?php

namespace App\Repository;

use App\Entity\DateCommission;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        if ([] !== $commissionIds) {
            $inList = implode(',', array_map('intval', $commissionIds));
            $select .= ' AND d.COMMISSION_CONCERNE IN ('.$inList.')';
        }

        $select .= ' ORDER BY DATE_COMMISSION, HEUREDEB_COMMISSION';

        $params = ['fromDate' => $fromDate, 'toDate' => $toDate];

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $conn->fetchAllAssociative($select, $params);

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

        if ([] !== $commissionIds) {
            $inList = implode(',', array_map('intval', $commissionIds));
            $sql .= ' AND d.COMMISSION_CONCERNE IN ('.$inList.')';
        }

        $params = ['fromDate' => $fromDate, 'toDate' => $toDate];
        $count = $conn->fetchOne($sql, $params);

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
        $sql = 'SELECT doss.*, dossType.LIBELLE_DOSSIERTYPE, etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS
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
        $sql = 'SELECT doss.*, dossType.LIBELLE_DOSSIERTYPE, etablissementinformations.LIBELLE_ETABLISSEMENTINFORMATIONS
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
}

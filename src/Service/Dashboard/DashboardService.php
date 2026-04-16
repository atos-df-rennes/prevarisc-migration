<?php

namespace App\Service\Dashboard;

use App\Entity\Utilisateur;
use App\Repository\DateCommissionRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Service\FeedService;
use App\Service\NotificationService;

/**
 * Orchestration des données pour les blocs du tableau de bord.
 * Port direct de Service_Dashboard et Service_Count (legacy), fusionnés en un seul service.
 *
 * Chaque méthode getData* retourne les données pour un bloc,
 * chaque méthode getCount* retourne le comptage.
 */
class DashboardService
{
    public const ID_DOSSIERTYPE_COURRIER = 5;

    public const ID_NATURE_LEVEE_PRESCRIPTIONS = 7;

    public const ID_NATURE_LEVEE_AVIS_DEF = 19;

    public const ID_NATURE_ECHEANCIER_TRAVAUX = 46;

    /** @var DashboardBlocConfig */
    private $blocConfig;

    /** @var DateCommissionRepository */
    private $dateCommissionRepository;

    /** @var EtablissementRepository */
    private $etablissementRepository;

    /** @var DossierRepository */
    private $dossierRepository;

    /** @var FeedService */
    private $feedService;

    /** @var NotificationService */
    private $notificationService;

    public function __construct(
        DashboardBlocConfig $blocConfig,
        DateCommissionRepository $dateCommissionRepository,
        EtablissementRepository $etablissementRepository,
        DossierRepository $dossierRepository,
        FeedService $feedService,
        NotificationService $notificationService
    ) {
        $this->blocConfig = $blocConfig;
        $this->dateCommissionRepository = $dateCommissionRepository;
        $this->etablissementRepository = $etablissementRepository;
        $this->dossierRepository = $dossierRepository;
        $this->feedService = $feedService;
        $this->notificationService = $notificationService;
    }

    /**
     * Retourne les données pour un bloc du tableau de bord.
     *
     * @return array<int|string, mixed>
     */
    public function getDataForBloc(string $blocId, Utilisateur $user): array
    {
        switch ($blocId) {
            case 'nextCommissions':
            case 'nextCommissionsOdj':
                return $this->getNextCommission($user);

            case 'ERPSuivis':
                return $this->getERPSuivis($user);

            case 'ERPOuvertsSousAvisDefavorable':
                return $this->getERPOuvertsSousAvisDefavorable();

            case 'ERPOuvertsSousAvisDefavorableSuivis':
                return $this->getERPOuvertsSousAvisDefavorableSuivis($user);

            case 'ERPOuvertsSousAvisDefavorableSurCommune':
                return $this->getERPOuvertsSousAvisDefavorableSurCommune($user);

            case 'ERPSansPreventionniste':
                return $this->getERPSansPreventionniste();

            case 'ERPOuvertsSansProchainesVisitePeriodiques':
                return $this->getERPOuvertsSansProchainesVisitePeriodiques($user);

            case 'DossiersSuivisSansAvis':
                return $this->getDossiersSuivisSansAvis($user);

            case 'DossiersSuivisNonVerrouilles':
                return $this->getDossiersSuivisNonVerrouilles($user);

            case 'DossierDateCommissionEchu':
                return $this->getDossierDateCommissionEchu($user);

            case 'DossierAvecAvisDiffere':
                return $this->getDossierAvecAvisDiffere($user);

            case 'CourrierSansReponse':
                return $this->getCourrierSansReponse();

            case 'feeds':
                return $this->feedService->getFeeds($user);

            case 'leveePresc':
                return $this->getLeveePresc();

            case 'absQuorum':
                return $this->getAbsenceQuorum();

            case 'npsp':
                return $this->getNpsp();

            case 'dossiersIncomplets':
                return $this->getDossiersIncomplets();

            case 'dossierPlatau':
                return $this->getDossiersPlatauSansEtablissement($user);

            case 'pjPlatauErreur':
                return $this->getDossiersPlatauPjsEnErreur();

            case 'nouvellesPjsPlatau':
                return $this->getDossiersPlatauNouvellesPjs($user);

            default:
                return [];
        }
    }

    /**
     * Retourne le nombre d'éléments pour un bloc du tableau de bord.
     */
    public function getCountForBloc(string $blocId, Utilisateur $user): int
    {
        switch ($blocId) {
            case 'nextCommissions':
            case 'nextCommissionsOdj':
                return $this->getNextCommissionCount($user);

            case 'ERPSuivis':
                return $this->getERPSuivisCount($user);

            case 'ERPOuvertsSousAvisDefavorable':
                return $this->getERPOuvertsSousAvisDefavorableCount();

            case 'ERPOuvertsSousAvisDefavorableSuivis':
                return $this->getERPOuvertsSousAvisDefavorableSuivisCount($user);

            case 'ERPOuvertsSousAvisDefavorableSurCommune':
                return $this->getERPOuvertsSousAvisDefavorableSurCommuneCount($user);

            case 'ERPSansPreventionniste':
                return $this->getERPSansPreventionnisteCount();

            case 'ERPOuvertsSansProchainesVisitePeriodiques':
                return $this->getERPOuvertsSansProchainesVisitePeriodiquesCount($user);

            case 'DossiersSuivisSansAvis':
                return $this->getDossiersSuivisSansAvisCount($user);

            case 'DossiersSuivisNonVerrouilles':
                return $this->getDossiersSuivisNonVerrouillesCount($user);

            case 'DossierDateCommissionEchu':
                return $this->getDossierDateCommissionEchuCount($user);

            case 'DossierAvecAvisDiffere':
                return $this->getDossierAvecAvisDiffereCount($user);

            case 'CourrierSansReponse':
                return $this->getCourrierSansReponseCount();

            case 'feeds':
                return $this->feedService->getFeedsCount($user);

            case 'leveePresc':
                return count($this->getLeveePresc());

            case 'absQuorum':
                return count($this->getAbsenceQuorum());

            case 'npsp':
                return count($this->getNpsp());

            case 'dossiersIncomplets':
                return $this->getDossiersIncompleCountDirecte();

            case 'dossierPlatau':
                return $this->getDossiersPlatauSansEtablissementCount();

            case 'pjPlatauErreur':
                return $this->getDossiersPlatauPjsEnErreurCount();

            case 'nouvellesPjsPlatau':
                return $this->getDossiersPlatauNouvellesPjsCount($user);

            default:
                return 0;
        }
    }

    /**
     * Retourne les IDs des commissions associées à l'utilisateur.
     *
     * @return int[]
     */
    private function getCommissionIds(Utilisateur $user): array
    {
        $commissionIds = [];
        foreach ($user->getCommissions() as $commission) {
            $commissionIds[] = (int) $commission->getId();
        }

        return $commissionIds;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getNextCommission(Utilisateur $user): array
    {
        $commissionIds = $this->getCommissionIds($user);
        $from = time();
        $to = time() + 3600 * 24 * $this->blocConfig->getNextCommissionsDays();

        $prochainesCommissions = $this->dateCommissionRepository->findNextCommissionsForDashboard(
            $commissionIds,
            $from,
            $to
        );

        $commissions = [];
        foreach ($prochainesCommissions as $commissiondujour) {
            $rawHeureDebut = $commissiondujour['HEUREDEB_COMMISSION'] ?? '';
            $rawHeureFin = $commissiondujour['HEUREFIN_COMMISSION'] ?? '';
            $heureDebut = is_string($rawHeureDebut) ? $rawHeureDebut : '';
            $heureFin = is_string($rawHeureFin) ? $rawHeureFin : '';

            $commissions[] = [
                'id' => $commissiondujour['ID_DATECOMMISSION'],
                'LIBELLE_COMMISSION' => $commissiondujour['LIBELLE_COMMISSION'],
                'LIBELLE_DATECOMMISSION' => $commissiondujour['LIBELLE_DATECOMMISSION'],
                'ID_COMMISSIONTYPEEVENEMENT' => $commissiondujour['ID_COMMISSIONTYPEEVENEMENT'],
                'DATE_COMMISSION' => $commissiondujour['DATE_COMMISSION'],
                'HEUREDEB_COMMISSION' => $heureDebut,
                'HEUREFIN_COMMISSION' => $heureFin,
                'heure' => substr($heureDebut, 0, 5).' - '.substr($heureFin, 0, 5),
                'odj' => $this->getCommissionOdj($commissiondujour),
            ];
        }

        return $commissions;
    }

    private function getNextCommissionCount(Utilisateur $user): int
    {
        $commissionIds = $this->getCommissionIds($user);
        $from = time();
        $to = time() + 3600 * 24 * $this->blocConfig->getNextCommissionsDays();

        return $this->dateCommissionRepository->countNextCommissionsForDashboard($commissionIds, $from, $to);
    }

    /**
     * @param array<string, mixed> $commission
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCommissionOdj(array $commission): array
    {
        $rawId = $commission['ID_DATECOMMISSION'] ?? null;
        $idDateCommission = is_numeric($rawId) ? (int) $rawId : 0;
        $dossiersNonAffect = $this->dateCommissionRepository->findDossierNonAffectForCommission($idDateCommission);
        $dossiersAffect = $this->dateCommissionRepository->findDossierAffectForCommission($idDateCommission);

        $odj = array_merge($dossiersNonAffect, $dossiersAffect);

        return array_unique($odj, SORT_REGULAR);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getERPSuivis(Utilisateur $user): array
    {
        return $this->etablissementRepository->findERPSuivisByUser((int) $user->getId());
    }

    private function getERPSuivisCount(Utilisateur $user): int
    {
        return $this->etablissementRepository->countERPSuivisByUser((int) $user->getId());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getERPOuvertsSousAvisDefavorable(): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = $this->etablissementRepository->findERPOuvertsSousAvisDefavorable();

        return $result;
    }

    private function getERPOuvertsSousAvisDefavorableCount(): int
    {
        return $this->etablissementRepository->countERPOuvertsSousAvisDefavorable();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getERPOuvertsSousAvisDefavorableSuivis(Utilisateur $user): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = $this->etablissementRepository->findERPOuvertsSousAvisDefavorable(null, null, (int) $user->getId());

        return $result;
    }

    private function getERPOuvertsSousAvisDefavorableSuivisCount(Utilisateur $user): int
    {
        return $this->etablissementRepository->countERPOuvertsSousAvisDefavorable(null, null, (int) $user->getId());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getERPOuvertsSousAvisDefavorableSurCommune(Utilisateur $user): array
    {
        if (!$user->getNuminsee()) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->etablissementRepository->findERPOuvertsSousAvisDefavorable(null, $user->getNuminsee());

        return $result;
    }

    private function getERPOuvertsSousAvisDefavorableSurCommuneCount(Utilisateur $user): int
    {
        if (!$user->getNuminsee()) {
            return 0;
        }

        return $this->etablissementRepository->countERPOuvertsSousAvisDefavorable(null, $user->getNuminsee());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getERPSansPreventionniste(): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = $this->etablissementRepository->findERPSansPreventionniste();

        return $result;
    }

    private function getERPSansPreventionnisteCount(): int
    {
        return $this->etablissementRepository->countERPSansPreventionniste();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getERPOuvertsSansProchainesVisitePeriodiques(Utilisateur $user): array
    {
        return $this->etablissementRepository->findERPOuvertsSansProchainesVisitePeriodiques(
            $this->getCommissionIds($user)
        );
    }

    private function getERPOuvertsSansProchainesVisitePeriodiquesCount(Utilisateur $user): int
    {
        return $this->etablissementRepository->countERPOuvertsSansProchainesVisitePeriodiques(
            $this->getCommissionIds($user)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDossiersSuivisSansAvis(Utilisateur $user): array
    {
        return $this->dossierRepository->findDossiersSuivisSansAvis((int) $user->getId());
    }

    private function getDossiersSuivisSansAvisCount(Utilisateur $user): int
    {
        return $this->dossierRepository->countDossiersSuivisSansAvis((int) $user->getId());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDossiersSuivisNonVerrouilles(Utilisateur $user): array
    {
        return $this->dossierRepository->findDossiersSuivisNonVerrouilles((int) $user->getId());
    }

    private function getDossiersSuivisNonVerrouillesCount(Utilisateur $user): int
    {
        return $this->dossierRepository->countDossiersSuivisNonVerrouilles((int) $user->getId());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDossierDateCommissionEchu(Utilisateur $user): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = $this->dossierRepository->findDossierDateCommissionEchu(
            $this->getCommissionIds($user),
            $this->blocConfig->getDossiersSansAvisDays()
        );

        return $result;
    }

    private function getDossierDateCommissionEchuCount(Utilisateur $user): int
    {
        return $this->dossierRepository->countDossierDateCommissionEchu(
            $this->getCommissionIds($user),
            $this->blocConfig->getDossiersSansAvisDays()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDossierAvecAvisDiffere(Utilisateur $user): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = $this->dossierRepository->findDossierAvecAvisDiffere($this->getCommissionIds($user));

        return $result;
    }

    private function getDossierAvecAvisDiffereCount(Utilisateur $user): int
    {
        return $this->dossierRepository->countDossierAvecAvisDiffere($this->getCommissionIds($user));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCourrierSansReponse(): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = $this->dossierRepository->findCourrierSansReponse($this->blocConfig->getCourrierSansReponseDays());

        return $result;
    }

    private function getCourrierSansReponseCount(): int
    {
        return $this->dossierRepository->countCourrierSansReponse($this->blocConfig->getCourrierSansReponseDays());
    }

    /**
     * Dossiers avec date de levée des prescriptions.
     * PHP post-filtering : supprime ceux qui ont un dossier lié de nature 7, 19 ou 46.
     * Port direct de Service_Dashboard::getLeveePresc().
     *
     * @return array<int, array<string, mixed>>
     */
    private function getLeveePresc(): array
    {
        $dossiers = $this->dossierRepository->findLeveePresc();
        $filtered = [];

        foreach ($dossiers as $dossier) {
            $rawDossierId = $dossier['ID_DOSSIER'] ?? 0;
            $dossiersLies = $this->dossierRepository->findDossiersLies(is_numeric($rawDossierId) ? (int) $rawDossierId : 0);
            $exclude = false;

            foreach ($dossiersLies as $lien) {
                $rawDossier1 = $lien['ID_DOSSIER1'] ?? 0;
                $rawDossier2 = $lien['ID_DOSSIER2'] ?? 0;
                $idDossier = is_numeric($rawDossierId) ? (int) $rawDossierId : 0;
                $id1 = is_numeric($rawDossier1) ? (int) $rawDossier1 : 0;
                $id2 = is_numeric($rawDossier2) ? (int) $rawDossier2 : 0;
                $idLien = ($id1 === $idDossier) ? $id2 : $id1;

                $idNature = $this->dossierRepository->getDossierNatureId($idLien);

                if (
                    self::ID_NATURE_LEVEE_AVIS_DEF === $idNature
                    || self::ID_NATURE_LEVEE_PRESCRIPTIONS === $idNature
                    || self::ID_NATURE_ECHEANCIER_TRAVAUX === $idNature
                ) {
                    $exclude = true;
                    break;
                }
            }

            if (!$exclude) {
                $filtered[] = $dossier;
            }
        }

        return $filtered;
    }

    /**
     * Dossiers ayant une absence de quorum.
     * PHP post-filtering : supprime ceux dont le dossier lié est de type 0 ou 5 (courrier).
     * Port direct de Service_Dashboard::getAbsenceQuorum().
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAbsenceQuorum(): array
    {
        $dossiers = $this->dossierRepository->findAbsenceQuorum();

        return $this->filterByLinkedDossierType($dossiers);
    }

    /**
     * Dossiers avec statut NPSP (ne peut se prononcer).
     * PHP post-filtering : même logique que absQuorum.
     * Port direct de Service_Dashboard::getNpsp().
     *
     * @return array<int, array<string, mixed>>
     */
    private function getNpsp(): array
    {
        $dossiers = $this->dossierRepository->findNpsp();

        return $this->filterByLinkedDossierType($dossiers);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDossiersIncomplets(): array
    {
        return $this->dossierRepository->findDossiersIncomplets();
    }

    private function getDossiersIncompleCountDirecte(): int
    {
        return $this->dossierRepository->countDossiersIncomplets();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDossiersPlatauSansEtablissement(Utilisateur $user): array
    {
        $lastVisit = $this->notificationService->getLastVisitDate($user, NotificationService::DASHBOARD_NAMESPACE);

        return $this->dossierRepository->findDossiersPlatauSansEtablissement($lastVisit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDossiersPlatauPjsEnErreur(): array
    {
        return $this->dossierRepository->findDossiersPlatauPjsEnErreur();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDossiersPlatauNouvellesPjs(Utilisateur $user): array
    {
        $lastVisitPieces = $this->notificationService->getLastVisitDate($user, NotificationService::PIECES_DOSSIER_NAMESPACE);

        return $this->dossierRepository->findDossiersPlatauNouvellesPjs($lastVisitPieces);
    }

    private function getDossiersPlatauSansEtablissementCount(): int
    {
        return $this->dossierRepository->countDossiersPlatauSansEtablissement();
    }

    private function getDossiersPlatauPjsEnErreurCount(): int
    {
        return $this->dossierRepository->countDossiersPlatauPjsEnErreur();
    }

    private function getDossiersPlatauNouvellesPjsCount(Utilisateur $user): int
    {
        $lastVisitPieces = $this->notificationService->getLastVisitDate($user, NotificationService::PIECES_DOSSIER_NAMESPACE);

        return $this->dossierRepository->countDossiersPlatauNouvellesPjs($lastVisitPieces);
    }

    /**
     * Filtre les dossiers en supprimant ceux dont le dossier lié est de type 0 ou 5 (courrier).
     * Commun à absQuorum et npsp.
     *
     * @param array<int, array<string, mixed>> $dossiers
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterByLinkedDossierType(array $dossiers): array
    {
        $filtered = [];

        foreach ($dossiers as $dossier) {
            $rawDossierId = $dossier['ID_DOSSIER'] ?? 0;
            $dossiersLies = $this->dossierRepository->findDossiersLies(is_numeric($rawDossierId) ? (int) $rawDossierId : 0);
            $exclude = false;

            foreach ($dossiersLies as $lien) {
                $rawDossier1 = $lien['ID_DOSSIER1'] ?? 0;
                $rawDossier2 = $lien['ID_DOSSIER2'] ?? 0;
                $idDossier = is_numeric($rawDossierId) ? (int) $rawDossierId : 0;
                $id1 = is_numeric($rawDossier1) ? (int) $rawDossier1 : 0;
                $id2 = is_numeric($rawDossier2) ? (int) $rawDossier2 : 0;
                $idLien = ($id1 === $idDossier) ? $id2 : $id1;

                $typeDossier = $this->dossierRepository->getDossierType($idLien);

                if (0 === $typeDossier || self::ID_DOSSIERTYPE_COURRIER === $typeDossier) {
                    $exclude = true;
                    break;
                }
            }

            if (!$exclude) {
                $filtered[] = $dossier;
            }
        }

        return $filtered;
    }
}

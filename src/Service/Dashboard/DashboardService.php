<?php

namespace App\Service\Dashboard;

use App\Dto\Repository\DossierAffectationDto;
use App\Dto\Repository\DossierDashboardDto;
use App\Dto\Repository\DossierDateCommissionEchuDto;
use App\Dto\Repository\EtablissementDashboardDto;
use App\Dto\Repository\NextCommissionDto;
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
            $heureDebut = is_string($commissiondujour->HEUREDEB_COMMISSION) ? $commissiondujour->HEUREDEB_COMMISSION : '';
            $heureFin = is_string($commissiondujour->HEUREFIN_COMMISSION) ? $commissiondujour->HEUREFIN_COMMISSION : '';

            $commissions[] = [
                'id' => $commissiondujour->ID_DATECOMMISSION,
                'LIBELLE_COMMISSION' => $commissiondujour->LIBELLE_COMMISSION,
                'LIBELLE_DATECOMMISSION' => $commissiondujour->LIBELLE_DATECOMMISSION,
                'ID_COMMISSIONTYPEEVENEMENT' => $commissiondujour->ID_COMMISSIONTYPEEVENEMENT,
                'DATE_COMMISSION' => $commissiondujour->DATE_COMMISSION,
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
     * @return DossierAffectationDto[]
     */
    private function getCommissionOdj(NextCommissionDto $commission): array
    {
        $idDateCommission = (int) $commission->ID_DATECOMMISSION;
        $dossiersNonAffect = $this->dateCommissionRepository->findDossierNonAffectForCommission($idDateCommission);
        $dossiersAffect = $this->dateCommissionRepository->findDossierAffectForCommission($idDateCommission);

        /** @var array<int, DossierAffectationDto> $indexed */
        $indexed = [];
        foreach ($dossiersNonAffect as $d) {
            $indexed[(int) $d->ID_DOSSIER] = $d;
        }

        foreach ($dossiersAffect as $d) {
            $indexed[(int) $d->ID_DOSSIER] = $d;
        }

        return array_values($indexed);
    }

    /**
     * @return EtablissementDashboardDto[]
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
     * @return EtablissementDashboardDto[]
     */
    private function getERPOuvertsSousAvisDefavorable(): array
    {
        return $this->etablissementRepository->findERPOuvertsSousAvisDefavorable();
    }

    private function getERPOuvertsSousAvisDefavorableCount(): int
    {
        return $this->etablissementRepository->countERPOuvertsSousAvisDefavorable();
    }

    /**
     * @return EtablissementDashboardDto[]
     */
    private function getERPOuvertsSousAvisDefavorableSuivis(Utilisateur $user): array
    {
        return $this->etablissementRepository->findERPOuvertsSousAvisDefavorable(null, null, (int) $user->getId());
    }

    private function getERPOuvertsSousAvisDefavorableSuivisCount(Utilisateur $user): int
    {
        return $this->etablissementRepository->countERPOuvertsSousAvisDefavorable(null, null, (int) $user->getId());
    }

    /**
     * @return EtablissementDashboardDto[]
     */
    private function getERPOuvertsSousAvisDefavorableSurCommune(Utilisateur $user): array
    {
        if (!$user->getNuminsee()) {
            return [];
        }

        return $this->etablissementRepository->findERPOuvertsSousAvisDefavorable(null, $user->getNuminsee());
    }

    private function getERPOuvertsSousAvisDefavorableSurCommuneCount(Utilisateur $user): int
    {
        if (!$user->getNuminsee()) {
            return 0;
        }

        return $this->etablissementRepository->countERPOuvertsSousAvisDefavorable(null, $user->getNuminsee());
    }

    /**
     * @return EtablissementDashboardDto[]
     */
    private function getERPSansPreventionniste(): array
    {
        return $this->etablissementRepository->findERPSansPreventionniste();
    }

    private function getERPSansPreventionnisteCount(): int
    {
        return $this->etablissementRepository->countERPSansPreventionniste();
    }

    /**
     * @return EtablissementDashboardDto[]
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
     * @return DossierDashboardDto[]
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
     * @return DossierDashboardDto[]
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
     * @return DossierDateCommissionEchuDto[]
     */
    private function getDossierDateCommissionEchu(Utilisateur $user): array
    {
        return $this->dossierRepository->findDossierDateCommissionEchu(
            $this->getCommissionIds($user),
            $this->blocConfig->getDossiersSansAvisDays()
        );
    }

    private function getDossierDateCommissionEchuCount(Utilisateur $user): int
    {
        return $this->dossierRepository->countDossierDateCommissionEchu(
            $this->getCommissionIds($user),
            $this->blocConfig->getDossiersSansAvisDays()
        );
    }

    /**
     * @return DossierDashboardDto[]
     */
    private function getDossierAvecAvisDiffere(Utilisateur $user): array
    {
        return $this->dossierRepository->findDossierAvecAvisDiffere($this->getCommissionIds($user));
    }

    private function getDossierAvecAvisDiffereCount(Utilisateur $user): int
    {
        return $this->dossierRepository->countDossierAvecAvisDiffere($this->getCommissionIds($user));
    }

    /**
     * @return DossierDashboardDto[]
     */
    private function getCourrierSansReponse(): array
    {
        return $this->dossierRepository->findCourrierSansReponse($this->blocConfig->getCourrierSansReponseDays());
    }

    private function getCourrierSansReponseCount(): int
    {
        return $this->dossierRepository->countCourrierSansReponse($this->blocConfig->getCourrierSansReponseDays());
    }

    /**
     * Dossiers avec date de levée des prescriptions.
     * Le filtrage (exclusion des natures 7, 19, 46) est géré directement par le repository.
     *
     * @return DossierDashboardDto[]
     */
    private function getLeveePresc(): array
    {
        return $this->dossierRepository->findLeveePresc();
    }

    /**
     * Dossiers ayant une absence de quorum.
     * Le filtrage (exclusion des types 0 et 5) est géré directement par le repository.
     *
     * @return DossierDashboardDto[]
     */
    private function getAbsenceQuorum(): array
    {
        return $this->dossierRepository->findAbsenceQuorum();
    }

    /**
     * Dossiers avec statut NPSP (ne peut se prononcer).
     * Le filtrage (exclusion des types 0 et 5) est géré directement par le repository.
     *
     * @return DossierDashboardDto[]
     */
    private function getNpsp(): array
    {
        return $this->dossierRepository->findNpsp();
    }

    /**
     * @return DossierDashboardDto[]
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
}

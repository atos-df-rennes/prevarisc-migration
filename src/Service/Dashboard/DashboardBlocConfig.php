<?php

namespace App\Service\Dashboard;

/**
 * Configuration des blocs du tableau de bord.
 * Port direct de Service_Dashboard::$blocsConfig (legacy).
 *
 * Chaque bloc contient :
 *   - method : nom de la méthode du DashboardService à appeler
 *   - acl    : privilege requis (string) ou null si pas de restriction
 *   - title  : libellé affiché dans l'entête du bloc
 *   - type   : type de partial à utiliser (commissions, odj, etablissements, dossiers, feeds)
 */
final class DashboardBlocConfig
{
    /** @var array<string, array<string, mixed>> */
    private $blocsConfig = [
        'nextCommissions' => [
            'method' => 'getNextCommission',
            'acl' => 'view_next_commissions',
            'title' => 'Prochaines commissions',
            'type' => 'commissions',
        ],
        'nextCommissionsOdj' => [
            'method' => 'getNextCommission',
            'acl' => 'view_next_commissions_odj',
            'title' => 'Prochaines commissions',
            'type' => 'odj',
        ],
        'ERPSuivis' => [
            'method' => 'getERPSuivis',
            'acl' => 'view_ets_suivis',
            'title' => 'Etablissements suivis',
            'type' => 'etablissements',
        ],
        'ERPOuvertsSousAvisDefavorable' => [
            'method' => 'getERPOuvertsSousAvisDefavorable',
            'acl' => 'view_ets_avis_defavorable',
            'title' => 'Etablissements sous avis défavorable',
            'type' => 'etablissements',
        ],
        'ERPOuvertsSousAvisDefavorableSuivis' => [
            'method' => 'getERPOuvertsSousAvisDefavorableSuivis',
            'acl' => 'view_ets_avis_defavorable_suivis',
            'title' => 'Etablissements suivis sous avis défavorable',
            'type' => 'etablissements',
        ],
        'ERPOuvertsSousAvisDefavorableSurCommune' => [
            'method' => 'getERPOuvertsSousAvisDefavorableSurCommune',
            'acl' => 'view_ets_avis_defavorable_sur_commune',
            'title' => 'Etablissements de votre commune sous avis défavorable',
            'type' => 'etablissements',
        ],
        'ERPSansPreventionniste' => [
            'method' => 'getERPSansPreventionniste',
            'acl' => 'view_ets_sans_preventionniste',
            'title' => 'Etablissements sans préventionnistes',
            'type' => 'etablissements',
        ],
        'ERPOuvertsSansProchainesVisitePeriodiques' => [
            'method' => 'getERPOuvertsSansProchainesVisitePeriodiques',
            'acl' => 'view_ets_ouverts_sans_prochaine_vp',
            'title' => 'Etablissements sans prochaine VP cette année',
            'type' => 'etablissements',
        ],
        'DossiersSuivisSansAvis' => [
            'method' => 'getDossiersSuivisSansAvis',
            'acl' => 'view_doss_suivis_sans_avis',
            'title' => 'Dossiers suivis sans avis du rapporteur',
            'type' => 'dossiers',
        ],
        'DossiersSuivisNonVerrouilles' => [
            'method' => 'getDossiersSuivisNonVerrouilles',
            'acl' => 'view_doss_suivis_unlocked',
            'title' => 'Dossiers suivis non verrouillés',
            'type' => 'dossiers',
        ],
        'DossierDateCommissionEchu' => [
            'method' => 'getDossierDateCommissionEchu',
            'acl' => 'view_doss_sans_avis',
            'title' => 'Dossiers sans avis de commission',
            'type' => 'dossiers',
        ],
        'DossierAvecAvisDiffere' => [
            'method' => 'getDossierAvecAvisDiffere',
            'acl' => 'view_doss_avis_differe',
            'title' => 'Dossiers avec avis différés',
            'type' => 'dossiers',
        ],
        'CourrierSansReponse' => [
            'method' => 'getCourrierSansReponse',
            'acl' => 'view_courrier_sans_reponse',
            'title' => 'Courriers sans réponse',
            'type' => 'dossiers',
        ],
        'feeds' => [
            'method' => 'getFeeds',
            'acl' => null,
            'title' => 'Messages',
            'type' => 'feeds',
        ],
        'leveePresc' => [
            'method' => 'getLeveePresc',
            'acl' => 'view_doss_levee_prescriptions',
            'title' => 'Dossiers avec une date de levée des prescriptions',
            'type' => 'dossiers',
        ],
        'absQuorum' => [
            'method' => 'getAbsenceQuorum',
            'acl' => 'view_doss_absence_quorum',
            'title' => 'Dossiers ayant une absence de quorum',
            'type' => 'dossiers',
        ],
        'npsp' => [
            'method' => 'getNpsp',
            'acl' => 'view_doss_npsp',
            'title' => 'Dossiers avec statut ne peut se prononcer',
            'type' => 'dossiers',
        ],
        'dossiersIncomplets' => [
            'method' => 'getDossiersIncomplets',
            'acl' => 'view_doss_incomplets',
            'title' => 'Dossiers incomplets',
            'type' => 'dossiers',
        ],
    ];

    public function __construct(
        private readonly bool $deactivatePlatau,
        private readonly int $nextCommissionsDays,
        private readonly int $dossiersSansAvisDays,
        private readonly int $courrierSansReponseDays
    ) {
        if (!$this->deactivatePlatau) {
            $this->blocsConfig['dossierPlatau'] = [
                'method' => 'getDossiersPlatauSansEtablissement',
                'acl' => 'view_doss_platau_sans_etab',
                'title' => "Dossiers Plat'AU à traiter",
                'type' => 'dossiers',
            ];
            $this->blocsConfig['pjPlatauErreur'] = [
                'method' => 'getDossiersPlatauPjsEnErreur',
                'acl' => 'view_doss_platau_pjs_en_erreur',
                'title' => "Dossiers Plat'AU avec des pièces jointes non envoyées",
                'type' => 'dossiers',
            ];
            $this->blocsConfig['nouvellesPjsPlatau'] = [
                'method' => 'getDossiersPlatauNouvellesPjs',
                'acl' => 'view_doss_platau_nouvelles_pjs',
                'title' => "Dossiers Plat'AU avec de nouvelles pièces jointes",
                'type' => 'dossiers',
            ];
        }
    }

    /**
     * Retourne la configuration de tous les blocs.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getBlocConfig(): array
    {
        return $this->blocsConfig;
    }

    public function getNextCommissionsDays(): int
    {
        return $this->nextCommissionsDays;
    }

    public function getDossiersSansAvisDays(): int
    {
        return $this->dossiersSansAvisDays;
    }

    public function getCourrierSansReponseDays(): int
    {
        return $this->courrierSansReponseDays;
    }
}

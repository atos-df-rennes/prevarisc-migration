<?php

declare(strict_types=1);

namespace App\Service\SuppressionUtilisateurs;

use App\Enum\MotifConservationUtilisateurEnum;
use App\Enum\ResultatSuppressionUtilisateurEnum;
use App\Repository\GroupeRepository;
use App\Repository\UtilisateurRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Suppression définitive des utilisateurs d'un groupe (MOD-05 §3.5, RG-USR-026 à RG-USR-032).
 *
 * Toutes les opérations sont tracées dans le journal d'audit « suppression_utilisateurs »
 * avec des identifiants techniques uniquement.
 */
final readonly class SuppressionUtilisateursService
{
    public const int GROUPE_PAR_DEFAUT_ID = 1;

    public const string PRIVILEGE_ADMINISTRATION = 'admin';

    public function __construct(
        private GroupeRepository $groupeRepository,
        private UtilisateurRepository $utilisateurRepository,
        #[Autowire(service: 'monolog.logger.suppression_utilisateurs')]
        private LoggerInterface $auditLogger,
    ) {
    }

    /**
     * @return list<array{id: int, libelle: string, nombreUtilisateurs: int}>
     */
    public function listerGroupes(): array
    {
        return $this->groupeRepository->findAvecNombreUtilisateurs();
    }

    public function journaliserRefusNonInteractif(): void
    {
        $this->auditLogger->warning('Exécution non interactive refusée', ['operateur' => $this->operateur()]);
    }

    /**
     * @throws GroupeProtegeException si le groupe ne peut pas être vidé (RG-USR-027)
     */
    public function analyser(int $groupeId, string $groupeLibelle, bool $simulation): AnalyseSuppressionUtilisateurs
    {
        $executionId = bin2hex(random_bytes(8));
        $contexte = [
            'execution' => $executionId,
            'groupe_id' => $groupeId,
            'groupe_libelle' => $groupeLibelle,
            'simulation' => $simulation,
        ];

        $this->auditLogger->info('Lancement de la suppression définitive des utilisateurs du groupe', $contexte + ['operateur' => $this->operateur()]);

        if ($this->estProtege($groupeId)) {
            $this->auditLogger->warning('Groupe protégé : suppression refusée', $contexte);

            throw new GroupeProtegeException(\sprintf('Le groupe « %s » est protégé : ses utilisateurs ne peuvent pas être supprimés.', $groupeLibelle));
        }

        $ids = $this->utilisateurRepository->findIdsByGroupe($groupeId);
        $conserves = [] === $ids ? [] : $this->utilisateurRepository->findReferencesBloquantes($ids);

        $analyse = new AnalyseSuppressionUtilisateurs($executionId, $groupeId, $groupeLibelle, $simulation, $ids, $conserves);

        $this->auditLogger->info('Analyse du groupe', $contexte + [
            'nombre_utilisateurs' => $analyse->nombreTotal(),
            'nombre_supprimables' => \count($analyse->supprimables()),
            'nombre_conserves' => \count($conserves),
        ]);

        foreach ($conserves as $id => $motifs) {
            $this->journaliserConservation($analyse, $id, $motifs);
        }

        return $analyse;
    }

    public function journaliserAnnulation(AnalyseSuppressionUtilisateurs $analyse): void
    {
        $this->auditLogger->info("Suppression annulée par l'opérateur", $this->contexte($analyse));
    }

    public function executer(AnalyseSuppressionUtilisateurs $analyse): BilanSuppressionUtilisateurs
    {
        $supprimables = $analyse->supprimables();

        if ($analyse->simulation) {
            $this->auditLogger->info('Simulation : utilisateurs qui seraient supprimés', $this->contexte($analyse) + ['utilisateurs' => $supprimables]);

            return $this->terminer($analyse, new BilanSuppressionUtilisateurs(true, $supprimables, $analyse->conserves, [], []));
        }

        // Les références peuvent avoir évolué depuis l'analyse présentée à l'opérateur.
        $nouvellesReferences = [] === $supprimables ? [] : $this->utilisateurRepository->findReferencesBloquantes($supprimables);
        $supprimes = [];
        $fichesConservees = [];
        $conserves = $analyse->conserves;
        $absents = [];
        $erreurs = [];

        foreach ($supprimables as $id) {
            if (\array_key_exists($id, $nouvellesReferences)) {
                $conserves[$id] = $nouvellesReferences[$id];
                $this->journaliserConservation($analyse, $id, $nouvellesReferences[$id]);

                continue;
            }

            try {
                $resultat = $this->utilisateurRepository->supprimerDefinitivement($id, $analyse->groupeId);
            } catch (\Throwable $exception) {
                $erreurs[$id] = $exception->getMessage();
                $this->auditLogger->error("Erreur lors de la suppression de l'utilisateur", $this->contexte($analyse) + [
                    'utilisateur_id' => $id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            if (ResultatSuppressionUtilisateurEnum::Absent === $resultat) {
                $absents[] = $id;
                $this->auditLogger->warning('Utilisateur absent du groupe : non supprimé', $this->contexte($analyse) + ['utilisateur_id' => $id]);

                continue;
            }

            $supprimes[] = $id;
            $ficheConservee = ResultatSuppressionUtilisateurEnum::SupprimeFicheConservee === $resultat;
            if ($ficheConservee) {
                $fichesConservees[] = $id;
            }

            $this->auditLogger->info('Utilisateur supprimé', $this->contexte($analyse) + [
                'utilisateur_id' => $id,
                'fiche_informations_partagee_conservee' => $ficheConservee,
            ]);
        }

        ksort($conserves);

        return $this->terminer($analyse, new BilanSuppressionUtilisateurs(false, $supprimes, $conserves, $absents, $erreurs, $fichesConservees));
    }

    private function estProtege(int $groupeId): bool
    {
        return self::GROUPE_PAR_DEFAUT_ID === $groupeId
            || $this->groupeRepository->possedePrivilege($groupeId, self::PRIVILEGE_ADMINISTRATION);
    }

    private function terminer(AnalyseSuppressionUtilisateurs $analyse, BilanSuppressionUtilisateurs $bilan): BilanSuppressionUtilisateurs
    {
        $this->auditLogger->log(
            $bilan->enErreur() ? LogLevel::ERROR : LogLevel::INFO,
            'Fin de la suppression définitive des utilisateurs du groupe',
            $this->contexte($analyse) + [
                ($bilan->simulation ? 'nombre_simules' : 'nombre_supprimes') => \count($bilan->supprimes),
                'nombre_fiches_partagees_conservees' => \count($bilan->fichesConservees),
                'nombre_conserves' => \count($bilan->conserves),
                'nombre_absents' => \count($bilan->absents),
                'nombre_erreurs' => \count($bilan->erreurs),
            ],
        );

        return $bilan;
    }

    /**
     * @param list<MotifConservationUtilisateurEnum> $motifs
     */
    private function journaliserConservation(AnalyseSuppressionUtilisateurs $analyse, int $id, array $motifs): void
    {
        $this->auditLogger->info('Utilisateur conservé : références métier', $this->contexte($analyse) + [
            'utilisateur_id' => $id,
            'motifs' => array_map(static fn (MotifConservationUtilisateurEnum $motif): string => $motif->value, $motifs),
        ]);
    }

    /**
     * @return array{execution: string, groupe_id: int, groupe_libelle: string, simulation: bool}
     */
    private function contexte(AnalyseSuppressionUtilisateurs $analyse): array
    {
        return [
            'execution' => $analyse->executionId,
            'groupe_id' => $analyse->groupeId,
            'groupe_libelle' => $analyse->groupeLibelle,
            'simulation' => $analyse->simulation,
        ];
    }

    private function operateur(): string
    {
        $operateur = getenv('SUDO_USER') ?: getenv('USER');

        return \is_string($operateur) && '' !== $operateur ? $operateur : get_current_user();
    }
}

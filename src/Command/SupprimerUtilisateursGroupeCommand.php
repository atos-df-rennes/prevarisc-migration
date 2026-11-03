<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\MotifConservationUtilisateurEnum;
use App\Service\SuppressionUtilisateurs\AnalyseSuppressionUtilisateurs;
use App\Service\SuppressionUtilisateurs\BilanSuppressionUtilisateurs;
use App\Service\SuppressionUtilisateurs\GroupeProtegeException;
use App\Service\SuppressionUtilisateurs\SuppressionUtilisateursService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Suppression définitive et interactive des utilisateurs d'un groupe (MOD-05 §3.5).
 */
#[AsCommand(
    name: 'app:utilisateurs:supprimer-groupe',
    description: "Supprime définitivement les utilisateurs d'un groupe, après sélection interactive et confirmation",
)]
final readonly class SupprimerUtilisateursGroupeCommand
{
    public function __construct(private SuppressionUtilisateursService $suppressionUtilisateurs)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        #[Option(description: 'Simule la suppression : analyse et traces sans rien supprimer')]
        bool $dryRun = false,
    ): int {
        if (!$input->isInteractive()) {
            $this->suppressionUtilisateurs->journaliserRefusNonInteractif();
            $io->error('Cette commande doit être exécutée en mode interactif.');

            return Command::FAILURE;
        }

        $groupes = $this->suppressionUtilisateurs->listerGroupes();
        if ([] === $groupes) {
            $io->warning("Aucun groupe d'utilisateurs.");

            return Command::SUCCESS;
        }

        $libelles = [];
        $groupesParId = [];
        foreach ($groupes as $groupe) {
            $libelles[$groupe['id']] = \sprintf('%s (ID %d, %d utilisateur(s))', $groupe['libelle'], $groupe['id'], $groupe['nombreUtilisateurs']);
            $groupesParId[$groupe['id']] = $groupe;
        }

        // Choix indexés par identifiant de groupe : l'exploitant saisit l'ID affiché.
        // Les clés étant entières, la question renvoie le libellé choisi.
        $libelleChoisi = $io->choice('Groupe dont les utilisateurs seront définitivement supprimés', $libelles);
        $groupe = $groupesParId[(int) array_search($libelleChoisi, $libelles, true)];

        try {
            $analyse = $this->suppressionUtilisateurs->analyser($groupe['id'], $groupe['libelle'], $dryRun);
        } catch (GroupeProtegeException $groupeProtegeException) {
            $io->error($groupeProtegeException->getMessage());

            return Command::FAILURE;
        }

        $this->afficherAnalyse($io, $analyse);

        if ($dryRun) {
            $io->note('Mode simulation : aucune suppression ne sera effectuée.');
        } elseif ([] !== $analyse->supprimables()
            && !$io->confirm(\sprintf('Supprimer définitivement %d utilisateur(s) du groupe « %s » ?', \count($analyse->supprimables()), $analyse->groupeLibelle), false)
        ) {
            $this->suppressionUtilisateurs->journaliserAnnulation($analyse);
            $io->warning("Opération annulée : aucun utilisateur n'a été supprimé.");

            return Command::SUCCESS;
        }

        $bilan = $this->suppressionUtilisateurs->executer($analyse);
        $this->afficherBilan($io, $bilan);

        return $bilan->enErreur() ? Command::FAILURE : Command::SUCCESS;
    }

    private function afficherAnalyse(SymfonyStyle $io, AnalyseSuppressionUtilisateurs $analyse): void
    {
        $io->section(\sprintf('Groupe « %s » (ID %d)', $analyse->groupeLibelle, $analyse->groupeId));
        $io->definitionList(
            ['Utilisateurs du groupe' => $analyse->nombreTotal()],
            ['Utilisateurs supprimables' => \count($analyse->supprimables())],
            ['Utilisateurs conservés (références métier)' => \count($analyse->conserves)],
        );
        $this->afficherConserves($io, $analyse->conserves);
    }

    private function afficherBilan(SymfonyStyle $io, BilanSuppressionUtilisateurs $bilan): void
    {
        $lignes = [[($bilan->simulation ? 'Utilisateurs qui seraient supprimés' : 'Utilisateurs supprimés') => \count($bilan->supprimes)]];
        if (!$bilan->simulation) {
            $lignes[] = ["dont fiche d'informations partagée conservée" => \count($bilan->fichesConservees)];
        }

        $lignes[] = ['Utilisateurs conservés' => \count($bilan->conserves)];
        $lignes[] = ['Utilisateurs absents du groupe' => \count($bilan->absents)];
        $lignes[] = ['Erreurs' => \count($bilan->erreurs)];

        $io->section('Bilan');
        $io->definitionList(...$lignes);

        if ($bilan->enErreur()) {
            $io->table(['ID utilisateur', 'Erreur'], array_map(
                static fn (int $id, string $message): array => [$id, $message],
                array_keys($bilan->erreurs),
                $bilan->erreurs,
            ));
            $io->error("Des erreurs sont survenues : les utilisateurs concernés n'ont pas été supprimés.");

            return;
        }

        $io->success($bilan->simulation ? 'Simulation terminée.' : 'Suppression terminée.');
    }

    /**
     * @param array<int, list<MotifConservationUtilisateurEnum>> $conserves
     */
    private function afficherConserves(SymfonyStyle $io, array $conserves): void
    {
        if ([] === $conserves) {
            return;
        }

        $io->table(['ID utilisateur', 'Motifs de conservation'], array_map(
            static fn (int $id, array $motifs): array => [
                $id,
                implode(', ', array_map(static fn (MotifConservationUtilisateurEnum $motif): string => $motif->libelle(), $motifs)),
            ],
            array_keys($conserves),
            $conserves,
        ));
    }
}

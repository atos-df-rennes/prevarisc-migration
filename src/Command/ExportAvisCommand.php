<?php

declare(strict_types=1);

namespace App\Command;

use App\Platau\AvisApi;
use App\Platau\AvisDossiers;
use App\Platau\ExportDocuments;
use App\Platau\PlatauException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'platau:export-avis', description: "Exporte les avis de commission sur Plat'AU.", aliases: ['export-avis'])]
final readonly class ExportAvisCommand
{
    public function __construct(private AvisApi $api, private AvisDossiers $dossiers, private ExportDocuments $documents)
    {
    }

    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Consultation concernée')] ?string $consultationId = null,
    ): int {
        if (null !== $consultationId && '' === trim($consultationId)) {
            throw new \InvalidArgumentException("L'identifiant de consultation ne peut pas être vide.");
        }
        $report = static function (string $message) use ($output): void {
            $output->writeln(sprintf('[%s] %s', date('d-m-Y H:i:s'), $message), OutputInterface::OUTPUT_RAW);
        };

        foreach ($this->candidates($consultationId, $report) as $candidate) {
            $id = $candidate['id'];
            $dossier = $this->dossiers->find($id);
            if (null === $dossier) {
                $report(sprintf("La consultation %s n'existe pas dans Prevarisc.", $id));
                continue;
            }
            if (!AvisApi::eligible($candidate['state'], $dossier['status'])) {
                $report(sprintf("Consultation %s : état incompatible avec l'envoi.", $id));
                continue;
            }
            if (!in_array($dossier['opinion'], [1, 2, 6], true)) {
                $report(sprintf('Consultation %s : en attente d’un avis de commission transmissible.', $id));
                continue;
            }

            $labels = $this->dossiers->prescriptions($dossier['id']);
            $prepared = [];
            try {
                $target = $this->api->refreshTarget($id, $candidate['state']);
                if (null === $target) {
                    $report(sprintf('Consultation %s : état modifié depuis la découverte, envoi ignoré.', $id));
                    continue;
                }
                $opinion = $this->api->opinion($id, $dossier['opinion'], $labels, $dossier['author'], $dossier['status'], $dossier['date']);
                $prepared = $this->documents->prepare($dossier['id'], $report, 9);
                $remote = $this->api->send($target, $opinion, $prepared);
            } catch (\Exception $exception) {
                $this->dossiers->failed($id);
                $this->documents->failed($prepared);
                $report(sprintf("Échec du versement de l'avis pour %s : %s", $id, $exception->getMessage()));
                continue;
            }

            $this->dossiers->accepted($id);
            $this->documents->accepted($prepared, $remote, $report);
            $report(sprintf('Avis envoyé pour la consultation %s.', $id));
        }

        return Command::SUCCESS;
    }

    /**
     * @param callable(string): void $report
     *
     * @return list<array{id: string, state: int}>
     */
    private function candidates(?string $consultationId, callable $report): array
    {
        // Terminer la découverte avant toute écriture. Une panne de lecture du lot ne vaut pas refus individuel.
        $candidates = [];
        foreach ($this->api->candidates($consultationId, [3]) as $candidate) {
            $candidates[$candidate['id']] = $candidate;
        }
        if (null === $consultationId) {
            foreach ($this->dossiers->retryIds() as $id) {
                if (isset($candidates[$id])) {
                    continue;
                }
                try {
                    foreach ($this->api->candidates($id) as $candidate) {
                        $candidates[$candidate['id']] = $candidate;
                    }
                } catch (PlatauException $exception) {
                    // Une reprise locale introuvable n'est pas un refus de versement.
                    $report(sprintf('Consultation %s : reprise ignorée (%s).', $id, $exception->getMessage()));
                }
            }
        }

        return array_values($candidates);
    }
}

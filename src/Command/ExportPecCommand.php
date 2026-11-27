<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\PlatauStatutPecEnum;
use App\Platau\ExportDocuments;
use App\Platau\PecApi;
use App\Platau\PecDossiers;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'platau:export-pec', description: "Exporte des prises en compte métier sur Plat'AU.", aliases: ['export-pec'])]
final readonly class ExportPecCommand
{
    public function __construct(private PecApi $api, private PecDossiers $dossiers, private ExportDocuments $documents)
    {
    }

    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Consultation concernée')] ?string $consultationId = null,
        #[Option(description: 'Délai de réponse en jours, en remplacement du délai de la consultation')] ?string $delaiReponse = null,
    ): int {
        $delay = null === $delaiReponse || '0' === $delaiReponse ? null : filter_var($delaiReponse, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (false === $delay) {
            throw new \InvalidArgumentException('Le délai de réponse doit être un nombre entier de jours strictement positif.');
        }

        if (null !== $consultationId && '' === trim($consultationId)) {
            throw new \InvalidArgumentException("L'identifiant de consultation ne peut pas être vide.");
        }

        $report = static function (string $message) use ($output): void {
            $output->writeln(sprintf('[%s] %s', date('d-m-Y H:i:s'), $message), OutputInterface::OUTPUT_RAW);
        };
        $report(null === $consultationId ? 'Recherche des consultations versées ou refusées ...' : 'Récupération de la consultation concernée ...');
        foreach ($this->api->candidates($consultationId) as $candidate) {
            $id = $candidate['id'];
            $dossier = $this->dossiers->find($id);
            if (null === $dossier) {
                $report(sprintf("La consultation %s n'existe pas dans Prevarisc.", $id));

                continue;
            }

            if (2 === $candidate['state'] && !in_array($dossier['status'], [PlatauStatutPecEnum::A_RENVOYER, PlatauStatutPecEnum::EN_ERREUR], true)) {
                continue;
            }

            if (null === $dossier['incomplete']) {
                $report(sprintf('Consultation %s : en attente de complétude sur Prevarisc.', $id));

                continue;
            }

            $prepared = [];
            try {
                $payload = $this->api->payload($id, !$dossier['incomplete'], $dossier['incomplete'] ? $dossier['observations'] : null, $dossier['author'], PlatauStatutPecEnum::A_RENVOYER === $dossier['status'] ? $dossier['date'] : null, $delay);
                $prepared = $this->documents->prepare($dossier['id'], $report, 47);
                $remote = $this->api->send($payload, $prepared);
            } catch (\Exception $exception) {
                $this->dossiers->failed($id);
                $this->documents->failed($prepared);
                $report(sprintf('Problème lors du traitement de la consultation %s : %s', $id, $exception->getMessage()));

                continue;
            }

            // La PEC est acceptée : un échec de rapprochement local ne doit pas provoquer un nouvel envoi.
            $this->dossiers->accepted($id);
            $this->documents->accepted($prepared, $remote, $report);
            $report(sprintf('Prise en compte %s envoyée pour la consultation %s.', $dossier['incomplete'] ? 'négative' : 'positive', $id));
        }

        return Command::SUCCESS;
    }
}

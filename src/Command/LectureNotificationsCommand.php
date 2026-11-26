<?php

declare(strict_types=1);

namespace App\Command;

use App\Platau\ConsultationImporter;
use App\Platau\HttpNotificationReader;
use App\Platau\ReceptionReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'platau:lecture-notifications',
    description: "Lit les notifications qui n'ont pas encore été consommées.",
    aliases: ['lecture-notifications'],
)]
final readonly class LectureNotificationsCommand
{
    public function __construct(
        private HttpNotificationReader $notifications,
        private ReceptionReader $reception,
        private ConsultationImporter $importer,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Offset à partir duquel on récupère les notifications')]
        ?string $offset = null,
    ): int {
        $cursor = null === $offset ? null : filter_var($offset, \FILTER_VALIDATE_INT);
        if (false === $cursor) {
            throw new \InvalidArgumentException(sprintf("La valeur de l'option offset doit être un entier. \"%s\" donné.", $offset));
        }

        $this->importer->requireCreator();
        $this->log($output, 'Lecture des nouvelles notifications ...');
        // Sans offset, la lecture consomme le lot pour l'acteur.
        $batch = $this->notifications->batch($cursor);

        if ([] === $batch) {
            $this->log($output, 'Aucune nouvelle notification.');
        }

        foreach ($batch as $row) {
            $this->process($row, $output);
        }

        return Command::SUCCESS;
    }

    private function process(mixed $row, OutputInterface $output): void
    {
        $offset = is_array($row) && is_int($row['offset'] ?? null) ? (string) $row['offset'] : 'inconnu';
        $prefix = sprintf('[Offset: %s] ', $offset);
        if (!is_array($row) || !is_int($row['idTypeObjetMetier'] ?? null) || !is_int($row['idTypeEvenement'] ?? null)) {
            $this->log($output, $prefix.'Notification incorrecte.');

            return;
        }

        $id = $row['idElementConcerne'] ?? null;
        if (!is_string($id) || '' === trim($id)) {
            $message = 6 === $row['idTypeObjetMetier'] && 19 === $row['idTypeEvenement']
                ? 'Identifiant de consultation manquant ou incorrect.' : 'Notification incorrecte.';
            $this->log($output, $prefix.$message);

            return;
        }

        try {
            $objet = $this->notifications->nomenclature('TYPE_OBJET_METIER', $row['idTypeObjetMetier']);
            $evenement = $this->notifications->nomenclature('TYPE_EVENEMENT', $row['idTypeEvenement']);
        } catch (\Throwable $throwable) {
            $this->log($output, $prefix.$throwable->getMessage());

            return;
        }

        if (6 !== $row['idTypeObjetMetier'] || 19 !== $row['idTypeEvenement']) {
            $libelle = in_array($row['idTypeObjetMetier'], [5, 6, 31], true) ? $objet.' - '.$evenement : $objet;
            $this->log($output, sprintf("La notification %s pour l'élément d'identifiant %s n'est pas prise en compte par la passerelle actuellement", $libelle, $id));

            return;
        }

        $this->log($output, sprintf("Traitement de la notification %s - %s pour l'élément d'identifiant %s", $objet, $evenement, $id));
        try {
            if ($this->importer->exists($id)) {
                $this->log($output, sprintf('Consultation %s déjà existante dans Prevarisc', $id));

                return;
            }

            $dossier = $this->reception->dossier($id);

            $serviceInstructeur = $this->notifications->actorDesignation($dossier->idServiceInstructeur);
            $serviceConsultant = $this->notifications->actorDesignation($dossier->consultation->idServiceConsultant);
            $this->importer->import($dossier, $serviceConsultant, $serviceInstructeur);

            $this->importer->markAwaiting($id);
            $this->log($output, sprintf('Consultation %s récupérée et stockée dans Prevarisc !', $id));
        } catch (\Throwable $throwable) {
            $this->log($output, $prefix.'Problème lors du traitement de la consultation : '.$throwable->getMessage());
        }
    }

    private function log(OutputInterface $output, string $message): void
    {
        $output->writeln(sprintf('[%s] %s', new \DateTimeImmutable()->format('d-m-Y H:i:s'), $message), OutputInterface::OUTPUT_RAW);
    }
}

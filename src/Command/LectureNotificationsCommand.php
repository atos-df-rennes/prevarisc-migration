<?php

declare(strict_types=1);

namespace App\Command;

use App\Platau\ConsultationImporter;
use App\Platau\HttpNotificationReader;
use App\Platau\PieceReception;
use App\Platau\PlatauException;
use App\Platau\ReceptionException;
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
        private PieceReception $pieces,
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

        $pieceNotifiee = 5 === $row['idTypeObjetMetier'] && in_array($row['idTypeEvenement'], [15, 17, 71], true);
        if (!$pieceNotifiee && (6 !== $row['idTypeObjetMetier'] || 19 !== $row['idTypeEvenement'])) {
            $libelle = in_array($row['idTypeObjetMetier'], [5, 6, 31], true) ? $objet.' - '.$evenement : $objet;
            $this->log($output, sprintf("La notification %s pour l'élément d'identifiant %s n'est pas prise en compte par la passerelle actuellement", $libelle, $id));

            return;
        }

        $this->log($output, sprintf("Traitement de la notification %s - %s pour l'élément d'identifiant %s", $objet, $evenement, $id));
        $idDossier = $row['idDossier'] ?? null;
        if ($pieceNotifiee) {
            try {
                $this->pieces->notified($this->dossierId($idDossier), $id);
            } catch (\Throwable $throwable) {
                $this->pieceError($output, $prefix, $throwable);
            }

            return;
        }

        try {
            if ($this->importer->exists($id)) {
                $this->log($output, sprintf('Consultation %s déjà existante dans Prevarisc', $id));

                return;
            }

            $dossier = $this->reception->dossier($id);

            $serviceInstructeur = $this->notifications->actorDesignation($dossier->idServiceInstructeur);
            $serviceConsultant = $this->notifications->actorDesignation($dossier->consultation->idServiceConsultant);
            $localId = $this->importer->import($dossier, $serviceConsultant, $serviceInstructeur);

            $this->importer->markAwaiting($id);
            $this->log($output, sprintf('Consultation %s récupérée et stockée dans Prevarisc !', $id));
        } catch (\Throwable $throwable) {
            $this->log($output, $prefix.'Problème lors du traitement de la consultation : '.$throwable->getMessage());

            return;
        }

        try {
            $idDossier = $this->dossierId($idDossier);
            if ('' !== trim($dossier->idDossier ?? '') && $dossier->idDossier !== $idDossier) {
                throw new ReceptionException('Le dossier de la consultation ne correspond pas à celui de la notification.');
            }

            foreach ($this->pieces->initial($idDossier, $localId) as $diagnostic) {
                $this->log($output, $prefix.$diagnostic);
            }
        } catch (\Throwable $throwable) {
            $this->pieceError($output, $prefix, $throwable);
        }
    }

    private function dossierId(mixed $id): string
    {
        if (!is_string($id) || '' === trim($id) || in_array($id, ['.', '..'], true)) {
            throw new ReceptionException('Identifiant de dossier manquant ou incorrect.');
        }

        return $id;
    }

    private function pieceError(OutputInterface $output, string $prefix, \Throwable $exception): void
    {
        $diagnostic = $exception instanceof ReceptionException || $exception instanceof PlatauException ? $exception->getMessage() : 'import impossible.';
        $this->log($output, $prefix.'Problème lors du traitement des pièces : '.$diagnostic);
    }

    private function log(OutputInterface $output, string $message): void
    {
        $output->writeln(sprintf('[%s] %s', new \DateTimeImmutable()->format('d-m-Y H:i:s'), $message), OutputInterface::OUTPUT_RAW);
    }
}

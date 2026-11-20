<?php

declare(strict_types=1);

namespace App\Command;

use App\Platau\ConsultationImporter;
use App\Platau\ConsultationReader;
use App\Platau\HttpNotificationReader;
use App\Platau\PlatauException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'platau:lecture-notifications',
    description: 'Lit un seul lot de notifications Plat’AU et importe les consultations versées en recette.',
    aliases: ['lecture-notifications'],
    help: 'Réserver cette commande à un acteur et une base isolés. Sans --offset, lire les notifications non consommées. '
        .'Seules les consultations versées (6/19) sont importées, sans pièces. Une consultation déjà présente est laissée intacte. '
        .'Une erreur individuelle est signalée avec son offset sans interrompre le lot ni changer le code de sortie.',
)]
final readonly class LectureNotificationsCommand
{
    public function __construct(
        private HttpNotificationReader $notifications,
        private ConsultationReader $consultations,
        private ConsultationImporter $importer,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Confirme l’utilisation d’un acteur et d’une base isolés pour la recette.')]
        bool $recette = false,
        #[Option(description: 'Offset à partir duquel lire les notifications.')]
        ?string $offset = null,
    ): int {
        $errors = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        if (!$recette) {
            $errors->writeln('Lecture refusée : --recette est obligatoire. Utilisez un acteur Plat’AU et une base Prevarisc isolés.');

            return Command::FAILURE;
        }

        $cursor = null === $offset ? null : filter_var($offset, \FILTER_VALIDATE_INT);
        if (false === $cursor) {
            $errors->writeln("La valeur de l'option offset doit être un entier.");

            return Command::FAILURE;
        }

        $output->writeln('Lecture des nouvelles notifications ...');
        try {
            // Sans offset, la lecture consomme le lot pour l'acteur.
            $this->importer->requireCreator();
            $batch = $this->notifications->batch($cursor);
        } catch (PlatauException $exception) {
            $errors->writeln($exception->getMessage(), OutputInterface::OUTPUT_RAW);

            return Command::FAILURE;
        } catch (\Throwable) {
            $errors->writeln('Échec de la lecture du lot de notifications.');

            return Command::FAILURE;
        }

        if ([] === $batch) {
            $output->writeln('Aucune nouvelle notification.');
        }

        foreach ($batch as $row) {
            $this->process($row, $output, $errors);
        }

        return Command::SUCCESS;
    }

    private function process(mixed $row, OutputInterface $output, OutputInterface $errors): void
    {
        $offset = is_array($row) && is_int($row['offset'] ?? null) ? (string) $row['offset'] : 'inconnu';
        $prefix = sprintf('[Offset: %s] ', $offset);
        if (!is_array($row) || !is_int($row['idTypeObjetMetier'] ?? null) || !is_int($row['idTypeEvenement'] ?? null)) {
            $errors->writeln($prefix.'Notification incorrecte.');

            return;
        }

        if (6 !== $row['idTypeObjetMetier'] || 19 !== $row['idTypeEvenement']) {
            $output->writeln($prefix.'Notification non prise en compte.');

            return;
        }

        $id = $row['idElementConcerne'] ?? null;
        if (!is_string($id) || '' === trim($id)) {
            $errors->writeln($prefix.'Identifiant de consultation manquant ou incorrect.');

            return;
        }

        $stage = 'vérification du doublon';
        try {
            if ($this->importer->exists($id)) {
                $output->writeln($prefix.'Consultation déjà existante dans Prevarisc.');

                return;
            }

            $stage = 'lecture de la consultation';
            $information = $this->consultations->details($id);
            $dossier = $information['dossier'] ?? null;
            $consultations = is_array($dossier) ? ($dossier['consultations'] ?? null) : null;
            $consultation = is_array($consultations) ? ($consultations[0] ?? null) : null;
            if (!is_array($dossier) || !is_array($consultation) || $id !== ($consultation['idConsultation'] ?? null)) {
                $errors->writeln($prefix.'La consultation renvoyée ne correspond pas à la notification.');

                return;
            }

            $stage = 'recherche des acteurs';
            $instructeur = $dossier['idServiceInstructeur'] ?? null;
            $consultant = $consultation['idServiceConsultant'] ?? null;
            if ((null !== $instructeur && !is_string($instructeur)) || (null !== $consultant && !is_string($consultant))) {
                $errors->writeln($prefix.'Identifiant de service incorrect.');

                return;
            }

            $serviceInstructeur = $this->notifications->actorDesignation($instructeur);
            $serviceConsultant = $this->notifications->actorDesignation($consultant);
            $stage = 'import de la consultation';
            $this->importer->import($information, $serviceConsultant, $serviceInstructeur);

            $stage = 'mise en attente de la PEC';
            $this->importer->markAwaiting($id);
            $output->writeln($prefix.'Consultation récupérée et stockée dans Prevarisc.');
        } catch (PlatauException $exception) {
            $errors->writeln($prefix.sprintf('Échec (%s) : %s', $stage, $exception->getMessage()), OutputInterface::OUTPUT_RAW);
        } catch (\Throwable) {
            $errors->writeln($prefix.'Échec du traitement : '.$stage.'.');
        }
    }
}

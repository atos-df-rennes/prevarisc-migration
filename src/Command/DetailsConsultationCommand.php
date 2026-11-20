<?php

declare(strict_types=1);

namespace App\Command;

use App\Platau\ConsultationReader;
use App\Platau\PlatauException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'platau:details-consultation',
    description: "Affiche les détails d'une consultation.",
    aliases: ['details-consultation'],
)]
final readonly class DetailsConsultationCommand
{
    public function __construct(private ConsultationReader $reader)
    {
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument(description: 'Consultation concernée', name: 'consultation-id')]
        string $consultationId,
        #[Option(description: 'Retourne un champ spécifique')]
        string|bool $champ = false,
    ): int {
        $output->writeln('Récupération de la consultation concernée ...');
        try {
            $details = $this->reader->details($consultationId);
        } catch (\Exception $exception) {
            $erreur = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
            $diagnostic = $exception instanceof PlatauException
                ? $exception->getMessage()
                : 'Échec de la lecture de la consultation Plat’AU.';
            $erreur->writeln($diagnostic, OutputInterface::OUTPUT_RAW);

            return Command::FAILURE;
        }

        $champs = \is_string($champ) && '' !== $champ
            ? [$champ => $this->lireChamp($details, $champ)]
            : $this->aplatir($details);

        foreach ($champs as $chemin => $valeur) {
            $texte = \is_array($valeur) ? 'Array' : (\is_scalar($valeur) ? (string) $valeur : '');
            $output->writeln($chemin.' : '.$texte, OutputInterface::OUTPUT_RAW);
        }

        return Command::SUCCESS;
    }

    /** @param array<array-key, mixed> $details */
    private function lireChamp(array $details, string $chemin): mixed
    {
        // Comme Dot::get(), une clé littérale à la racine prime sur le chemin imbriqué.
        if (\array_key_exists($chemin, $details)) {
            return $details[$chemin];
        }

        $valeur = $details;
        foreach (explode('.', $chemin) as $segment) {
            if (!\is_array($valeur) || !\array_key_exists($segment, $valeur)) {
                return 'Aucune donnée';
            }

            $valeur = $valeur[$segment];
        }

        return $valeur;
    }

    /**
     * @param array<array-key, mixed> $details
     *
     * @return array<array-key, mixed>
     */
    private function aplatir(array $details, string $prefixe = ''): array
    {
        $champs = [];
        foreach ($details as $cle => $valeur) {
            $chemin = $prefixe.$cle;
            $champs[] = \is_array($valeur) && [] !== $valeur
                ? $this->aplatir($valeur, $chemin.'.')
                : [$chemin => $valeur];
        }

        return array_merge(...$champs);
    }
}

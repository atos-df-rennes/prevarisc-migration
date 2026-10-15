<?php

declare(strict_types=1);

namespace App\Service\SuppressionUtilisateurs;

use App\Enum\MotifConservationUtilisateurEnum;

/**
 * Résultat de l'analyse d'un groupe avant suppression définitive de ses utilisateurs.
 */
final readonly class AnalyseSuppressionUtilisateurs
{
    /**
     * @param list<int>                                          $idsUtilisateurs tous les utilisateurs du groupe
     * @param array<int, list<MotifConservationUtilisateurEnum>> $conserves       utilisateurs référencés, indexés par identifiant
     */
    public function __construct(
        public string $executionId,
        public int $groupeId,
        public string $groupeLibelle,
        public bool $simulation,
        public array $idsUtilisateurs,
        public array $conserves,
    ) {
    }

    public function nombreTotal(): int
    {
        return \count($this->idsUtilisateurs);
    }

    /**
     * @return list<int>
     */
    public function supprimables(): array
    {
        return array_values(array_filter(
            $this->idsUtilisateurs,
            fn (int $id): bool => !\array_key_exists($id, $this->conserves),
        ));
    }
}

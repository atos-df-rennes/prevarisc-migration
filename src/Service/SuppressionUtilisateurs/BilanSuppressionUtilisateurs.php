<?php

declare(strict_types=1);

namespace App\Service\SuppressionUtilisateurs;

use App\Enum\MotifConservationUtilisateurEnum;

/**
 * Bilan d'une exécution de la suppression définitive des utilisateurs d'un groupe.
 */
final readonly class BilanSuppressionUtilisateurs
{
    /**
     * @param list<int>                                          $supprimes        utilisateurs supprimés (ou qui l'auraient été en simulation)
     * @param array<int, list<MotifConservationUtilisateurEnum>> $conserves        utilisateurs conservés car référencés
     * @param list<int>                                          $absents          utilisateurs qui n'appartiennent plus au groupe au moment de la suppression
     * @param array<int, string>                                 $erreurs          messages d'erreur indexés par identifiant d'utilisateur
     * @param list<int>                                          $fichesConservees utilisateurs supprimés dont la fiche d'informations partagée a été conservée
     */
    public function __construct(
        public bool $simulation,
        public array $supprimes,
        public array $conserves,
        public array $absents,
        public array $erreurs,
        public array $fichesConservees = [],
    ) {
    }

    public function enErreur(): bool
    {
        return [] !== $this->erreurs;
    }
}

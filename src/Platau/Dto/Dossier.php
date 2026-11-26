<?php

declare(strict_types=1);

namespace App\Platau\Dto;

use Symfony\Component\Serializer\Attribute\SerializedPath;

final readonly class Dossier
{
    /** @param Personne[]|null $personnes */
    public function __construct(
        #[SerializedPath('[consultations][0]')]
        public Consultation $consultation,
        public ?string $idDossier = null,
        public ?string $idServiceInstructeur = null,
        public ?string $txDescriptifGlobal = null,
        public ?string $noLocal = null,
        public ?string $suffixeNoLocal = null,
        public ?Nomenclature $nomTypeDossier = null,
        private ?array $personnes = null,
    ) {
    }

    public function demandeurs(): ?string
    {
        $noms = [];
        foreach ($this->personnes ?? [] as $personne) {
            if ($personne->estPetitionnaire()) {
                $noms[] = $personne->nomComplet();
            }
        }

        return [] === $noms ? null : implode(' / ', $noms);
    }
}

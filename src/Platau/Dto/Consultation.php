<?php

declare(strict_types=1);

namespace App\Platau\Dto;

final readonly class Consultation
{
    public function __construct(
        public string $idConsultation,
        public ?string $dtEmission = null,
        public ?string $dtConsultation = null,
        public ?string $txObjetDeLaConsultation = null,
        public ?int $delaiDeReponse = null,
        public ?Nomenclature $nomTypeDelai = null,
        public ?Nomenclature $nomTypeConsultation = null,
        public ?string $idServiceConsultant = null,
    ) {
    }

    public function dateReponseAttendue(): ?string
    {
        if (null === $this->dtEmission || null === $this->delaiDeReponse) {
            return null;
        }

        $interval = match ($this->nomTypeDelai?->libNom) {
            'Jours calendaires' => new \DateInterval("P{$this->delaiDeReponse}D"),
            'Mois' => new \DateInterval("P{$this->delaiDeReponse}M"),
            default => null,
        };
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $this->dtEmission);

        return !$interval instanceof \DateInterval || false === $date ? null : $date->add($interval)->format('Y-m-d');
    }
}

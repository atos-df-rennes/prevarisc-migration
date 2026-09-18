<?php

namespace App\Dto\Commission;

use App\Entity\CommissionTypeEvenement;
use App\Entity\DateCommission;

/**
 * Value Object représentant un événement de calendrier pour FullCalendar.
 * Immutable avec validation des données requises.
 */
final readonly class CalendarEventDto
{
    private function __construct(private int $id, private string $title, private string $start, private string $end, private string $url, private string $className, private bool $allDay)
    {
    }

    /**
     * Crée un CalendarEventDto à partir d'une entité DateCommission.
     *
     * @throws \InvalidArgumentException si les données requises sont manquantes
     */
    public static function fromEntity(DateCommission $dateCommission): self
    {
        $id = $dateCommission->getId();
        if (null === $id) {
            throw new \InvalidArgumentException("L'identifiant de la date de commission est requis.");
        }

        $date = $dateCommission->getDate();
        if (null === $date) {
            throw new \InvalidArgumentException('La date de commission est requise.');
        }

        $heureDebut = $dateCommission->getHeureDebut();
        if (null === $heureDebut) {
            throw new \InvalidArgumentException('L\'heure de début de commission est requise.');
        }

        $heureFin = $dateCommission->getHeureFin();
        if (null === $heureFin) {
            throw new \InvalidArgumentException("L'heure de fin de commission est requise.");
        }

        $typeEvenement = $dateCommission->getTypeEvenement();
        if (null === $typeEvenement) {
            throw new \InvalidArgumentException('Le type d\'événement de commission est requis.');
        }

        $dateStr = $date->format('Y-m-d');
        $heureDebutStr = $heureDebut->format('H:i:s');
        $heureFinStr = $heureFin->format('H:i:s');

        return new self(
            $id,
            '   '.($dateCommission->getLibelle() ?? ''),
            $dateStr.' '.$heureDebutStr,
            $dateStr.' '.$heureFinStr,
            'commission/id/'.$id,
            self::buildClassName($typeEvenement),
            false
        );
    }

    /**
     * Construit la classe CSS pour le type d'événement.
     */
    private static function buildClassName(CommissionTypeEvenement $typeEvenement): string
    {
        return 'display-'.$typeEvenement->getId();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStart(): string
    {
        return $this->start;
    }

    public function getEnd(): string
    {
        return $this->end;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    /**
     * Convertit le Value Object en tableau pour la réponse JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start,
            'end' => $this->end,
            'url' => $this->url,
            'className' => $this->className,
            'allDay' => $this->allDay,
        ];
    }
}

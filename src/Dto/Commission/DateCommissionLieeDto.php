<?php

namespace App\Dto\Commission;

use App\Entity\DateCommission;

/**
 * DTO pour les dates de commission liées.
 * Contient uniquement les informations basiques nécessaires.
 */
final class DateCommissionLieeDto
{
    private int $id;

    private string $dateCommission;

    private string $heureDebut;

    private string $heureFin;

    private ?string $libelle;

    private int $idCommission;

    private int $idTypeEvenement;

    private function __construct(
        int $id,
        string $dateCommission,
        string $heureDebut,
        string $heureFin,
        ?string $libelle,
        int $idCommission,
        int $idTypeEvenement
    ) {
        $this->id = $id;
        $this->dateCommission = $dateCommission;
        $this->heureDebut = $heureDebut;
        $this->heureFin = $heureFin;
        $this->libelle = $libelle;
        $this->idCommission = $idCommission;
        $this->idTypeEvenement = $idTypeEvenement;
    }

    /**
     * Crée un DTO à partir d'une entité DateCommission.
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
            throw new \InvalidArgumentException('L\'heure de début est requise.');
        }

        $heureFin = $dateCommission->getHeureFin();
        if (null === $heureFin) {
            throw new \InvalidArgumentException("L'heure de fin est requise.");
        }

        $commission = $dateCommission->getCommission();
        if (null === $commission || null === $commission->getId()) {
            throw new \InvalidArgumentException('La commission est requise.');
        }

        $typeEvenement = $dateCommission->getTypeEvenement();
        if (null === $typeEvenement || null === $typeEvenement->getId()) {
            throw new \InvalidArgumentException('Le type d\'événement est requis.');
        }

        return new self(
            $id,
            $date->format('Y-m-d'),
            $heureDebut->format('H:i:s'),
            $heureFin->format('H:i:s'),
            $dateCommission->getLibelle(),
            $commission->getId(),
            $typeEvenement->getId()
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDateCommission(): string
    {
        return $this->dateCommission;
    }

    public function getHeureDebut(): string
    {
        return $this->heureDebut;
    }

    public function getHeureFin(): string
    {
        return $this->heureFin;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function getIdCommission(): int
    {
        return $this->idCommission;
    }

    public function getIdTypeEvenement(): int
    {
        return $this->idTypeEvenement;
    }

    /**
     * Convertit le DTO en tableau pour la réponse JSON.
     * Compatible avec le format legacy attendu par le JS.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ID_DATECOMMISSION' => $this->id,
            'DATE_COMMISSION' => $this->dateCommission,
            'HEUREDEB_COMMISSION' => $this->heureDebut,
            'HEUREFIN_COMMISSION' => $this->heureFin,
            'LIBELLE_DATECOMMISSION' => $this->libelle,
            'COMMISSION_CONCERNE' => $this->idCommission,
            'ID_COMMISSIONTYPEEVENEMENT' => $this->idTypeEvenement,
        ];
    }
}

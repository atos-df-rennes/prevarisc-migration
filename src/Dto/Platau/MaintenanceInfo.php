<?php

namespace App\Dto\Platau;

/**
 * Informations de maintenance retournées par le healthcheck Plat'AU.
 * Couvre la maintenance en cours ET la prochaine maintenance programmée.
 */
final class MaintenanceInfo implements \JsonSerializable
{
    private bool $active;

    private ?string $typeMaintenance;

    private ?string $descMaintenance;

    private ?string $prochaineMaintenance;

    private ?string $descProchaineMaintenance;

    public function __construct(
        bool $active,
        ?string $typeMaintenance,
        ?string $descMaintenance,
        ?string $prochaineMaintenance,
        ?string $descProchaineMaintenance
    ) {
        $this->active = $active;
        $this->typeMaintenance = $typeMaintenance;
        $this->descMaintenance = $descMaintenance;
        $this->prochaineMaintenance = $prochaineMaintenance;
        $this->descProchaineMaintenance = $descProchaineMaintenance;
    }

    /**
     * Construit un MaintenanceInfo depuis la réponse brute de l'API Plat'AU.
     * Le champ `maintenance` est nullable dans l'API : null est traité comme false.
     * Les chaînes vides sont normalisées en null.
     * La date `prochaineMaintenance` est formatée côté serveur.
     */
    public static function fromApiResponse(\stdClass $response): self
    {
        $active = true === ($response->maintenance ?? null);

        $prochaineMaintenance = null;
        $rawDate = $response->prochaineMaintenance ?? null;
        if (is_string($rawDate) && '' !== trim($rawDate)) {
            try {
                $date = new \DateTime($rawDate);
                $prochaineMaintenance = $date->format('d/m/Y à H\hi');
            } catch (\Exception $e) {
                $prochaineMaintenance = $rawDate;
            }
        }

        return new self(
            $active,
            PlatauDtoHelper::normalizeString($response->typeMaintenance ?? null),
            PlatauDtoHelper::normalizeString($response->descMaintenance ?? null),
            $prochaineMaintenance,
            PlatauDtoHelper::normalizeString($response->descProchaineMaintenance ?? null)
        );
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getTypeMaintenance(): ?string
    {
        return $this->typeMaintenance;
    }

    public function getDescMaintenance(): ?string
    {
        return $this->descMaintenance;
    }

    public function getProchaineMaintenance(): ?string
    {
        return $this->prochaineMaintenance;
    }

    public function getDescProchaineMaintenance(): ?string
    {
        return $this->descProchaineMaintenance;
    }

    /**
     * Indique s'il y a une information de maintenance à afficher
     * (maintenance active ou prochaine maintenance planifiée).
     */
    public function hasAnyMaintenanceInfo(): bool
    {
        return $this->active || null !== $this->prochaineMaintenance;
    }

    /**
     * Construit le message HTML d'affichage pour le toast de maintenance.
     * Le contenu dynamique est échappé pour prévenir les injections XSS.
     */
    public function toDisplayMessage(): string
    {
        $parts = [];

        if ($this->active) {
            $line = '<strong>Maintenance en cours</strong>';
            if (null !== $this->typeMaintenance) {
                $line .= ' <small>('.htmlspecialchars($this->typeMaintenance, ENT_QUOTES, 'UTF-8').')</small>';
            }

            $parts[] = $line;
            if (null !== $this->descMaintenance) {
                $parts[] = htmlspecialchars($this->descMaintenance, ENT_QUOTES, 'UTF-8');
            }
        }

        if (null !== $this->prochaineMaintenance) {
            $parts[] = '<strong>Prochaine maintenance</strong> : '.htmlspecialchars($this->prochaineMaintenance, ENT_QUOTES, 'UTF-8');
            if (null !== $this->descProchaineMaintenance) {
                $parts[] = htmlspecialchars($this->descProchaineMaintenance, ENT_QUOTES, 'UTF-8');
            }
        }

        return implode('<br>', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'active' => $this->active,
            'typeMaintenance' => $this->typeMaintenance,
            'descMaintenance' => $this->descMaintenance,
            'prochaineMaintenance' => $this->prochaineMaintenance,
            'descProchaineMaintenance' => $this->descProchaineMaintenance,
            'message' => $this->toDisplayMessage(),
        ];
    }
}

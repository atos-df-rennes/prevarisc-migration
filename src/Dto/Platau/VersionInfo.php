<?php

namespace App\Dto\Platau;

/**
 * Informations de version retournées par le healthcheck Plat'AU.
 */
final readonly class VersionInfo implements \JsonSerializable
{
    public function __construct(private ?string $version, private ?string $versionModel, private ?string $versionNomenclature)
    {
    }

    /**
     * Construit un VersionInfo depuis la réponse brute de l'API Plat'AU.
     * Normalise les chaînes vides en null.
     */
    public static function fromApiResponse(\stdClass $response): self
    {
        return new self(
            PlatauDtoHelper::normalizeString($response->version ?? null),
            PlatauDtoHelper::normalizeString($response->versionModel ?? null),
            PlatauDtoHelper::normalizeString($response->versionNomenclature ?? null)
        );
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getVersionModel(): ?string
    {
        return $this->versionModel;
    }

    public function getVersionNomenclature(): ?string
    {
        return $this->versionNomenclature;
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return [
            'version' => $this->version,
            'versionModel' => $this->versionModel,
            'versionNomenclature' => $this->versionNomenclature,
        ];
    }
}

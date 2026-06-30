<?php

namespace App\Dto\Platau;

/**
 * Informations de version retournées par le healthcheck Plat'AU.
 */
final class VersionInfo implements \JsonSerializable
{
    /** @var string|null */
    private $version;

    /** @var string|null */
    private $versionModel;

    /** @var string|null */
    private $versionNomenclature;

    public function __construct(?string $version, ?string $versionModel, ?string $versionNomenclature)
    {
        $this->version = $version;
        $this->versionModel = $versionModel;
        $this->versionNomenclature = $versionNomenclature;
    }

    /**
     * Construit un VersionInfo depuis la réponse brute de l'API Plat'AU.
     * Normalise les chaînes vides en null.
     */
    public static function fromApiResponse(\stdClass $response): self
    {
        return new self(
            self::normalizeString($response->version ?? null),
            self::normalizeString($response->versionModel ?? null),
            self::normalizeString($response->versionNomenclature ?? null)
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

    /**
     * @param mixed $value
     */
    private static function normalizeString($value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return $value;
    }
}

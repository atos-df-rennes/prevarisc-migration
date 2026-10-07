<?php

namespace App\Dto\Platau;

/**
 * Résultat du healthcheck Plat'AU conservé en cache, avec ses horaires de vérification.
 */
final readonly class HealthcheckStatus implements \JsonSerializable
{
    public const string STATUS_OK = 'ok';

    public const string STATUS_WARNING = 'warning';

    public const string STATUS_ERROR = 'error';

    public function __construct(
        private HealthcheckResult $result,
        private \DateTimeImmutable $checkedAt,
        private \DateTimeImmutable $nextCheckAt
    ) {
    }

    public function getResult(): HealthcheckResult
    {
        return $this->result;
    }

    public function getCheckedAt(): \DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function getNextCheckAt(): \DateTimeImmutable
    {
        return $this->nextCheckAt;
    }

    /**
     * Vert : disponible ; orange : disponible avec maintenance ; rouge : erreur Plat'AU ou Prevarisc.
     */
    public function getStatus(): string
    {
        if (!$this->result->isSuccess()) {
            return self::STATUS_ERROR;
        }

        return null !== $this->result->getMaintenanceInfo() ? self::STATUS_WARNING : self::STATUS_OK;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->getStatus(),
            'checkedAt' => $this->checkedAt->format(\DATE_ATOM),
            'nextCheckAt' => $this->nextCheckAt->format(\DATE_ATOM),
        ] + $this->result->jsonSerialize();
    }
}

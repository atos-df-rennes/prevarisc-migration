<?php

namespace App\Service;

use App\Dto\Platau\HealthcheckStatus;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Fournit l'état de Plat'AU en le conservant en cache pour éviter une requête à chaque page.
 * Le résultat (succès comme erreur) est partagé entre tous les utilisateurs pendant la durée configurée.
 */
class PlatauHealthcheckStatusProvider
{
    private const string CACHE_KEY = 'platau_healthcheck_status';

    public function __construct(
        private readonly PlatauHealthcheckService $healthcheckService,
        private readonly CacheInterface $cache,
        private readonly int $cacheTtl
    ) {
    }

    public function getStatus(): HealthcheckStatus
    {
        $ttl = max(1, $this->cacheTtl);

        /** @var HealthcheckStatus $status */
        $status = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) use ($ttl): HealthcheckStatus {
            $item->expiresAfter($ttl);

            $checkedAt = new \DateTimeImmutable();

            return new HealthcheckStatus(
                $this->healthcheckService->executeHealthcheck(),
                $checkedAt,
                $checkedAt->modify(sprintf('+%d seconds', $ttl))
            );
        });

        return $status;
    }
}

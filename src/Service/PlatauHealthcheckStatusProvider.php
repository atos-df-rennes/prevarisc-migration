<?php

namespace App\Service;

use App\Dto\Platau\HealthcheckStatus;
use Psr\Cache\CacheItemPoolInterface;
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
        private readonly CacheInterface&CacheItemPoolInterface $cache,
        private readonly int $cacheTtl
    ) {
    }

    /**
     * Retourne l'état en cache, en exécutant la vérification si le cache a expiré.
     */
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

    /**
     * Retourne l'état en cache sans jamais déclencher de vérification, ou null si le cache a expiré.
     */
    public function getCachedStatus(): ?HealthcheckStatus
    {
        $status = $this->cache->getItem(self::CACHE_KEY)->get();

        return $status instanceof HealthcheckStatus ? $status : null;
    }
}

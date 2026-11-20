<?php

declare(strict_types=1);

namespace App\Platau;

use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class ConsultationRetryStrategy extends GenericRetryStrategy
{
    public const int MAX_RETRIES = 5;

    public function __construct()
    {
        parent::__construct([429, 500, 503]);
    }

    public function getDelay(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): int
    {
        // RetryableHttpClient donne priorité à Retry-After ; sinon délai linéaire historique.
        $count = $context->getInfo('retry_count');

        return 1500 * (1 + (is_int($count) ? $count : 0));
    }
}

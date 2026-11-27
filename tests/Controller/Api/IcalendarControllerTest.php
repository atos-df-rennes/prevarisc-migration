<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Controller\Api\IcalendarController;
use App\Repository\UtilisateurRepository;
use App\Service\Commission\IcalendarSyncService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class IcalendarControllerTest extends TestCase
{
    #[DataProvider('securityKeys')]
    public function testSecurityKey(string $configuredKey, string $key, int $expectedStatus): void
    {
        $controller = new IcalendarController(
            $this->createStub(UtilisateurRepository::class),
            $this->createStub(IcalendarSyncService::class),
            $configuredKey,
        );

        $this->assertSame($expectedStatus, $controller->sync(key: $key)->getStatusCode());
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function securityKeys(): iterable
    {
        yield 'clé absente' => ['', '', Response::HTTP_FORBIDDEN];
        yield 'clé incorrecte' => ['cle-secrete', 'autre-cle', Response::HTTP_FORBIDDEN];
        yield 'clé correcte' => ['cle-secrete', 'cle-secrete', Response::HTTP_BAD_REQUEST];
        yield 'clé non vide zéro' => ['0', '0', Response::HTTP_BAD_REQUEST];
    }
}

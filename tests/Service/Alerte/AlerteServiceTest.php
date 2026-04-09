<?php

namespace App\Tests\Service\Alerte;

use App\Service\Alerte\AlerteService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AlerteServiceTest extends KernelTestCase
{
    /** @var AlerteService */
    private $alerteService;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        $container = self::$container;

        $this->alerteService = $container->get(AlerteService::class);
    }

    /**
     * @dataProvider alerteProvider
     */
    public function testGetLink(int $idTypeChangement, ?int $idEtablissement, string $expected): void
    {
        $link = $this->alerteService->getLink($idTypeChangement, $idEtablissement);

        $this->assertSame($expected, $link);
    }

    public static function alerteProvider(): iterable
    {
        yield 'type et établissement défini' => [
            1,
            10,
            '<a data-value="1" data-ets="10" class="alerte-link"><span class="glyphicon glyphicon-bell" aria-hidden="true"></span>Alerter</a>',
        ];

        // @fixme: idéalement il ne faudrait pas avoir l'espace supplémentaire ici
        yield 'type défini mais pas établissement' => [
            1,
            null,
            '<a data-value="1"  class="alerte-link"><span class="glyphicon glyphicon-bell" aria-hidden="true"></span>Alerter</a>',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Dto\Platau\HealthcheckResult;
use App\Dto\Platau\HealthcheckStatus;
use App\Dto\Platau\MaintenanceInfo;
use App\Dto\Platau\VersionInfo;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

final class PlatauStatusTemplateTest extends KernelTestCase
{
    /**
     * @return iterable<string, array{0: HealthcheckResult, 1: string, 2: list<string>, 3: list<string>}>
     */
    public static function statuses(): iterable
    {
        $version = new VersionInfo('2.1', 'M1', 'N1');
        $maintenance = new MaintenanceInfo(true, 'Technique', null, null, null);

        yield 'disponible' => [
            HealthcheckResult::success($version, null),
            'platau-status-ok',
            ["La connexion à Plat'AU a fonctionnée.<br><small>2.1 / Modèle : M1 / Nomenclature : N1</small>"],
            ['platau-status-version', 'platau-status-maintenance'],
        ];
        yield 'maintenance' => [
            HealthcheckResult::success($version, $maintenance),
            'platau-status-warning',
            ['<strong>Maintenance en cours</strong>'],
            ['platau-status-version'],
        ];
        yield "erreur Plat'AU avec version" => [
            HealthcheckResult::platauError("Plat'AU est en erreur", $version, null),
            'platau-status-error',
            ['id="platau-status-version" class="small text-muted">2.1 / Modèle : M1 / Nomenclature : N1</p>'],
            ['platau-status-maintenance'],
        ];
        yield 'erreur Prevarisc' => [
            HealthcheckResult::prevariscError('<b>échec</b>'),
            'platau-status-error',
            ['&lt;b&gt;échec&lt;/b&gt;'],
            ['platau-status-version', 'platau-status-maintenance'],
        ];
    }

    /**
     * @param list<string> $expected
     * @param list<string> $absent
     */
    #[DataProvider('statuses')]
    public function testRendersCachedStatus(HealthcheckResult $result, string $dotClass, array $expected, array $absent): void
    {
        $checkedAt = new \DateTimeImmutable('2026-01-01 15:52:00');
        $html = $this->render(new HealthcheckStatus($result, $checkedAt, $checkedAt->modify('+300 seconds')));

        $this->assertStringContainsString($dotClass, $html);
        $this->assertStringNotContainsString('data-url', $html);
        $this->assertStringContainsString('Dernière vérification : 15h52', $html);
        $this->assertStringNotContainsString('Prochaine vérification', $html);

        foreach ($expected as $fragment) {
            $this->assertStringContainsString($fragment, $html);
        }

        foreach ($absent as $fragment) {
            $this->assertStringNotContainsString($fragment, $html);
        }
    }

    private function render(HealthcheckStatus $status): string
    {
        $twig = self::getContainer()->get('twig');
        $this->assertInstanceOf(Environment::class, $twig);

        return $twig->render('index/_platau_status.html.twig', ['status' => $status]);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\VersionService;
use PHPUnit\Framework\TestCase;

final class VersionServiceTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/prevarisc_changelog_test_'.uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir.'/CHANGELOG*.md') ?: [] as $changelog) {
            unlink($changelog);
        }

        rmdir($this->tmpDir);
    }

    // -------------------------------------------------------------------------
    // getCurrentVersion()
    // -------------------------------------------------------------------------

    public function testGetCurrentVersionExtraitLaVersionLaPlusEleveeDeTousLesFichiers(): void
    {
        $this->writeChangelog($this->makeChangelog(['3.0.0-rc.3', '3.0.0-rc.4']), '3');
        $this->writeChangelog($this->makeChangelog(['2.10.7', '2.10.6']));

        $service = new VersionService($this->tmpDir);

        $this->assertSame('3.0.0-rc.4', $service->getCurrentVersion());
    }

    public function testGetCurrentVersionRetourneNullSiAucunFichier(): void
    {
        $service = new VersionService($this->tmpDir);

        $this->assertNull($service->getCurrentVersion());
    }

    public function testGetCurrentVersionIgnoreLeFichierHistoriqueUnique(): void
    {
        file_put_contents($this->tmpDir.'/CHANGELOG.md', $this->makeChangelog(['2.8.18']));

        $service = new VersionService($this->tmpDir);

        $this->assertNull($service->getCurrentVersion());
    }

    public function testGetCurrentVersionRetourneNullSiAucuneSection(): void
    {
        $this->writeChangelog("# Changelog\n\nAucune entrée.");

        $service = new VersionService($this->tmpDir);

        $this->assertNull($service->getCurrentVersion());
    }

    // -------------------------------------------------------------------------
    // getChangelogByMajorVersion()
    // -------------------------------------------------------------------------

    public function testGetChangelogByMajorVersionGroupeParVersionsMajeureEtMineure(): void
    {
        $this->writeChangelog($this->makeChangelog(['2.8.18', '2.8.17', '2.7.1']));
        $this->writeChangelog($this->makeChangelog(['3.0.0-rc.1']), '3');

        $service = new VersionService($this->tmpDir);
        $result = $service->getChangelogByMajorVersion();

        $this->assertSame(['3.x', '2.x'], array_keys($result));
        $this->assertSame(['2.8', '2.7'], array_keys($result['2.x']));
        $this->assertSame(['3.0'], array_keys($result['3.x']));
        $this->assertCount(2, $result['2.x']['2.8']);
        $this->assertCount(1, $result['2.x']['2.7']);
        $this->assertSame('2.8.18', $result['2.x']['2.8'][0]['version']);
        $this->assertSame('2.8.17', $result['2.x']['2.8'][1]['version']);
        $this->assertSame('2.7.1', $result['2.x']['2.7'][0]['version']);
    }

    public function testGetChangelogByMajorVersionNeMelangePasLesEnTetesDesFichiers(): void
    {
        $this->writeChangelog($this->makeChangelog(['2.8.18']));
        $this->writeChangelog(
            "<!-- BEGIN HEADER -->\n# Changelog\n<!-- END HEADER -->\n\n".$this->makeChangelog(['3.0.0-rc.1']),
            '3'
        );

        $service = new VersionService($this->tmpDir);
        $result = $service->getChangelogByMajorVersion();

        $this->assertStringNotContainsString('BEGIN HEADER', $result['2.x']['2.8'][0]['content']);
    }

    public function testGetChangelogByMajorVersionTrieLesVersionsMineuresSemantiquement(): void
    {
        $this->writeChangelog($this->makeChangelog(['2.9.0', '2.10.0', '2.8.0']));

        $service = new VersionService($this->tmpDir);
        $result = $service->getChangelogByMajorVersion();

        $this->assertSame(['2.10', '2.9', '2.8'], array_keys($result['2.x']));
    }

    public function testGetChangelogByMajorVersionSupprimeLEnTeteDuContent(): void
    {
        $this->writeChangelog($this->makeChangelog(['2.8.18']));

        $service = new VersionService($this->tmpDir);
        $result = $service->getChangelogByMajorVersion();

        $this->assertStringNotContainsString('## [2.8.18]', $result['2.x']['2.8'][0]['content']);
    }

    public function testGetChangelogByMajorVersionRetourneTableauVideSansFichier(): void
    {
        $service = new VersionService($this->tmpDir);

        $this->assertSame([], $service->getChangelogByMajorVersion());
    }

    public function testGetChangelogByMajorVersionIgnoreLeFichierHistoriqueUnique(): void
    {
        file_put_contents($this->tmpDir.'/CHANGELOG.md', $this->makeChangelog(['2.8.18']));

        $service = new VersionService($this->tmpDir);

        $this->assertSame([], $service->getChangelogByMajorVersion());
    }

    public function testGetChangelogByMajorVersionRetourneTableauVideSiAucuneSection(): void
    {
        $this->writeChangelog("# Changelog\n\nAucune entrée.");

        $service = new VersionService($this->tmpDir);

        $this->assertSame([], $service->getChangelogByMajorVersion());
    }

    public function testGetChangelogByMajorVersionConserveLaDateDeChaquePatch(): void
    {
        $this->writeChangelog(
            "## [2.8.18](https://example.com) (31-03-2026)\n\nContenu.\n\n---\n".
            "## [2.8.17](https://example.com) (24-03-2026)\n\nContenu.\n\n"
        );

        $service = new VersionService($this->tmpDir);
        $result = $service->getChangelogByMajorVersion();

        $this->assertSame('31-03-2026', $result['2.x']['2.8'][0]['date']);
        $this->assertSame('24-03-2026', $result['2.x']['2.8'][1]['date']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function writeChangelog(string $content, string $majorVersion = '2'): void
    {
        file_put_contents($this->tmpDir.'/CHANGELOG-'.$majorVersion.'.x.md', $content);
    }

    /**
     * Génère un changelog minimal avec les versions fournies.
     *
     * @param string[] $versions
     */
    private function makeChangelog(array $versions): string
    {
        $lines = ["# Changelog\n"];
        foreach ($versions as $version) {
            $lines[] = "## [{$version}](https://example.com) (01-01-2026)\n\n* Une entrée.\n\n---\n";
        }

        return implode("\n", $lines);
    }
}

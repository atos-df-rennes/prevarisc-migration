<?php

namespace App\Tests\Service;

use App\Service\VersionService;
use PHPUnit\Framework\TestCase;

final class VersionServiceTest extends TestCase
{
    /** @var string */
    private $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/prevarisc_changelog_test_'.uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $changelog = $this->tmpDir.'/CHANGELOG.md';
        if (file_exists($changelog)) {
            unlink($changelog);
        }

        rmdir($this->tmpDir);
    }

    // -------------------------------------------------------------------------
    // getCurrentVersion()
    // -------------------------------------------------------------------------

    public function testGetCurrentVersionExtraitLaPremierVersion(): void
    {
        $this->writeChangelog($this->makeChangelog(['2.8.18', '2.8.17', '2.8.16']));

        $service = new VersionService($this->tmpDir);

        self::assertSame('2.8.18', $service->getCurrentVersion());
    }

    public function testGetCurrentVersionRetourneNullSiAucunFichier(): void
    {
        $service = new VersionService($this->tmpDir);

        self::assertNull($service->getCurrentVersion());
    }

    public function testGetCurrentVersionRetourneNullSiAucuneSection(): void
    {
        $this->writeChangelog("# Changelog\n\nAucune entrée.");

        $service = new VersionService($this->tmpDir);

        self::assertNull($service->getCurrentVersion());
    }

    // -------------------------------------------------------------------------
    // getChangelogByMinorVersion()
    // -------------------------------------------------------------------------

    public function testGetChangelogByMinorVersionGroupeParVersionMineure(): void
    {
        $this->writeChangelog($this->makeChangelog(['2.8.18', '2.8.17', '2.7.1']));

        $service = new VersionService($this->tmpDir);
        $result = $service->getChangelogByMinorVersion();

        self::assertArrayHasKey('2.8', $result);
        self::assertArrayHasKey('2.7', $result);
        self::assertCount(2, $result['2.8']);
        self::assertCount(1, $result['2.7']);
        self::assertSame('2.8.18', $result['2.8'][0]['version']);
        self::assertSame('2.8.17', $result['2.8'][1]['version']);
        self::assertSame('2.7.1', $result['2.7'][0]['version']);
    }

    public function testGetChangelogByMinorVersionTrieSemantiquementDescendant(): void
    {
        // Cas réel : 2.10 doit apparaître avant 2.9 (tri sémantique, pas alphabétique)
        $this->writeChangelog($this->makeChangelog(['2.9.0', '2.10.0', '2.8.0']));

        $service = new VersionService($this->tmpDir);
        $keys = array_keys($service->getChangelogByMinorVersion());

        self::assertSame(['2.10', '2.9', '2.8'], $keys);
    }

    public function testGetChangelogByMinorVersionSupprimeLEnTeteDuContent(): void
    {
        $this->writeChangelog($this->makeChangelog(['2.8.18']));

        $service = new VersionService($this->tmpDir);
        $result = $service->getChangelogByMinorVersion();

        // La ligne ## [2.8.18](...) ne doit pas apparaître dans le content
        self::assertStringNotContainsString('## [2.8.18]', $result['2.8'][0]['content']);
    }

    public function testGetChangelogByMinorVersionRetourneTableauVideSiFichierAbsent(): void
    {
        $service = new VersionService($this->tmpDir);

        self::assertSame([], $service->getChangelogByMinorVersion());
    }

    public function testGetChangelogByMinorVersionRetourneTableauVideSiAucuneSection(): void
    {
        $this->writeChangelog("# Changelog\n\nAucune entrée.");

        $service = new VersionService($this->tmpDir);

        self::assertSame([], $service->getChangelogByMinorVersion());
    }

    public function testGetChangelogByMinorVersionConserveLaDateDeChaquePatch(): void
    {
        $this->writeChangelog(
            "## [2.8.18](https://example.com) (31-03-2026)\n\nContenu.\n\n---\n\n".
            "## [2.8.17](https://example.com) (24-03-2026)\n\nContenu.\n\n"
        );

        $service = new VersionService($this->tmpDir);
        $result = $service->getChangelogByMinorVersion();

        self::assertSame('31-03-2026', $result['2.8'][0]['date']);
        self::assertSame('24-03-2026', $result['2.8'][1]['date']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function writeChangelog(string $content): void
    {
        file_put_contents($this->tmpDir.'/CHANGELOG.md', $content);
    }

    /**
     * Génère un CHANGELOG.md minimal avec les versions fournies.
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

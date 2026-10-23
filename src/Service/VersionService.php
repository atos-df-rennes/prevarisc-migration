<?php

namespace App\Service;

final readonly class VersionService
{
    public function __construct(private string $srcDir)
    {
    }

    public function getCurrentVersion(): ?string
    {
        $changelogs = $this->readChangelogs();

        if (null === $changelogs) {
            return null;
        }

        $currentVersion = null;

        foreach ($changelogs as $contents) {
            preg_match_all('/^## \[([^\]]+)\]/m', $contents, $matches);

            foreach ($matches[1] as $version) {
                if (null === $currentVersion || 0 < version_compare($version, $currentVersion)) {
                    $currentVersion = $version;
                }
            }
        }

        return $currentVersion;
    }

    /**
     * Parse les entrées des changelogs et les groupe par version majeure puis mineure.
     *
     * @return array<string, array<string, list<array{version: string, date: string, content: string}>>>
     */
    public function getChangelogByMajorVersion(): array
    {
        $changelogs = $this->readChangelogs();

        if (null === $changelogs) {
            return [];
        }

        $pattern = '/^## \[([^\]]+)\]\([^)]*\)\s*\(([^)]+)\)/m';
        $sections = [];

        foreach ($changelogs as $contents) {
            preg_match_all($pattern, $contents, $headingMatches, PREG_OFFSET_CAPTURE);
            $count = count($headingMatches[0]);

            for ($i = 0; $i < $count; ++$i) {
                $version = $headingMatches[1][$i][0];
                $date = $headingMatches[2][$i][0];
                $start = $headingMatches[0][$i][1];
                $end = ($i + 1 < $count) ? $headingMatches[0][$i + 1][1] : strlen($contents);

                $block = substr($contents, $start, $end - $start);
                $block = (string) preg_replace('/\s*---\s*$/', '', trim($block));
                $block = (string) preg_replace('/^##[^\n]*\n\n?/', '', $block);

                $majorVersion = $this->extractMajorVersion($version);
                $minorVersion = $this->extractMinorVersion($version);

                $sections[$majorVersion][$minorVersion] ??= [];
                $sections[$majorVersion][$minorVersion][] = [
                    'version' => $version,
                    'date' => $date,
                    'content' => $block,
                ];
            }
        }

        uksort($sections, fn (string $a, string $b): int => version_compare($b, $a));

        foreach ($sections as &$minorVersions) {
            uksort($minorVersions, fn (string $a, string $b): int => version_compare($b, $a));
        }

        unset($minorVersions);

        return $sections;
    }

    /**
     * @return list<string>|null
     */
    private function readChangelogs(): ?array
    {
        $paths = glob($this->srcDir.'/CHANGELOG-*.x.md');

        if (false === $paths || [] === $paths) {
            return null;
        }

        $changelogs = [];

        foreach ($paths as $path) {
            $contents = file_get_contents($path);

            if (false === $contents) {
                return null;
            }

            $changelogs[] = $contents;
        }

        return $changelogs;
    }

    private function extractMajorVersion(string $version): string
    {
        $clean = (string) preg_replace('/[^0-9.].*/', '', $version);
        $parts = explode('.', $clean);

        return $parts[0].'.x';
    }

    private function extractMinorVersion(string $version): string
    {
        $clean = (string) preg_replace('/[^0-9.].*/', '', $version);
        $parts = explode('.', $clean);

        return isset($parts[1]) ? $parts[0].'.'.$parts[1] : $parts[0];
    }
}

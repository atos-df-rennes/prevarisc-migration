<?php

namespace App\Service;

final readonly class VersionService
{
    public function __construct(private string $srcDir)
    {
    }

    public function getCurrentVersion(): ?string
    {
        $contents = $this->readChangelog();

        if (null === $contents) {
            return null;
        }

        if (1 === preg_match('/^## \[([^\]]+)\]/m', $contents, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Parse le CHANGELOG.md et retourne les entrées groupées par version mineure (ex: "2.8").
     * Chaque entrée contient la version, la date et le contenu Markdown brut.
     *
     * @return array<string, array<int, array{version: string, date: string, content: string}>>
     */
    public function getChangelogByMinorVersion(): array
    {
        $contents = $this->readChangelog();

        if (null === $contents) {
            return [];
        }

        $pattern = '/^## \[([^\]]+)\]\([^)]*\)\s*\(([^)]+)\)/m';
        preg_match_all($pattern, $contents, $headingMatches, PREG_OFFSET_CAPTURE);

        if (empty($headingMatches[0])) {
            return [];
        }

        $sections = [];
        $count = count($headingMatches[0]);

        for ($i = 0; $i < $count; ++$i) {
            $version = $headingMatches[1][$i][0];
            $date = $headingMatches[2][$i][0];
            $start = $headingMatches[0][$i][1];
            $end = ($i + 1 < $count) ? $headingMatches[0][$i + 1][1] : strlen($contents);

            $block = substr($contents, $start, $end - $start);
            $block = (string) preg_replace('/\s*---\s*$/', '', trim($block));
            // Supprimer la ligne d'en-tête ## [x.y.z]... pour éviter la duplication dans le rendu
            $block = (string) preg_replace('/^##[^\n]*\n\n?/', '', $block);

            $minorVersion = $this->extractMinorVersion($version);

            $sections[$minorVersion] ??= [];

            $sections[$minorVersion][] = [
                'version' => $version,
                'date' => $date,
                'content' => $block,
            ];
        }

        uksort($sections, fn (string $a, string $b): int => version_compare($b, $a));

        return $sections;
    }

    private function readChangelog(): ?string
    {
        $path = $this->srcDir.'/CHANGELOG.md';

        if (!file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        return false !== $contents ? $contents : null;
    }

    private function extractMinorVersion(string $version): string
    {
        $clean = (string) preg_replace('/[^0-9.].*/', '', $version);
        $parts = explode('.', $clean);

        return isset($parts[1]) ? $parts[0].'.'.$parts[1] : $parts[0];
    }
}

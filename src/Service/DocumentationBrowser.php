<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\DocumentationSpace;
use Michelf\MarkdownExtra;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class DocumentationBrowser
{
    public function __construct(
        private string $srcDir,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return list<array{space: string, label: string, children: list<array<string, mixed>>}>
     */
    public function getNavigation(bool $includeTechnical): array
    {
        $spaces = [DocumentationSpace::Functional];
        if ($includeTechnical) {
            $spaces[] = DocumentationSpace::Technical;
        }

        $navigation = [];
        foreach ($spaces as $space) {
            $root = $this->getRootPath($space);
            if (!is_dir($root)) {
                throw new \RuntimeException(sprintf('Le répertoire de documentation "%s" est introuvable.', $root));
            }

            $navigation[] = [
                'space' => $space->value,
                'label' => $space->label(),
                'children' => $this->getDirectoryEntries($root, ''),
            ];
        }

        return $navigation;
    }

    /**
     * @return array{space: string, spaceLabel: string, path: string, title: string, format: 'md'|'html', content: string}
     */
    public function getDocument(string $spaceName, string $relativePath): array
    {
        $space = DocumentationSpace::tryFrom($spaceName);
        if (null === $space) {
            throw new \InvalidArgumentException('Espace de documentation inconnu.');
        }

        $filePath = $this->resolveFilePath($space, $relativePath);
        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Le fichier de documentation "%s" n’a pas pu être lu.', $relativePath));
        }

        $format = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if (!in_array($format, ['md', 'html'], true)) {
            throw new \InvalidArgumentException('Type de document non autorisé.');
        }

        $title = $this->getTitle($content, basename($relativePath));

        if ('md' === $format) {
            $content = $this->renderMarkdown($content, $space, $relativePath);
        }

        return [
            'space' => $space->value,
            'spaceLabel' => $space->label(),
            'path' => $relativePath,
            'title' => $title,
            'format' => $format,
            'content' => $content,
        ];
    }

    public static function slugifyHeading(string $heading): string
    {
        $slug = mb_strtolower(trim($heading), 'UTF-8');
        $slug = preg_replace('/\s+/u', '-', $slug) ?? '';
        $slug = preg_replace('/[^\p{L}\p{N}_-]/u', '', $slug) ?? '';

        return trim($slug, '-');
    }

    private function getRootPath(DocumentationSpace $space): string
    {
        return $this->srcDir.'/docs/'.$space->value;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getDirectoryEntries(string $directory, string $relativeDirectory): array
    {
        $names = scandir($directory);
        if (false === $names) {
            throw new \RuntimeException(sprintf('Le répertoire de documentation "%s" n’a pas pu être lu.', $directory));
        }

        $entries = [];
        foreach ($names as $name) {
            if ('.' === $name || '..' === $name || str_starts_with($name, '.') || is_link($directory.'/'.$name)) {
                continue;
            }

            $relativePath = '' === $relativeDirectory ? $name : $relativeDirectory.'/'.$name;
            $absolutePath = $directory.'/'.$name;
            if (is_dir($absolutePath)) {
                $entries[] = [
                    'type' => 'directory',
                    'label' => str_replace(['-', '_'], ' ', $name),
                    'path' => $relativePath,
                    'children' => $this->getDirectoryEntries($absolutePath, $relativePath),
                ];

                continue;
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($extension, ['md', 'html'], true)) {
                continue;
            }

            $contents = file_get_contents($absolutePath);
            if (false === $contents) {
                throw new \RuntimeException(sprintf('Le fichier de documentation "%s" n’a pas pu être lu.', $relativePath));
            }

            $entries[] = [
                'type' => 'file',
                'label' => $this->getTitle($contents, $name),
                'path' => $relativePath,
                'format' => $extension,
            ];
        }

        usort($entries, static function (array $left, array $right): int {
            $leftPriority = 'file' === $left['type'] ? self::filePriority($left['path']) : 2;
            $rightPriority = 'file' === $right['type'] ? self::filePriority($right['path']) : 2;
            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            if ($left['type'] !== $right['type']) {
                return 'directory' === $left['type'] ? -1 : 1;
            }

            return strnatcasecmp($left['label'], $right['label']);
        });

        return $entries;
    }

    private static function filePriority(string $path): int
    {
        return match (strtolower(basename($path))) {
            'index.md' => 0,
            'readme.md' => 1,
            default => 2,
        };
    }

    private function getTitle(string $contents, string $filename): string
    {
        if (preg_match('/^\s{0,3}#{1,6}\s+(.+?)\s*#*\s*$/mu', $contents, $matches)) {
            return trim($matches[1]);
        }

        return ucfirst(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
    }

    private function resolveFilePath(DocumentationSpace $space, string $relativePath): string
    {
        if (
            '' === $relativePath
            || str_starts_with($relativePath, '/')
            || str_contains($relativePath, '\\')
            || str_contains($relativePath, "\0")
        ) {
            throw new \InvalidArgumentException('Chemin de documentation invalide.');
        }

        $segments = explode('/', $relativePath);
        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || str_starts_with($segment, '.')) {
                throw new \InvalidArgumentException('Chemin de documentation invalide.');
            }
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['md', 'html'], true)) {
            throw new \InvalidArgumentException('Type de document non autorisé.');
        }

        $root = realpath($this->getRootPath($space));
        if (false === $root) {
            throw new \RuntimeException(sprintf('Le répertoire de documentation "%s" est introuvable.', $space->value));
        }

        $candidate = $root;
        foreach ($segments as $segment) {
            $candidate .= DIRECTORY_SEPARATOR.$segment;
            if (is_link($candidate)) {
                throw new \InvalidArgumentException('Chemin de documentation invalide.');
            }
        }

        $resolvedPath = realpath($candidate);
        if (
            false === $resolvedPath
            || !is_file($resolvedPath)
            || !str_starts_with($resolvedPath, $root.DIRECTORY_SEPARATOR)
        ) {
            throw new \InvalidArgumentException('Document de documentation introuvable.');
        }

        return $resolvedPath;
    }

    private function renderMarkdown(string $markdown, DocumentationSpace $space, string $currentPath): string
    {
        $parser = new MarkdownExtra();
        $parser->no_markup = true;
        $parser->url_filter_func = static function (string $url): string {
            $normalizedUrl = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $normalizedUrl = preg_replace('/[\x00-\x20\x7f]+/', '', $normalizedUrl) ?? $normalizedUrl;

            $scheme = parse_url($normalizedUrl, PHP_URL_SCHEME);

            if (
                false === $scheme
                || (null !== $scheme && !in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true))
            ) {
                return '#';
            }

            return $url;
        };

        return $this->rewriteRelativeLinks($parser->transform($markdown), $space, $currentPath);
    }

    private function rewriteRelativeLinks(string $html, DocumentationSpace $space, string $currentPath): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousErrorSetting = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="documentation-content">'.$html.'</div></body></html>',
            LIBXML_NONET | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorSetting);

        if (!$loaded) {
            throw new \RuntimeException('Le contenu Markdown converti n’a pas pu être analysé.');
        }

        $xpath = new \DOMXPath($document);
        $headings = $xpath->query('//*[@id="documentation-content"]//h1 | //*[@id="documentation-content"]//h2 | //*[@id="documentation-content"]//h3 | //*[@id="documentation-content"]//h4 | //*[@id="documentation-content"]//h5 | //*[@id="documentation-content"]//h6');
        if (!$headings instanceof \DOMNodeList) {
            throw new \RuntimeException('Les titres du document Markdown n’ont pas pu être analysés.');
        }

        $usedHeadingIds = [];
        foreach ($headings as $heading) {
            if (!$heading instanceof \DOMElement) {
                continue;
            }

            $id = $heading->getAttribute('id');
            if ('' !== $id) {
                $usedHeadingIds[$id] = true;

                continue;
            }

            $baseId = self::slugifyHeading($heading->textContent) ?: 'section';
            $id = $baseId;
            $suffix = 1;
            while (isset($usedHeadingIds[$id])) {
                $id = $baseId.'-'.$suffix;
                ++$suffix;
            }

            $usedHeadingIds[$id] = true;
            $heading->setAttribute('id', $id);
        }

        $links = $xpath->query('//*[@id="documentation-content"]//a[@href]');
        if (!$links instanceof \DOMNodeList) {
            throw new \RuntimeException('Les liens du document Markdown n’ont pas pu être analysés.');
        }

        foreach ($links as $link) {
            if (!$link instanceof \DOMElement) {
                continue;
            }

            $href = $link->getAttribute('href');
            $parts = parse_url($href);
            if (false === $parts || isset($parts['scheme']) || isset($parts['host'])) {
                continue;
            }

            $targetPath = $parts['path'] ?? '';
            if ('' === $targetPath || !in_array(strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)), ['md', 'html'], true)) {
                continue;
            }

            $relativeTarget = $this->normalizeRelativeTarget($currentPath, $targetPath);
            if (null === $relativeTarget || !$this->isValidDocumentTarget($space, $relativeTarget)) {
                $link->removeAttribute('href');

                continue;
            }

            $targetUrl = $this->urlGenerator->generate('documentation_document', [
                'space' => $space->value,
                'path' => $relativeTarget,
            ]);
            if (isset($parts['query'])) {
                $targetUrl .= '?'.$parts['query'];
            }

            if (isset($parts['fragment'])) {
                $targetUrl .= '#'.$parts['fragment'];
            }

            $link->setAttribute('href', $targetUrl);
        }

        $contentNodes = $xpath->query('//*[@id="documentation-content"]');
        if (!$contentNodes instanceof \DOMNodeList) {
            throw new \RuntimeException('Le contenu du document Markdown n’a pas pu être récupéré.');
        }

        $content = $contentNodes->item(0);
        if (!$content instanceof \DOMElement) {
            throw new \RuntimeException('Le contenu du document Markdown n’a pas pu être récupéré.');
        }

        $rendered = '';
        foreach ($content->childNodes as $childNode) {
            $rendered .= $document->saveHTML($childNode);
        }

        return $rendered;
    }

    private function normalizeRelativeTarget(string $currentPath, string $targetPath): ?string
    {
        if (str_starts_with($targetPath, '/') || str_contains($targetPath, '\\') || str_contains($targetPath, "\0")) {
            return null;
        }

        $segments = explode('/', dirname($currentPath));
        if ('.' === $segments[0]) {
            $segments = [];
        }

        foreach (explode('/', rawurldecode($targetPath)) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }

            if ('..' === $segment) {
                if ([] === $segments) {
                    return null;
                }

                array_pop($segments);

                continue;
            }

            if (str_contains($segment, "\0") || str_contains($segment, '\\')) {
                return null;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function isValidDocumentTarget(DocumentationSpace $space, string $relativePath): bool
    {
        try {
            $this->resolveFilePath($space, $relativePath);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return true;
    }
}

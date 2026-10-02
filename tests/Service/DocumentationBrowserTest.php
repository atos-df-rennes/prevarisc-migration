<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\DocumentationBrowser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class DocumentationBrowserTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/prevarisc_documentation_test_'.uniqid();
        mkdir($this->projectDir.'/docs/specs/nested', 0777, true);
        mkdir($this->projectDir.'/docs/tech', 0777, true);

        $this->writeDocument('docs/specs/INDEX.md', <<<'MD'
# Index fonctionnel

[Document interne](nested/README.md#section)

[Document absent](missing.md)

[Lien externe](https://example.com)

[Hors de l'espace](../outside.md)

[JavaScript interdit](javascript:alert(1))

![Image interdite](data:text/html,unsafe)

<script>alert('unsafe')</script>

Titre
-----

| Colonne | Valeur |
| --- | --- |
| A | B |
MD);
        $this->writeDocument('docs/specs/nested/README.md', "# Section\n\nContenu.");
        $this->writeDocument('docs/specs/demo.html', '<!doctype html><p>Démo HTML</p>');
        $this->writeDocument('docs/tech/README.md', '# Documentation technique');
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->projectDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->projectDir);
    }

    public function testNavigationListsOnlyAuthorizedSpacesAndNestedMarkdownAndHtmlFiles(): void
    {
        $functionalNavigation = $this->browser()->getNavigation(false);
        $this->assertCount(1, $functionalNavigation);
        $this->assertSame('specs', $functionalNavigation[0]['space']);
        $this->assertSame('INDEX.md', $functionalNavigation[0]['children'][0]['path']);
        $this->assertSame('directory', $functionalNavigation[0]['children'][1]['type']);

        $allNavigation = $this->browser()->getNavigation(true);
        $this->assertSame(['specs', 'tech'], array_column($allNavigation, 'space'));
        $this->assertSame('README.md', $allNavigation[1]['children'][0]['path']);
        $this->assertSame('html', $functionalNavigation[0]['children'][2]['format']);
    }

    public function testMarkdownRenderingAddsHeadingIdsAndRewritesOnlyValidInternalLinks(): void
    {
        $document = $this->browser()->getDocument('specs', 'INDEX.md');

        $this->assertSame('md', $document['format']);
        $this->assertStringContainsString('<h1 id="index-fonctionnel">', $document['content']);
        $this->assertStringContainsString('<h2 id="titre">', $document['content']);
        $this->assertStringContainsString('href="/documentation/specs/nested/README.md#section"', $document['content']);
        $this->assertStringContainsString('href="https://example.com"', $document['content']);
        $this->assertStringContainsString('Document absent</a>', $document['content']);
        $this->assertStringNotContainsString('href="missing.md"', $document['content']);
        $this->assertStringNotContainsString('href="../outside.md"', $document['content']);
        $this->assertStringNotContainsString('href="javascript:', $document['content']);
        $this->assertStringNotContainsString('src="data:', $document['content']);
        $this->assertStringNotContainsString('<script>', $document['content']);
        $this->assertStringContainsString('&lt;script&gt;', $document['content']);
        $this->assertStringContainsString('<table>', $document['content']);
    }

    public function testHtmlDocumentIsReturnedWithoutMarkdownConversion(): void
    {
        $document = $this->browser()->getDocument('specs', 'demo.html');

        $this->assertSame('html', $document['format']);
        $this->assertSame('<!doctype html><p>Démo HTML</p>', $document['content']);
    }

    public function testNavigationExcludesHiddenUnsupportedAndLinkedEntries(): void
    {
        $this->writeDocument('docs/specs/.hidden.md', '# Hidden');
        $this->writeDocument('docs/specs/.hidden/README.md', '# Hidden directory');
        $this->writeDocument('docs/specs/ignored.txt', '# Ignored');
        $this->writeDocument('docs/specs/uppercase.MD', '# Uppercase');
        $this->writeDocument('docs/specs/empty/ignored.txt', '# Ignored');
        symlink($this->projectDir.'/docs/specs/INDEX.md', $this->projectDir.'/docs/specs/linked.md');
        symlink($this->projectDir.'/docs/specs/nested', $this->projectDir.'/docs/specs/linked-directory');

        $entries = $this->browser()->getNavigation(false)[0]['children'];
        $this->assertSame(['INDEX.md', 'empty', 'nested', 'demo.html', 'uppercase.MD'], array_column($entries, 'path'));
        $this->assertSame([], $entries[1]['children']);
        $this->assertSame('md', $entries[4]['format']);
        $this->assertSame('md', $this->browser()->getDocument('specs', 'uppercase.MD')['format']);
    }

    #[DataProvider('provideLinkedPaths')]
    public function testRejectsSymlinksEvenWhenTheirTargetsAreInsideTheDocumentation(string $path, string $target): void
    {
        symlink($this->projectDir.'/'.$target, $this->projectDir.'/docs/specs/'.explode('/', $path)[0]);
        $this->expectException(\InvalidArgumentException::class);

        $this->browser()->getDocument('specs', $path);
    }

    public static function provideLinkedPaths(): iterable
    {
        yield 'file inside root' => ['linked.md', 'docs/specs/INDEX.md'];
        yield 'directory inside root' => ['linked/README.md', 'docs/specs/nested'];
        yield 'file outside root' => ['linked.md', 'docs/tech/README.md'];
        yield 'directory outside root' => ['linked/README.md', 'docs/tech'];
    }

    #[DataProvider('provideDocumentTitles')]
    public function testExtractsMarkdownTitlesOrBuildsUnicodeFilenameTitles(string $filename, string $contents, string $expected): void
    {
        $this->writeDocument('docs/specs/'.$filename, $contents);

        $this->assertSame($expected, $this->browser()->getDocument('specs', $filename)['title']);
    }

    public static function provideDocumentTitles(): iterable
    {
        yield 'markdown heading' => ['example.md', "Introduction\n\n  ## Un titre ###\n", 'Un titre'];
        yield 'filename separators' => ['guide_de-lecture.md', 'Sans titre', 'Guide de lecture'];
        yield 'accented filename' => ['établissements.md', 'Sans titre', 'Établissements'];
    }

    public function testGeneratedAnchorsHandleCollisionsAndPreserveExplicitIdsAndLinks(): void
    {
        $this->writeDocument('docs/specs/anchors.md', <<<'MD'
# Établissements
## Etablissements
## Ancre conservée {#custom}
## !!!
## ???

[Première section](#etablissements)
[Seconde section](#etablissements-1)
[Ancre explicite](#custom)
[Autre document](nested/README.md#section)
MD);
        $content = $this->browser()->getDocument('specs', 'anchors.md')['content'];

        foreach (['etablissements', 'etablissements-1', 'custom', 'section', 'section-1'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $content);
        }

        $this->assertStringContainsString('href="#etablissements"', $content);
        $this->assertStringContainsString('href="#etablissements-1"', $content);
        $this->assertStringContainsString('href="#custom"', $content);
        $this->assertStringContainsString('href="/documentation/specs/nested/README.md#section"', $content);
    }

    public function testProjectIndexLinksMatchGeneratedHeadingIds(): void
    {
        $content = $this->browser(dirname(__DIR__, 2))->getDocument('specs', 'INDEX.md')['content'];
        $document = new \DOMDocument();
        $document->loadHTML('<meta charset="UTF-8">'.$content, LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//a[starts-with(@href, "#")]');
        $this->assertInstanceOf(\DOMNodeList::class, $links);
        $this->assertGreaterThanOrEqual(24, $links->length);

        foreach ($links as $link) {
            $this->assertInstanceOf(\DOMElement::class, $link);
            $id = substr($link->getAttribute('href'), 1);
            $this->assertInstanceOf(\DOMElement::class, $document->getElementById($id), 'Missing heading for #'.$id);
        }
    }

    #[DataProvider('provideInvalidPaths')]
    public function testRejectsInvalidOrUnavailableDocumentPaths(string $path): void
    {
        mkdir($this->projectDir.'/docs/specs/folder.md');
        $this->expectException(\InvalidArgumentException::class);

        $this->browser()->getDocument('specs', $path);
    }

    public static function provideInvalidPaths(): iterable
    {
        yield 'parent traversal' => ['../outside.md'];
        yield 'nested traversal' => ['nested/../../outside.md'];
        yield 'absolute path' => ['/etc/passwd.md'];
        yield 'unsupported extension' => ['INDEX.txt'];
        yield 'missing file' => ['missing.md'];
        yield 'empty path' => [''];
        yield 'empty segment' => ['nested//README.md'];
        yield 'current directory' => ['./INDEX.md'];
        yield 'nested current directory' => ['nested/./README.md'];
        yield 'hidden file' => ['.hidden.md'];
        yield 'hidden directory' => ['.hidden/README.md'];
        yield 'backslash' => ['nested\\README.md'];
        yield 'null byte' => ["INDEX.md\0"];
        yield 'directory with document extension' => ['folder.md'];
    }

    #[DataProvider('provideHeadingSlugs')]
    public function testSlugifiesHeadingsForInternalAnchors(string $heading, string $expected): void
    {
        $this->assertSame($expected, $this->browser()->slugifyHeading($heading));
    }

    public static function provideHeadingSlugs(): iterable
    {
        yield 'accented title with punctuation' => ['MOD-01 — Établissements', 'mod-01-etablissements'];
        yield 'apostrophe' => ["À propos d'un dossier", 'a-propos-d-un-dossier'];
        yield 'repeated spaces' => ['Titre   avec espaces', 'titre-avec-espaces'];
        yield 'underscores and repeated hyphens' => ['Titre__avec---séparateurs', 'titre-avec-separateurs'];
        yield 'only punctuation' => ['!!!', ''];
    }

    private function browser(?string $projectDir = null): DocumentationBrowser
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $parameters): string => sprintf(
                '/documentation/%s/%s',
                $parameters['space'],
                str_replace('%2F', '/', rawurlencode($parameters['path'])),
            ),
        );

        return new DocumentationBrowser($projectDir ?? $this->projectDir, $urlGenerator, new AsciiSlugger('fr'));
    }

    private function writeDocument(string $path, string $content): void
    {
        $filePath = $this->projectDir.'/'.$path;
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $content);
    }
}

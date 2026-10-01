<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\DocumentationBrowser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
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

    #[DataProvider('provideInvalidPaths')]
    public function testRejectsInvalidOrUnavailableDocumentPaths(string $path): void
    {
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
    }

    #[DataProvider('provideHeadingSlugs')]
    public function testSlugifiesHeadingsForInternalAnchors(string $heading, string $expected): void
    {
        $this->assertSame($expected, DocumentationBrowser::slugifyHeading($heading));
    }

    public static function provideHeadingSlugs(): iterable
    {
        yield 'accented title with punctuation' => ['MOD-01 — Établissements', 'mod-01--établissements'];
        yield 'apostrophe' => ["À propos d'un dossier", 'à-propos-dun-dossier'];
        yield 'repeated spaces' => ['Titre   avec espaces', 'titre-avec-espaces'];
    }

    private function browser(): DocumentationBrowser
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $parameters): string => sprintf(
                '/documentation/%s/%s',
                $parameters['space'],
                str_replace('%2F', '/', rawurlencode($parameters['path'])),
            ),
        );

        return new DocumentationBrowser($this->projectDir, $urlGenerator);
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

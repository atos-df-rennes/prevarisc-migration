<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\HighlightExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HighlightExtensionTest extends TestCase
{
    private HighlightExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new HighlightExtension();
    }

    /**
     * @param string[] $keywords
     */
    #[DataProvider('highlightProvider')]
    public function testHighlight(string $subject, array $keywords, string $expected): void
    {
        $result = $this->extension->highlight($subject, $keywords);

        $this->assertSame($expected, $result);
    }

    public static function highlightProvider(): \Iterator
    {
        yield 'empty keywords returns subject unchanged' => [
            'Texte sans mise en évidence',
            [],
            'Texte sans mise en évidence',
        ];
        yield 'single keyword is wrapped in highlight span' => [
            'Porte coupe-feu',
            ['porte'],
            '<span class="highlight">Porte</span> coupe-feu',
        ];
        yield 'matching is case insensitive and preserves original case' => [
            'PORTE coupe-feu',
            ['porte'],
            '<span class="highlight">PORTE</span> coupe-feu',
        ];
        yield 'multiple keywords are all highlighted' => [
            'Porte coupe-feu installée',
            ['Porte', 'installée'],
            '<span class="highlight">Porte</span> coupe-feu <span class="highlight">installée</span>',
        ];
        yield 'keyword not found returns subject unchanged' => [
            'Escalier de secours',
            ['porte'],
            'Escalier de secours',
        ];
        yield 'empty keyword is ignored' => [
            'Porte coupe-feu',
            [''],
            'Porte coupe-feu',
        ];
        yield 'keyword with html special chars does not match escaped entities' => [
            'R&amp;glementaire',
            ['R&glementaire'],
            'R&amp;glementaire',
        ];
        yield 'keyword matching inside html tag name is skipped' => [
            'Porte<br />coupe-feu',
            ['br'],
            'Porte<br />coupe-feu',
        ];
        yield 'html tags in subject are preserved when keyword matches outside tags' => [
            'Porte<br />coupe-feu',
            ['coupe-feu'],
            'Porte<br /><span class="highlight">coupe-feu</span>',
        ];
        yield 'keyword appearing multiple times is highlighted each time' => [
            'Porte porte PORTE',
            ['porte'],
            '<span class="highlight">Porte</span> <span class="highlight">porte</span> <span class="highlight">PORTE</span>',
        ];
    }
}

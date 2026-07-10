<?php

namespace App\Tests\Twig;

use App\Twig\HighlightExtension;
use PHPUnit\Framework\TestCase;

class HighlightExtensionTest extends TestCase
{
    private HighlightExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new HighlightExtension();
    }

    /**
     * @dataProvider highlightProvider
     *
     * @param string[] $keywords
     */
    public function testHighlight(string $subject, array $keywords, string $expected): void
    {
        $result = $this->extension->highlight($subject, $keywords);

        $this->assertSame($expected, $result);
    }

    public function highlightProvider(): array
    {
        return [
            'empty keywords returns subject unchanged' => [
                'Texte sans mise en évidence',
                [],
                'Texte sans mise en évidence',
            ],
            'single keyword is wrapped in highlight span' => [
                'Porte coupe-feu',
                ['porte'],
                '<span class="highlight">Porte</span> coupe-feu',
            ],
            'matching is case insensitive and preserves original case' => [
                'PORTE coupe-feu',
                ['porte'],
                '<span class="highlight">PORTE</span> coupe-feu',
            ],
            'multiple keywords are all highlighted' => [
                'Porte coupe-feu installée',
                ['Porte', 'installée'],
                '<span class="highlight">Porte</span> coupe-feu <span class="highlight">installée</span>',
            ],
            'keyword not found returns subject unchanged' => [
                'Escalier de secours',
                ['porte'],
                'Escalier de secours',
            ],
            'empty keyword is ignored' => [
                'Porte coupe-feu',
                [''],
                'Porte coupe-feu',
            ],
            'keyword with html special chars does not match escaped entities' => [
                'R&amp;glementaire',
                ['R&glementaire'],
                'R&amp;glementaire',
            ],
            'keyword matching inside html tag name is skipped' => [
                'Porte<br />coupe-feu',
                ['br'],
                'Porte<br />coupe-feu',
            ],
            'html tags in subject are preserved when keyword matches outside tags' => [
                'Porte<br />coupe-feu',
                ['coupe-feu'],
                'Porte<br /><span class="highlight">coupe-feu</span>',
            ],
            'keyword appearing multiple times is highlighted each time' => [
                'Porte porte PORTE',
                ['porte'],
                '<span class="highlight">Porte</span> <span class="highlight">porte</span> <span class="highlight">PORTE</span>',
            ],
        ];
    }
}

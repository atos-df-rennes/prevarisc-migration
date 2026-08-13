<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Utils\PathNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PathNormalizerTest extends TestCase
{
    #[DataProvider('provideNormalizationCases')]
    public function testNormalizationHandlesVariousInputFormats(string $input, string $expected): void
    {
        $result = PathNormalizer::normalize($input);

        $this->assertSame($expected, $result);
    }

    #[DataProvider('provideJoinCases')]
    public function testJoinCombinesPathSegmentsCorrectly(array $parts, string $expected): void
    {
        $result = PathNormalizer::join(...$parts);

        $this->assertSame($expected, $result);
    }

    public static function provideNormalizationCases(): iterable
    {
        // Linux paths
        yield 'linux path without trailing slash' => [
            '/data/documents',
            '/data/documents/',
        ];
        yield 'linux path with trailing slash' => [
            '/data/documents/',
            '/data/documents/',
        ];
        yield 'linux path with multiple trailing slashes' => [
            '/data/documents///',
            '/data/documents/',
        ];
        yield 'linux path with multiple consecutive slashes in middle' => [
            '/data//documents///uploads',
            '/data/documents/uploads/',
        ];

        // Windows paths with backslashes
        yield 'windows path with backslashes without trailing slash' => [
            'C:\\data\\documents',
            'C:/data/documents/',
        ];
        yield 'windows path with backslashes with trailing slash' => [
            'C:\\data\\documents\\',
            'C:/data/documents/',
        ];
        yield 'windows path with backslashes and multiple trailing slashes' => [
            'C:\\data\\documents\\\\\\',
            'C:/data/documents/',
        ];

        // Windows paths with forward slashes
        yield 'windows path with forward slashes without trailing slash' => [
            'C:/data/documents',
            'C:/data/documents/',
        ];
        yield 'windows path with forward slashes with trailing slash' => [
            'C:/data/documents/',
            'C:/data/documents/',
        ];

        // Mixed slashes (Windows + misconfigured)
        yield 'mixed forward and backslashes without trailing' => [
            'C:\\data/documents\\uploads',
            'C:/data/documents/uploads/',
        ];
        yield 'mixed forward and backslashes with trailing' => [
            'C:/data\\documents/uploads\\',
            'C:/data/documents/uploads/',
        ];

        // Edge cases
        yield 'empty string' => [
            '',
            '/',
        ];
        yield 'single slash' => [
            '/',
            '/',
        ];
        yield 'multiple slashes only' => [
            '///',
            '/',
        ];
        yield 'root windows drive' => [
            'C:\\',
            'C:/',
        ];
        yield 'root windows drive with forward slash' => [
            'C:/',
            'C:/',
        ];
    }

    public static function provideJoinCases(): iterable
    {
        // Simple Linux joins
        yield 'join two linux parts' => [
            ['/data/documents', '0'],
            '/data/documents/0',
        ];
        yield 'join three linux parts' => [
            ['/data/documents', '0', 'file.txt'],
            '/data/documents/0/file.txt',
        ];

        // Linux parts with slashes
        yield 'join linux parts with slashes in segments' => [
            ['/data/documents/', '0', 'file.txt'],
            '/data/documents/0/file.txt',
        ];

        // Windows joins
        yield 'join two windows parts' => [
            ['C:\\data\\documents', '0'],
            'C:/data/documents/0',
        ];
        yield 'join three windows parts' => [
            ['C:\\data\\documents', '0', 'file.txt'],
            'C:/data/documents/0/file.txt',
        ];

        // Windows parts with slashes
        yield 'join windows parts with trailing slash in first' => [
            ['C:\\data\\documents\\', '0', 'file.txt'],
            'C:/data/documents/0/file.txt',
        ];

        // Mixed slashes
        yield 'join mixed slash parts' => [
            ['C:/data\\documents', '0', 'file.txt'],
            'C:/data/documents/0/file.txt',
        ];

        // Commission directory pattern (what SDIS uses)
        yield 'join commission directory - linux' => [
            ['/uploads/documents/', '0'],
            '/uploads/documents/0',
        ];
        yield 'join commission directory - windows' => [
            ['C:\\uploads\\documents\\', '0'],
            'C:/uploads/documents/0',
        ];
        yield 'join commission directory - linux no trailing' => [
            ['/uploads/documents', '0'],
            '/uploads/documents/0',
        ];

        // Real-world document generation pattern
        yield 'join file path for document' => [
            ['/uploads/documents', '0', 'template.odt'],
            '/uploads/documents/0/template.odt',
        ];
        yield 'join file path for document - windows' => [
            ['C:\\uploads\\documents\\', '0', 'template.odt'],
            'C:/uploads/documents/0/template.odt',
        ];

        // Relative paths
        yield 'join relative paths' => [
            ['uploads/documents', '0'],
            'uploads/documents/0',
        ];

        // Single part
        yield 'single part returns without trailing slash' => [
            ['/data/documents/'],
            '/data/documents',
        ];

        // Empty parts handling
        yield 'join with multiple parts including numbers' => [
            ['/data', '0', '123', 'file.txt'],
            '/data/0/123/file.txt',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Utils\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    #[DataProvider('sanitizeSuccessProvider')]
    public function testSanitizeSuccess(string $input, string $expected): void
    {
        $this->assertSame($expected, File::sanitize($input));
    }

    public static function sanitizeSuccessProvider(): \Iterator
    {
        yield 'standard filename' => [
            'document.pdf',
            'document.pdf',
        ];
        yield 'filename with underscores' => [
            'mon_fichier_test.docx',
            'mon_fichier_test.docx',
        ];
        yield 'filename with dashes' => [
            'rapport-annuel-2025.xlsx',
            'rapport-annuel-2025.xlsx',
        ];
        yield 'filename with spaces' => [
            'mon document.txt',
            'mon document.txt',
        ];
        yield 'filename with special characters' => [
            'document_spécial-éàù@2025.pdf',
            'document_spécial-éàù@2025.pdf',
        ];
        yield 'filename with parentheses' => [
            'document(1).pdf',
            'document(1).pdf',
        ];
        yield 'filename with apostrophes' => [
            "document d'étude.docx",
            "document d'étude.docx",
        ];
        yield 'path traversal with slash - basename extracted' => [
            '/etc/passwd',
            'passwd',
        ];
        yield 'path traversal with directory - basename extracted' => [
            '../../../etc/passwd',
            'passwd',
        ];
        yield 'complex path - basename extracted' => [
            '/var/www/html/../config/database.yml',
            'database.yml',
        ];
        yield 'windows path - basename not extracted on Linux' => [
            'C:\\Windows\\System32\\config.ini',
            'C:\\Windows\\System32\\config.ini',
        ];
        yield 'mixed slashes - basename extracted' => [
            '../uploads/../../sensitive.txt',
            'sensitive.txt',
        ];
    }

    #[DataProvider('sanitizeExceptionProvider')]
    public function testSanitizeException(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom du fichier n'est pas correct.");

        File::sanitize($input);
    }

    public static function sanitizeExceptionProvider(): \Iterator
    {
        yield 'empty string' => [''];
        yield 'single dot' => ['.'];
        yield 'double dots' => ['..'];
        yield 'path with only dots' => ['../..'];
        yield 'path ending with dot' => ['somepath/.'];
        yield 'path ending with double dots' => ['somepath/..'];
    }

    #[DataProvider('sanitizeForContentDispositionProvider')]
    public function testSanitizeForContentDisposition(string $input, string $expected): void
    {
        $this->assertSame($expected, File::sanitizeForContentDisposition($input));
    }

    public static function sanitizeForContentDispositionProvider(): \Iterator
    {
        yield 'standard filename unchanged' => [
            'document.pdf',
            'document.pdf',
        ];
        yield 'filename with spaces unchanged' => [
            'mon document.txt',
            'mon document.txt',
        ];
        yield 'filename with special characters unchanged' => [
            'document_spécial-éàù@2025.pdf',
            'document_spécial-éàù@2025.pdf',
        ];
        yield 'filename with forward slash' => [
            'Devis 2024/2025.pdf',
            'Devis 2024-2025.pdf',
        ];
        yield 'filename with backslash' => [
            'dossier\\sous-dossier.pdf',
            'dossier-sous-dossier.pdf',
        ];
        yield 'filename with multiple forward slashes' => [
            'a/b/c.pdf',
            'a-b-c.pdf',
        ];
        yield 'filename with mixed slashes and backslashes' => [
            'a/b\\c.pdf',
            'a-b-c.pdf',
        ];
        yield 'filename that is only a slash' => [
            '/',
            '-',
        ];
        yield 'empty string' => [
            '',
            '',
        ];
    }
}

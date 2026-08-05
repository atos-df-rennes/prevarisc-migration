<?php

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

    public static function sanitizeSuccessProvider(): array
    {
        return [
            'standard filename' => [
                'document.pdf',
                'document.pdf',
            ],
            'filename with underscores' => [
                'mon_fichier_test.docx',
                'mon_fichier_test.docx',
            ],
            'filename with dashes' => [
                'rapport-annuel-2025.xlsx',
                'rapport-annuel-2025.xlsx',
            ],
            'filename with spaces' => [
                'mon document.txt',
                'mon document.txt',
            ],
            'filename with special characters' => [
                'document_spécial-éàù@2025.pdf',
                'document_spécial-éàù@2025.pdf',
            ],
            'filename with parentheses' => [
                'document(1).pdf',
                'document(1).pdf',
            ],
            'filename with apostrophes' => [
                "document d'étude.docx",
                "document d'étude.docx",
            ],
            'path traversal with slash - basename extracted' => [
                '/etc/passwd',
                'passwd',
            ],
            'path traversal with directory - basename extracted' => [
                '../../../etc/passwd',
                'passwd',
            ],
            'complex path - basename extracted' => [
                '/var/www/html/../config/database.yml',
                'database.yml',
            ],
            'windows path - basename not extracted on Linux' => [
                'C:\\Windows\\System32\\config.ini',
                'C:\\Windows\\System32\\config.ini',
            ],
            'mixed slashes - basename extracted' => [
                '../uploads/../../sensitive.txt',
                'sensitive.txt',
            ],
        ];
    }

    #[DataProvider('sanitizeExceptionProvider')]
    public function testSanitizeException(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom du fichier n'est pas correct.");

        File::sanitize($input);
    }

    public static function sanitizeExceptionProvider(): array
    {
        return [
            'empty string' => [''],
            'single dot' => ['.'],
            'double dots' => ['..'],
            'path with only dots' => ['../..'],
            'path ending with dot' => ['somepath/.'],
            'path ending with double dots' => ['somepath/..'],
        ];
    }
}

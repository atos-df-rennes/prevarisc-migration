<?php

namespace App\Tests\Service;

use App\Service\Documents;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class DocumentsTest extends TestCase
{
    /** @var string */
    private $tmpDir;

    /** @var Documents */
    private $documents;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/prevarisc_documents_'.uniqid();
        $filesystem = new Filesystem();
        $filesystem->mkdir([
            $this->tmpDir.'/documents/0',
            $this->tmpDir.'/documents/1/subdirectory',
            $this->tmpDir.'/documents-other',
        ]);
        foreach ([
            'documents/0/shared.odt',
            'documents/1/template.odt',
            'documents/1/Signatures membres sous commission.odt',
            'documents/1/not-a-template.txt',
            'documents/1/subdirectory/nested.odt',
            'documents-other/outside.odt',
        ] as $file) {
            $filesystem->touch($this->tmpDir.'/'.$file);
        }

        $this->documents = new Documents('/data/uploads/documents/', $this->tmpDir.'/documents/');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testListsOnlyImmediateOdtTemplates(): void
    {
        self::assertSame(['Signatures membres sous commission.odt', 'template.odt'], $this->documents->listReportTemplates(1));
        self::assertSame(['shared.odt'], $this->documents->listReportTemplates(0));
        self::assertSame([], $this->documents->listReportTemplates(2));
    }

    /**
     * @dataProvider validTemplateProvider
     */
    public function testResolvesListedTemplate(string $relativeFile): void
    {
        self::assertSame(
            realpath($this->tmpDir.'/documents/'.$relativeFile),
            $this->documents->resolveReportTemplate($relativeFile)
        );
    }

    public static function validTemplateProvider(): iterable
    {
        yield 'commission template' => ['1/template.odt'];
        yield 'shared template' => ['0/shared.odt'];
        yield 'spaces in filename' => ['1/Signatures membres sous commission.odt'];
    }

    /**
     * @dataProvider invalidTemplateProvider
     */
    public function testRejectsInvalidTemplate(string $relativeFile): void
    {
        self::assertNull($this->documents->resolveReportTemplate($relativeFile));
    }

    public static function invalidTemplateProvider(): iterable
    {
        yield 'empty selection' => [''];
        yield 'missing filename' => ['1'];
        yield 'empty filename' => ['1/'];
        yield 'non-numeric commission' => ['abc/template.odt'];
        yield 'missing commission' => ['2/template.odt'];
        yield 'missing file' => ['1/missing.odt'];
        yield 'wrong extension' => ['1/not-a-template.txt'];
        yield 'subdirectory' => ['1/subdirectory/nested.odt'];
        yield 'parent directory' => ['../documents-other/outside.odt'];
        yield 'nested traversal' => ['1/../../documents-other/outside.odt'];
        yield 'backslash traversal' => ['1/..\\..\\documents-other\\outside.odt'];
        yield 'absolute Unix path' => ['/1/template.odt'];
        yield 'absolute Windows path' => ['C:\\documents\\1\\template.odt'];
        yield 'UNC path' => ['\\\\192.168.1.1\\PREVENTION\\documents\\1\\template.odt'];
        yield 'null byte' => ["1/template.odt\0"];
        yield 'pattern is not a filename' => ['1/*.odt'];
    }

    public function testCanonicalizesConfiguredBaseDirectory(): void
    {
        $documents = new Documents('/data/', $this->tmpDir.'/documents/../documents');

        self::assertSame(
            realpath($this->tmpDir.'/documents/1/template.odt'),
            $documents->resolveReportTemplate('1/template.odt')
        );
    }

    public function testWindowsBaseWithDifferentCaseAndMixedSeparators(): void
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            self::markTestSkipped('Requires the Windows filesystem.');
        }

        $base = str_replace('/', '\\', $this->tmpDir).'/DOCUMENTS/';
        $documents = new Documents('/data/', $base);

        self::assertSame(
            realpath($this->tmpDir.'/documents/1/template.odt'),
            $documents->resolveReportTemplate('1/template.odt')
        );
    }

    public function testSymbolicLinksCannotEscapeTheLibrary(): void
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            self::markTestSkipped('Creating symbolic links on Windows requires elevated privileges.');
        }

        symlink($this->tmpDir.'/documents-other/outside.odt', $this->tmpDir.'/documents/1/outside.odt');
        symlink($this->tmpDir.'/documents-other', $this->tmpDir.'/documents/2');
        symlink($this->tmpDir.'/documents/1/template.odt', $this->tmpDir.'/documents/1/inside.odt');

        self::assertNull($this->documents->resolveReportTemplate('1/outside.odt'));
        self::assertNull($this->documents->resolveReportTemplate('2/outside.odt'));
        self::assertSame(
            realpath($this->tmpDir.'/documents/1/template.odt'),
            $this->documents->resolveReportTemplate('1/inside.odt')
        );
    }
}

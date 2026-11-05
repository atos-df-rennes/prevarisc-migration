<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\SearchExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

final class SearchExportTest extends TestCase
{
    public function testExportDossiersAddsReferenceColumnBeforeObjectAndShiftsRemainingColumns(): void
    {
        ob_start();
        new SearchExport()->exportDossiers([
            [
                'REFCOURRIER_DOSSIER' => 'REF-2026-01',
                'OBJET_DOSSIER' => 'Objet du dossier',
                'ID_PLATAU' => 'PLATAU-123',
                'ID_PIECEJOINTE' => 1,
            ],
        ], '1');
        $ods = (string) ob_get_clean();

        $temporaryFile = tempnam(sys_get_temp_dir(), 'prevarisc-export-');
        if (false === $temporaryFile) {
            self::fail('Unable to create a temporary ODS file.');
        }

        try {
            file_put_contents($temporaryFile, $ods);
            $sheet = IOFactory::createReader('Ods')->load($temporaryFile)->getActiveSheet();

            $this->assertSame('Référence courrier', $sheet->getCell('M1')->getValue());
            $this->assertSame('Objet du dossier', $sheet->getCell('N1')->getValue());
            $this->assertSame('REF-2026-01', $sheet->getCell('M2')->getValue());
            $this->assertSame('Objet du dossier', $sheet->getCell('N2')->getValue());
            $this->assertSame('Identifiant PLATAU', $sheet->getCell('W1')->getValue());
            $this->assertSame('PLATAU-123', $sheet->getCell('W2')->getValue());
        } finally {
            unlink($temporaryFile);
        }
    }
}

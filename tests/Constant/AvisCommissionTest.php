<?php

namespace App\Tests\Constant;

use App\Constant\AvisCommission;
use PHPUnit\Framework\TestCase;

class AvisCommissionTest extends TestCase
{
    /**
     * @dataProvider avisProvider
     *
     * @param int|string|null $code
     */
    public function testGetLibelleRetourneLeLibelleCorrect($code, string $expected): void
    {
        self::assertSame($expected, AvisCommission::getLibelle($code));
    }

    /**
     * @return array<string, array{0: int|string|null, 1: string}>
     */
    public function avisProvider(): array
    {
        return [
            'favorable' => [1, 'Favorable'],
            'defavorable' => [2, 'Défavorable'],
            'favorable avec presc' => [3, 'Favorable avec prescriptions'],
            'code string 1' => ['1', 'Favorable'],
            'code string 2' => ['2', 'Défavorable'],
            'code inconnu 0' => [0, ''],
            'code inconnu 99' => [99, ''],
            'null' => [null, ''],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Constant;

use App\Constant\AvisCommission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AvisCommissionTest extends TestCase
{
    #[DataProvider('avisProvider')]
    public function testGetLibelleRetourneLeLibelleCorrect(int|string|null $code, string $expected): void
    {
        $this->assertSame($expected, AvisCommission::getLibelle($code));
    }

    /**
     * @return \Iterator<string, array{(int | string | null), string}>
     */
    public static function avisProvider(): \Iterator
    {
        yield 'favorable' => [1, 'Favorable'];
        yield 'defavorable' => [2, 'Défavorable'];
        yield 'favorable avec presc' => [3, 'Favorable avec prescriptions'];
        yield 'code string 1' => ['1', 'Favorable'];
        yield 'code string 2' => ['2', 'Défavorable'];
        yield 'code inconnu 0' => [0, ''];
        yield 'code inconnu 99' => [99, ''];
        yield 'null' => [null, ''];
    }
}

<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\BooleanStringTransformer;
use PHPUnit\Framework\TestCase;

/**
 * Teste le contrat bool ↔ string du BooleanStringTransformer.
 */
final class BooleanStringTransformerTest extends TestCase
{
    private BooleanStringTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new BooleanStringTransformer();
    }

    // =========================================================================
    // transform (bool → string)
    // =========================================================================
    /**
     * @dataProvider transformProvider
     */
    public function testTransform(?bool $input, ?string $expected): void
    {
        self::assertSame($expected, $this->transformer->transform($input));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function transformProvider(): array
    {
        return [
            'true → "1"' => [true, '1'],
            'false → "0"' => [false, '0'],
            'null → null' => [null, null],
        ];
    }

    // =========================================================================
    // reverseTransform (string → bool)
    // =========================================================================
    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform(?string $input, bool $expected): void
    {
        self::assertSame($expected, $this->transformer->reverseTransform($input));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function reverseTransformProvider(): array
    {
        return [
            '"1" → true' => ['1', true],
            '"0" → false' => ['0', false],
            'null → false' => [null, false],
            '"" → false' => ['', false],
        ];
    }
}

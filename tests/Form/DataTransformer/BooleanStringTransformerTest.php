<?php

declare(strict_types=1);

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\BooleanStringTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
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
    #[DataProvider('transformProvider')]
    public function testTransform(?bool $input, ?string $expected): void
    {
        $this->assertSame($expected, $this->transformer->transform($input));
    }

    /**
     * @return \Iterator<string, array<mixed>>
     */
    public static function transformProvider(): \Iterator
    {
        yield 'true → "1"' => [true, '1'];
        yield 'false → "0"' => [false, '0'];
        yield 'null → null' => [null, null];
    }

    // =========================================================================
    // reverseTransform (string → bool)
    // =========================================================================
    #[DataProvider('reverseTransformProvider')]
    public function testReverseTransform(?string $input, bool $expected): void
    {
        $this->assertSame($expected, $this->transformer->reverseTransform($input));
    }

    /**
     * @return \Iterator<string, array<mixed>>
     */
    public static function reverseTransformProvider(): \Iterator
    {
        yield '"1" → true' => ['1', true];
        yield '"0" → false' => ['0', false];
        yield 'null → false' => [null, false];
        yield '"" → false' => ['', false];
    }
}

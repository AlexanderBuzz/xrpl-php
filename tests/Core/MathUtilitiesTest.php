<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\MathUtilities;

/**
 * The decimal helpers behind Amount.
 *
 * They had no tests of their own, which mattered when brick/math deprecated
 * BigDecimal::getIntegralPart() and getFractionalPart() - 0.15 removes them and
 * 0.16 brings them back with a different meaning. The values below were
 * captured from the implementation that used them, so the replacement is held
 * to the same behaviour rather than to what the behaviour arguably should be.
 *
 * Two of those quirks are deliberate here: getBigDecimalPrecision() counts the
 * leading zero of a value below 1, and returns 0 for zero itself.
 */
final class MathUtilitiesTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: int, 2: int, 3: int, 4: string}>
     */
    public static function decimalProvider(): array
    {
        // value, precision, precision incl. zeros, exponent, trimmed
        return [
            'zero' => ['0', 0, 1, -1, '0'],
            'one' => ['1', 1, 1, 0, '1'],
            'ten' => ['10', 1, 2, 1, '10'],
            'thousand' => ['1000', 1, 4, 3, '1000'],
            'tenth' => ['0.1', 2, 2, -1, '0.1'],
            'thousandth' => ['0.001', 4, 4, -3, '0.001'],
            'mixed' => ['123.456', 6, 6, 2, '123.456'],
            'sixteen digits' => ['1111111111111111', 16, 16, 15, '1111111111111111'],
            'negative integer' => ['-2', 1, 1, 0, '-2'],
            'negative decimal' => ['-12.34567', 7, 7, 1, '-12.34567'],
            'tiny' => ['1e-20', 21, 21, -20, '0.00000000000000000001'],
            'small fraction' => ['0.0000123', 8, 8, -5, '0.0000123'],
            'quadrillion' => ['1000000000000000', 1, 16, 15, '1000000000000000'],
        ];
    }

    #[DataProvider('decimalProvider')]
    public function testPrecision(string $value, int $precision): void
    {
        $this->assertEquals($precision, MathUtilities::getBigDecimalPrecision(BigDecimal::of($value)));
    }

    #[DataProvider('decimalProvider')]
    public function testPrecisionIncludingZeros(string $value, int $precision, int $withZeros): void
    {
        $this->assertEquals(
            $withZeros,
            MathUtilities::getBigDecimalPrecision(BigDecimal::of($value), true)
        );
    }

    #[DataProvider('decimalProvider')]
    public function testExponent(string $value, int $precision, int $withZeros, int $exponent): void
    {
        $this->assertEquals($exponent, MathUtilities::getBigDecimalExponent(BigDecimal::of($value)));
    }

    #[DataProvider('decimalProvider')]
    public function testTrimAmountZeros(
        string $value,
        int $precision,
        int $withZeros,
        int $exponent,
        string $trimmed
    ): void {
        $this->assertEquals($trimmed, MathUtilities::trimAmountZeros(BigDecimal::of($value)));
    }

    /**
     * A whole number is rendered without a fractional part - a trailing ".0"
     * is not what rippled or the reference SDKs produce.
     */
    public function testTrimAmountZerosDropsAnEmptyFraction(): void
    {
        $this->assertEquals('10000', MathUtilities::trimAmountZeros(BigDecimal::of('10000.000')));
        $this->assertEquals('10000.5', MathUtilities::trimAmountZeros(BigDecimal::of('10000.500')));
    }
}

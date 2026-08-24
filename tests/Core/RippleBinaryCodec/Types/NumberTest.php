<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Number;

/**
 * STNumber: 12 bytes, a signed 64 bit mantissa followed by a signed 32 bit
 * exponent, both big endian.
 *
 * The reference fixtures of ripple-binary-codec exercise the arithmetic through
 * two VaultCreate transactions, so the values below are taken from there. What
 * those fixtures do not reach is the canonical zero and the threshold between
 * decimal and scientific rendering, which is what the rest of this file covers.
 *
 * JavaScript:
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/src/types/st-number.ts
 */
final class NumberTest extends TestCase
{
    /**
     * Values that appear in the reference VaultCreate fixtures.
     */
    public static function referenceValueProvider(): array
    {
        return [
            '99e20' => ['99e20', '0DBD2FC137A3000000000004'],
            '9999999999999999e80' => ['9999999999999999e80', '0DE0B6B3A763FF9C0000004E'],
            'mantissa above 2^63-1' => ['9223372036854775900', '0CCCCCCCCCCCCCD600000001'],
            'decimal fraction' => ['12347865.746832746', '1122D7D8F56AFD68FFFFFFF5'],
            'large integer' => ['9323372036854775800', '0CF053BF3C8DCCCC00000001'],
        ];
    }

    #[DataProvider('referenceValueProvider')]
    public function testEncode(string $value, string $hex): void
    {
        $this->assertEquals($hex, Number::fromJson($value)->toHex());
    }

    #[DataProvider('referenceValueProvider')]
    public function testDecode(string $value, string $hex): void
    {
        $this->assertEquals($value, Number::fromHex($hex)->toJson());
    }

    /**
     * rippled encodes zero as mantissa 0 with the exponent -2147483648, not as
     * twelve zero bytes.
     */
    public function testCanonicalZero(): void
    {
        $this->assertEquals('000000000000000080000000', Number::fromJson('0')->toHex());
        $this->assertEquals('0', Number::fromHex('000000000000000080000000')->toJson());
    }

    public function testNegativeValue(): void
    {
        $this->assertEquals('F21F494C589C0000FFFFFFEE', Number::fromJson('-1')->toHex());
        $this->assertEquals('-1', Number::fromHex('F21F494C589C0000FFFFFFEE')->toJson());
    }

    /**
     * rippled renders decimal for exponents between -28 and -8 and switches to
     * scientific notation outside of that band.
     */
    public static function renderingProvider(): array
    {
        return [
            'integer stays decimal' => ['1'],
            'exponent -27 stays decimal' => ['0.000000001'],
            'exponent -38 goes scientific' => ['1e-20'],
            'large exponent goes scientific' => ['99e20'],
        ];
    }

    #[DataProvider('renderingProvider')]
    public function testRenderingRoundtrip(string $value): void
    {
        $this->assertEquals($value, Number::fromHex(Number::fromJson($value)->toHex())->toJson());
    }

    public function testRejectsNonNumericString(): void
    {
        $this->expectExceptionMessage('Unable to parse number from string');

        Number::fromJson('not a number');
    }

    public function testRejectsWrongByteLength(): void
    {
        $this->expectExceptionMessage('Invalid Number length');

        new Number(\Hardcastle\Buffer\Buffer::alloc(8));
    }

    public function testDefaultIsTwelveZeroBytes(): void
    {
        $this->assertEquals('000000000000000000000000', (new Number())->toHex());
    }
}

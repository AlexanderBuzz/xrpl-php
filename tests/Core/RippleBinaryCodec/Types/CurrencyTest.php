<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Currency;

/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/types/CurrencyTypeTest.java
 *
 * XRPL.JS
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/test/hash.test.js
 */
final class CurrencyTest extends TestCase
{
    public function testDecodeIso3(): void
    {
        $this->assertEquals(
            "XRP",
            Currency::fromHex("0000000000000000000000000000000000000000")->toJson()
        );

        $this->assertEquals(
            "USD",
            Currency::fromHex("0000000000000000000000005553440000000000")->toJson()
        );

        $this->assertEquals(
            "xSD",
            Currency::fromHex("0000000000000000000000007853440000000000")->toJson()
        );
    }

    public function testEncodeIso3(): void
    {
        $this->assertEquals(
            "0000000000000000000000000000000000000000",
            Currency::fromJson("XRP")->toHex()
        );

        $this->assertEquals(
            "0000000000000000000000005553440000000000",
            Currency::fromJson("USD")->toHex()
        );

        $this->assertEquals(
            "0000000000000000000000007853440000000000",
            Currency::fromJson("xSD")->toHex()
        );
    }

    public function testDecodeCustom(): void
    {
        $customCode = str_pad("", 40,"11", STR_PAD_RIGHT);
        $this->assertEquals(
            $customCode,
            Currency::fromHex($customCode)->toHex()
        );
    }

    public function testEncodeCustom(): void
    {
        $customCode = str_pad("", 40, "11", STR_PAD_RIGHT);
        $this->assertEquals(
            $customCode,
            Currency::fromJson($customCode)->toHex()
        );
    }

    // https://xrpl.org/currency-formats.html#currency-formats
    //
    //public function testInvalidCurrencyType(): void
    //{
    //    $customCode = str_pad("00", 40, "11", STR_PAD_RIGHT);
    //
    //    $this->expectExceptionMessage('Unsupported Currency representation: ' . $customCode);
    //
    //    Currency::fromJson($customCode)->toHex();
    //}
}
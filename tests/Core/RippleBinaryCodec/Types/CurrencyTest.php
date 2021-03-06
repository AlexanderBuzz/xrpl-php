<?php declare(strict_types=1);

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
    private Currency $currency;

    protected function setUp(): void
    {
        $this->currency = new Currency();
    }

   public function testDecodeIso3()
   {
       $this->assertEquals(
           "XRP",
           $this->currency->fromHex("0000000000000000000000000000000000000000")->toJson()
       );

       $this->assertEquals(
           "USD",
           $this->currency->fromHex("0000000000000000000000005553440000000000")->toJson()
       );

       $this->assertEquals(
           "xSD",
           $this->currency->fromHex("0000000000000000000000007853440000000000")->toJson()
       );
   }

    public function testEncodeIso3()
    {
        $this->assertEquals(
            "0000000000000000000000000000000000000000",
            $this->currency->fromSerializedJson("XRP")->toHex()
        );

        $this->assertEquals(
            "0000000000000000000000005553440000000000",
            $this->currency->fromSerializedJson("USD")->toHex()
        );

        $this->assertEquals(
            "0000000000000000000000007853440000000000",
            $this->currency->fromSerializedJson("xSD")->toHex()
        );
    }
}
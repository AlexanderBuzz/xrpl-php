<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;

/**
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/types/AmountTypeTest.java
 */
final class AmountTest extends TestCase
{
    //private static Amount $codec = new Amount();

    private Amount $amount;

    protected function setUp(): void
    {
        $this->amount = new Amount();
    }

    public function testDecodeXrpAmount(): void
    {
        $amount = new Amount();

        $this->assertEquals(
            "100",
            $amount->fromHex("4000000000000064")->toJson()
        );
        $this->assertEquals(
            "100000000000000000",
            $amount->fromHex("416345785D8A0000")->toJson()
        );
    }

    public function testEncodeXrpAmount(): void
    {
        $amount = new Amount();

        $this->assertEquals(
            "4000000000000064",
            $amount->fromSerializedJson("100")->toHex()
        );
        $this->assertEquals(
            "416345785D8A0000",
            $amount->fromSerializedJson("100000000000000000")->toHex()
        );
    }

    /*
    public function testEncodeOutOfBounds(): void
    {
        $amount = $this->createMock(Amount::class);

        $this->expectException("Exception");
        //$this->expectExceptionCode(100);
        //$this->expectExceptionMessage("Cannot divide by zero");

        $amount->fromSerializedJson("416345785D8A0001");
    }

    public function testEncodeCurrencyAmount(): void
    {
        $amount = $this->createMock(Amount::class);

        $var = \XRPL_PHP\Core\RippleBinaryCodec\Types\JsonObject::
        $this->assertEquals(
            "800000000000000000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            $amount->fromJson()->toHex()
        );
    }

    public function testDecodeCurrencyAmount(): void
    {

    }

    public function testDecodeNegativeCurrencyAmount(): void
    {

    }

    public function testEncodeZeroCurrencyAmount(): void
    {

    }
    */
}
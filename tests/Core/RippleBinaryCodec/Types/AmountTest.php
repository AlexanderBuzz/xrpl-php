<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;

/**
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/types/AmountTypeTest.java
 */
final class AmountTest extends TestCase
{
    public function testDecodeXrpAmount(): void
    {
        $this->assertEquals(
            "100",
            Amount::fromHex("4000000000000064")->toJson()
        );
        $this->assertEquals(
            "100000000000000000",
            Amount::fromHex("416345785D8A0000")->toJson()
        );
    }

    public function testEncodeXrpAmount(): void
    {
        $this->assertEquals(
            "4000000000000064",
            Amount::fromJson("100")->toHex()
        );
        $this->assertEquals(
            "416345785D8A0000",
            Amount::fromJson("100000000000000000")->toHex()
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

    */

    public function testEncodeCurrencyAmount(): void
    {
        $json = "{\"value\":\"0.0000123\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "D3445EADB112E00000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"0.1\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "D4438D7EA4C6800000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"0\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "800000000000000000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"1\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "D4838D7EA4C6800000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"200\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "D5071AFD498D000000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"-2\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "94871AFD498D000000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"-200\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "95071AFD498D000000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"2.1\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "D48775F05A07400000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"123.456\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "D50462D36641000000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"211.0000123\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "D5077F08AFCEB4C000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );

        $json = "{\"value\":\"-12.34567\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";
        $this->assertEquals(
            "94C462D5077C860000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44",
            Amount::fromJson($json)->toHex()
        );
    }

    public function testDecodeCurrencyAmount(): void
    {
        $hex = "D48775F05A07400000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44";
        $json = "{\"value\":\"2.1\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );

        $hex = "D5077F08AFCEB4C000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44";
        $json = "{\"value\":\"211.0000123\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );
    }

    public function testDecodeNegativeCurrencyAmount(): void
    {
        $hex = "94C462D5077C860000000000000000000000000055534400000000008B1CE810C13D6F337DAC85863B3D70265A24DF44";
        $json = "{\"value\":\"-12.34567\",\"currency\":\"USD\",\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );
    }

    public function testEncodeZeroCurrencyAmount(): void
    {
        $hex = "800000000000000000000000000000000000000055534400000000000000000000000000000000000000000000000001";
        $json = "{\"currency\":\"USD\",\"value\":\"0.0\",\"issuer\":\"rrrrrrrrrrrrrrrrrrrrBZbvji\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );
    }

    public function testEncodeLargeCurrencyAmount(): void
    {
        $hex = "D843F28CB71571C700000000000000000000000055534400000000000000000000000000000000000000000000000001";
        $json = "{\"currency\":\"USD\",\"value\":\"1111111111111111.0\",\"issuer\":\"rrrrrrrrrrrrrrrrrrrrBZbvji\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );
    }
}
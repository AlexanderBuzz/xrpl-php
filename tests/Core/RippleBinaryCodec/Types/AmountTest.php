<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;

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

    public function testDecodeZeroCurrencyAmount(): void
    {
        $hex = "800000000000000000000000000000000000000055534400000000000000000000000000000000000000000000000001";
        $json = "{\"currency\":\"USD\",\"value\":\"0\",\"issuer\":\"rrrrrrrrrrrrrrrrrrrrBZbvji\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );
    }

    public function testEncodeLargeCurrencyAmount(): void
    {
        $hex = "D843F28CB71571C700000000000000000000000055534400000000000000000000000000000000000000000000000001";
        $json = "{\"currency\":\"USD\",\"value\":\"1111111111111111\",\"issuer\":\"rrrrrrrrrrrrrrrrrrrrBZbvji\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );
    }

    public function testEdgeCases(): void
    {
        $hex = "EC2386F26FC0FFFF00000000000000000000000058504D000000000005BF25234D58ED48A3E44BB7F3D39AA7834A2905";
        $json = "{\"currency\":\"XPM\",\"value\":\"99999999999999990000000000000000000000000000000000000000000000000000000000000000000000000000000\",\"issuer\":\"rXPMxBeefHGxx2K7g5qmmWq3gFsgawkoa\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );

        $hex = "D82386F26FC0FFF65045575045570000000000000000000000000000797EF64BE4DC6DCBFAF5A993E28765441EB1C802";
        $json = "{\"currency\":\"5045575045570000000000000000000000000000\",\"value\":\"999999999999999\",\"issuer\":\"rUnQi6wgpPEFxJ4qJA8jJJZ8HeLtVjNBCV\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );

        $hex = "D8438D7EA4C680005045575045570000000000000000000000000000797EF64BE4DC6DCBFAF5A993E28765441EB1C802";
        $json = "{\"currency\":\"5045575045570000000000000000000000000000\",\"value\":\"1000000000000000\",\"issuer\":\"rUnQi6wgpPEFxJ4qJA8jJJZ8HeLtVjNBCV\"}";

        $this->assertEquals(
            json_decode($json, true),
            Amount::fromHex($hex)->toJson()
        );
    }


    // --- MPT amounts (MPTokensV1) ----------------------------------------
    //
    // An MPT amount is 33 bytes: a leading byte with bit 0x20 set to mark it as
    // MPT and 0x40 for a positive value, then a 64 bit value, then the 24 byte
    // MPTokenIssuanceID. The reference fixtures of ripple-binary-codec predate
    // the amendment and contain no MPT amount at all.

    private const MPT_ISSUANCE_ID = '000004C463C52827307480341125DA0577DEFC38405B0E3E';

    public function testEncodeMptAmount(): void
    {
        $json = json_encode([
            'mpt_issuance_id' => self::MPT_ISSUANCE_ID,
            'value' => '10',
        ]);

        $this->assertEquals(
            '60000000000000000A' . self::MPT_ISSUANCE_ID,
            Amount::fromJson($json)->toHex()
        );
    }

    public function testDecodeMptAmount(): void
    {
        $hex = '60000000000000000A' . self::MPT_ISSUANCE_ID;

        $this->assertEquals(
            [
                'value' => '10',
                'mpt_issuance_id' => self::MPT_ISSUANCE_ID,
            ],
            Amount::fromHex($hex)->toJson()
        );
    }

    public function testMptAmountRoundtrip(): void
    {
        foreach (['0', '1', '9223372036854775807'] as $value) {
            $json = json_encode([
                'mpt_issuance_id' => self::MPT_ISSUANCE_ID,
                'value' => $value,
            ]);

            $decoded = Amount::fromHex(Amount::fromJson($json)->toHex())->toJson();

            $this->assertEquals($value, $decoded['value']);
            $this->assertEquals(self::MPT_ISSUANCE_ID, $decoded['mpt_issuance_id']);
        }
    }

    /**
     * An MPT amount is an integer count of the smallest unit, so a decimal
     * point is not a rounding question but an invalid amount.
     */
    public function testMptAmountRejectsDecimals(): void
    {
        $this->expectExceptionMessage('is an illegal amount');

        Amount::fromJson(json_encode([
            'mpt_issuance_id' => self::MPT_ISSUANCE_ID,
            'value' => '10.5',
        ]));
    }

    public function testMptAmountRejectsNegativeValue(): void
    {
        $this->expectExceptionMessage('is an illegal amount');

        Amount::fromJson(json_encode([
            'mpt_issuance_id' => self::MPT_ISSUANCE_ID,
            'value' => '-1',
        ]));
    }

    /**
     * The most significant bit of the 64 bit value is reserved.
     */
    public function testMptAmountRejectsValueAboveMaxInt64(): void
    {
        $this->expectExceptionMessage('is an illegal amount');

        Amount::fromJson(json_encode([
            'mpt_issuance_id' => self::MPT_ISSUANCE_ID,
            'value' => '9223372036854775808',
        ]));
    }

    /**
     * XRP, IOU and MPT are told apart by the two top bits of the first byte.
     * MPT and XRP both have 0x80 clear, so 0x20 is what separates them.
     */
    public function testMptIsNotMistakenForXrp(): void
    {
        $mpt = Amount::fromHex('60000000000000000A' . self::MPT_ISSUANCE_ID)->toJson();
        $xrp = Amount::fromHex('400000000000000A')->toJson();

        $this->assertIsArray($mpt);
        $this->assertIsString($xrp);
    }

    public function testRejectsUnknownAmountObject(): void
    {
        $this->expectExceptionMessage('Invalid type to construct an Amount');

        Amount::fromJson(json_encode(['value' => '10', 'nonsense' => 'x']));
    }
}

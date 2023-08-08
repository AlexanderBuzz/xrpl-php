<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

final class BinaryCodecTest extends TestCase
{
    public const SIMPLE_JSON = ["CloseResolution" => 1,  "Method" => 2];
    public const SINGLE_LEVEL_OBJECT_JSON = "{\"Memo\":{\"Memo\":{\"Method\":2}}}";
    public const MULTI_LEVEL_OBJECT_JSON = "{\"Memo\":{\"Memo\":{\"CloseResolution\":1,\"Method\":2}}}";

    public const SIMPLE_HEX = "011001021002";
    public const SINGLE_OBJECT_HEX = "EAEA021002E1E1";
    public const MULTI_LEVEL_OBJECT_HEX = "EAEA011001021002E1E1";

    /** @psalm-suppress PropertyNotSetInConstructor */
    private array $fixtures;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private BinaryCodec $binaryCodec;

    protected function setUp(): void
    {
        $raw = file_get_contents(__DIR__ . "/fixtures.json");
        $this->fixtures = json_decode($raw, true);

        $this->binaryCodec = new BinaryCodec();
    }

    //https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/XrplBinaryCodecTest.java


    public function testEncodeDecodeSimple(): void
    {
        $this->assertEquals(
            self::SIMPLE_HEX,
            $this->binaryCodec->encode(self::SIMPLE_JSON)
        );

        $this->assertEquals(
            self::SIMPLE_JSON,
            $this->binaryCodec->decode(self::SIMPLE_HEX)
        );
    }

    public function testEncodeForSigning(): void
    {
        $json =
        "{\"Account\":\"r45dBj4S3VvMMYXxr9vHX4Z4Ma6ifPMCkK\",\"TransactionType\":\"Payment\",\"Fee\":\"789\"," .
        "\"Sequence\":1,\"Flags\":2147614720,\"SourceTag\":1," .
        "\"Amount\":{\"value\":\"1234567890123456\",\"currency\":\"USD\"," .
        "\"issuer\":\"rDgZZ3wyprx4ZqrGQUkquE9Fs2Xs8XBcdw\"}," .
        "\"Destination\":\"rrrrrrrrrrrrrrrrrrrrBZbvji\",\"DestinationTag\":2," .
        "\"SigningPubKey\":\"ED5F5AC8B98974A3CA843326D9B88CEBD0560177B973EE0B149F782CFAA06DC66A\"," .
        "\"TxnSignature\": \"12345678\"}";

    // expected value obtained by calling encodeForSigning(json) from ripple-binary-codec
    $expected =
        "535458001200002280020000230000000124000000012E0000000261D84462D53C8ABAC00000000000000000000000005553440000000" .
        "0008B1CE810C13D6F337DAC85863B3D70265A24DF446840000000000003157321ED5F5AC8B98974A3CA843326D9B88CEB" .
        "D0560177B973EE0B149F782CFAA06DC66A8114EE39E6D05CFD6A90DAB700A1D70149ECEE29DFEC83140000000000000000" .
        "000000000000000000000001";

        $this->assertNotEquals(
            $this->binaryCodec->encode($json),
            $this->binaryCodec->encodeForSigning($json)
        );

        $this->assertEquals(
            $expected,
            $this->binaryCodec->encodeForSigning($json)
        );
    }

}
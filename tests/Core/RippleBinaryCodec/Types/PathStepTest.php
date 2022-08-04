<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\PathStep;

/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/types/BlobTypeTest.java
 *
 * XRPL.JS
 * untested...
 */
final class PathStepTest extends TestCase
{
    private string $json;

    private string $hex;

    protected function setUp(): void
    {
        $this->json = json_encode([
            "account" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
            "currency" => "BTC",
            "issuer" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
        ]);

        $this->hex = "31585e1f3bd02a15d6185f8bb9b57cc60deddb37c10000000000000000000000004254430000000000585e1f3bd02a15d6185f8bb9b57cc60deddb37c1";
    }

    /*
    public function testDecode()
    {

    }


    public function testEncode()
    {
        $this->assertEquals(
            $this->hex,
            PathStep::fromSerializedJson($this->json)->toHex()
        );
    }
    */
}
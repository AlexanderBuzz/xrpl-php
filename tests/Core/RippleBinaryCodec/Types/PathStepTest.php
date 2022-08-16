<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
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
    private array $json;

    private string $hex;

    protected function setUp(): void
    {
        $this->json = [
            "account" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
            "currency" => "BTC",
            "issuer" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
        ];

        $this->hex = "31585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C10000000000000000000000004254430000000000585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C1";
    }


    public function testDecode()
    {
        $this->assertEquals(
            $this->json,
            PathStep::fromHex($this->hex)->toJson()
        );
    }

    public function testEncode()
    {
        $serializedJson = json_encode($this->json);
        $this->assertEquals(
            $this->hex,
            PathStep::fromJson($serializedJson)->toHex()
        );
    }
}
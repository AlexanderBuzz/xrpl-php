<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Issue;

final class IssueTest extends TestCase
{
    public function testDecode(): void
    {
        $hex = $this->getZeroBytes(20);
        $json = "{\"currency\":\"XRP\"}";

        $this->assertEquals(
            json_decode($json, true),
            Issue::fromHex($hex)->toJson()
        );

        $hex = "0000000000000000000000005453540000000000F2F97C4301C80D60F86653A319AA7F302C70B83B";
        $json = "{\"currency\":\"TST\",\"issuer\":\"rP9jPyP5kyvFRb6ZiRghAGw5u8SGAmU4bd\"}";

        $this->assertEquals(
            json_decode($json, true),
            Issue::fromHex($hex)->toJson()
        );
    }

    public function testEncode(): void
    {

        $hex = $this->getZeroBytes(20);
        $json = "{\"currency\":\"XRP\"}";

        $this->assertEquals(
            $hex,
            Issue::fromJson($json)->toHex()
        );

        $hex = "0000000000000000000000005453540000000000F2F97C4301C80D60F86653A319AA7F302C70B83B";
        $json = "{\"currency\":\"TST\",\"issuer\":\"rP9jPyP5kyvFRb6ZiRghAGw5u8SGAmU4bd\"}";

        $this->assertEquals(
            $hex,
            Issue::fromJson($json)->toHex()
        );
    }

    private function getZeroBytes(int $size): string
    {
        return str_repeat("00", $size);
    }
}
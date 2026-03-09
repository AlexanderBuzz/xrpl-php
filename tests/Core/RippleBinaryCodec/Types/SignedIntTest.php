<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\SignedInt32;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\SignedInt64;
use PHPUnit\Framework\TestCase;

class SignedIntTest extends TestCase
{
    public function testSignedInt32Encoding(): void
    {
        // Positive
        $this->assertEquals("00000005", SignedInt32::fromJson(5)->toHex());
        // Negative
        $this->assertEquals("FFFFFFFB", SignedInt32::fromJson(-5)->toHex());
        // Max
        $this->assertEquals("7FFFFFFF", SignedInt32::fromJson(2147483647)->toHex());
        // Min
        $this->assertEquals("80000000", SignedInt32::fromJson(-2147483648)->toHex());
    }

    public function testSignedInt32Decoding(): void
    {
        // Positive
        $this->assertEquals(5, SignedInt32::fromHex("00000005")->toJson());
        // Negative
        $this->assertEquals(-5, SignedInt32::fromHex("FFFFFFFB")->toJson());
        // Max
        $this->assertEquals(2147483647, SignedInt32::fromHex("7FFFFFFF")->toJson());
        // Min
        $this->assertEquals(-2147483648, SignedInt32::fromHex("80000000")->toJson());
    }

    public function testSignedInt64Encoding(): void
    {
        // Positive
        $this->assertEquals("0000000000000005", SignedInt64::fromJson(5)->toHex());
        $this->assertEquals("0000000000000005", SignedInt64::fromJson("5")->toHex());
        // Negative
        $this->assertEquals("FFFFFFFFFFFFFFFB", SignedInt64::fromJson(-5)->toHex());
        $this->assertEquals("FFFFFFFFFFFFFFFB", SignedInt64::fromJson("-5")->toHex());
        // Max (9223372036854775807)
        $this->assertEquals("7FFFFFFFFFFFFFFF", SignedInt64::fromJson("9223372036854775807")->toHex());
        // Min (-9223372036854775808)
        $this->assertEquals("8000000000000000", SignedInt64::fromJson("-9223372036854775808")->toHex());
    }

    public function testSignedInt64Decoding(): void
    {
        // Positive
        $this->assertEquals("5", SignedInt64::fromHex("0000000000000005")->toJson());
        // Negative
        $this->assertEquals("-5", SignedInt64::fromHex("FFFFFFFFFFFFFFFB")->toJson());
        // Max
        $this->assertEquals("9223372036854775807", SignedInt64::fromHex("7FFFFFFFFFFFFFFF")->toJson());
        // Min
        $this->assertEquals("-9223372036854775808", SignedInt64::fromHex("8000000000000000")->toJson());
    }
}

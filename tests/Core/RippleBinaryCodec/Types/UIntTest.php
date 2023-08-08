<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use Brick\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt8;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt16;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt64;

final class UIntTest extends TestCase
{
    /** @psalm-suppress UndefinedMethod */
    public function testDecodeUInt8(): void
    {
        $this->assertEquals(
            0,
            UnsignedInt8::fromHex('00')->valueOf()
        );

        $this->assertEquals(
            15,
            UnsignedInt8::fromHex('0F')->valueOf()
        );

        $this->assertEquals(
            255,
            UnsignedInt8::fromHex('FF')->valueOf()
        );
    }

    public function testEncodeUInt8(): void
    {
        $this->assertEquals(
            '00',
            UnsignedInt8::fromJson(0)->toString()
        );

        $this->assertEquals(
            '0F',
            UnsignedInt8::fromJson(15)->toString()
        );

        $this->assertEquals(
            'FF',
            UnsignedInt8::fromJson(255)->toString()
        );

        $this->assertEquals(
            'FF',
            UnsignedInt8::fromJson('255')->toString()
        );
    }

    /** @psalm-suppress UndefinedMethod */
    public function testDecodeUInt16(): void
    {
        $this->assertEquals(
            0,
            UnsignedInt16::fromHex('0000')->valueOf()
        );

        $this->assertEquals(
            15,
            UnsignedInt16::fromHex('000F')->valueOf()
        );

        $this->assertEquals(
            65535,
            UnsignedInt16::fromHex('FFFF')->valueOf()
        );
    }

    public function testEncodeUInt16(): void
    {
        $this->assertEquals(
            '0000',
            UnsignedInt16::fromJson(0)->toString()
        );

        $this->assertEquals(
            '000F',
            UnsignedInt16::fromJson(15)->toString()
        );

        $this->assertEquals(
            'FFFF',
            UnsignedInt16::fromJson(65535)->toString()
        );

        $this->assertEquals(
            '00FF',
            UnsignedInt16::fromJson('255')->toString()
        );
    }

    /** @psalm-suppress UndefinedMethod */
    public function testDecodeUInt32(): void
    {
        $this->assertEquals(
            0,
            UnsignedInt32::fromHex('00000000')->valueOf()
        );

        $this->assertEquals(
            15,
            UnsignedInt32::fromHex('0000000F')->valueOf()
        );

        $this->assertEquals(
            65535,
            UnsignedInt32::fromHex('0000FFFF')->valueOf()
        );

        $this->assertEquals(
            4294967295,
            UnsignedInt32::fromHex('FFFFFFFF')->valueOf()
        );
    }

    public function testEncodeUInt32(): void
    {
        $this->assertEquals(
            '00000000',
            UnsignedInt32::fromJson(0)->toString()
        );

        $this->assertEquals(
            '0000000F',
            UnsignedInt32::fromJson(15)->toString()
        );

        $this->assertEquals(
            '0000FFFF',
            UnsignedInt32::fromJson(65535)->toString()
        );

        $this->assertEquals(
            'FFFFFFFF',
            UnsignedInt32::fromJson(4294967295)->toString()
        );

        $this->assertEquals(
            '000000FF',
            UnsignedInt32::fromJson('255')->toString()
        );
    }

    /** @psalm-suppress UndefinedMethod */
    public function testDecodeUInt64(): void
    {
        $this->assertEquals(
            "0",
            UnsignedInt64::fromHex('0000000000000000')->valueOf()
        );

        $this->assertEquals(
            "15",
            UnsignedInt64::fromHex('000000000000000F')->valueOf()
        );

        $this->assertEquals(
            "65535",
            UnsignedInt64::fromHex('000000000000FFFF')->valueOf()
        );

        $this->assertEquals(
            "4294967295",
            UnsignedInt64::fromHex('00000000FFFFFFFF')->valueOf()
        );

        $bigInteger = BigInteger::fromBase('FFFFFFFFFFFFFFFF', 16);
        $this->assertEquals(
            "18446744073709551615",
            UnsignedInt64::fromHex('FFFFFFFFFFFFFFFF')->valueOf()
        );
    }

    public function testEncodeUInt64(): void
    {
        $this->assertEquals(
            '0000000000000000',
            UnsignedInt64::fromJson("0")->toString()
        );

        $this->assertEquals(
            '000000000000000F',
            UnsignedInt64::fromJson("15")->toString()
        );

        $this->assertEquals(
            '000000000000FFFF',
            UnsignedInt64::fromJson("65535")->toString()
        );

        $this->assertEquals(
            '00000000FFFFFFFF',
            UnsignedInt64::fromJson("4294967295")->toString()
        );

        $this->assertEquals(
            '00000000000000FF',
            UnsignedInt64::fromJson("255")->toString()
        );

        $this->assertEquals(
            'FFFFFFFFFFFFFFFF',
            UnsignedInt64::fromJson("18446744073709551615")->toString()
        );
    }
}
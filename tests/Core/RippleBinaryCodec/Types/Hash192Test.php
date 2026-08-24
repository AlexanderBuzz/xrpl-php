<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash192;

/**
 * 192 bit (24 byte) hash, used for MPTokenIssuanceID and ShareMPTID.
 *
 * JavaScript:
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/src/types/hash-192.ts
 */
final class Hash192Test extends TestCase
{
    private const VALUE = '000004C463C52827307480341125DA0577DEFC38405B0E3E';

    public function testWidth(): void
    {
        $this->assertEquals(24, (new Hash192())->getWidth());
    }

    public function testDecode(): void
    {
        $this->assertEquals(self::VALUE, Hash192::fromHex(self::VALUE)->toJson());
    }

    public function testEncode(): void
    {
        $this->assertEquals(self::VALUE, Hash192::fromJson(self::VALUE)->toHex());
    }

    public function testDefaultIsZero(): void
    {
        $this->assertEquals(str_repeat('0', 48), (new Hash192())->toHex());
    }

    /**
     * A Hash256 value in a Hash192 field would silently shift every following
     * field, so the length is enforced.
     */
    public function testRejectsWrongLength(): void
    {
        $this->expectExceptionMessage('Invalid hash length');

        new Hash192(Buffer::from(str_repeat('AB', 32), 'hex'));
    }

    public function testRejectsTooShortValue(): void
    {
        $this->expectExceptionMessage('Invalid hash length');

        new Hash192(Buffer::from('ABAB', 'hex'));
    }
}

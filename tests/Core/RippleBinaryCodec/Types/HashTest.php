<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash128;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash160;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/types/HashTypeTest.java
 *
 * XRPL.JS
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/test/hash.test.js
 */
final class HashTest extends TestCase
{
    public function testDecode()
    {
        $this->assertEquals(
            $this->getBytes(16),
            Hash128::fromHex($this->getBytes(16))->toHex()
        );

        $this->assertEquals(
            $this->getBytes(20),
            Hash160::fromHex($this->getBytes(20))->toHex()
        );

        $this->assertEquals(
            $this->getBytes(32),
            Hash256::fromHex($this->getBytes(32))->toHex()
        );
    }

    public function testEncode()
    {
        $this->assertEquals(
            $this->getBytes(16),
            Hash128::fromSerializedJson($this->getBytes(16))->toHex()
        );

        $this->assertEquals(
            $this->getBytes(20),
            Hash160::fromSerializedJson($this->getBytes(20))->toHex()
        );

        $this->assertEquals(
            $this->getBytes(32),
            Hash256::fromSerializedJson($this->getBytes(32))->toHex()
        );
    }

    private function getBytes(int $size): string
    {
        return str_repeat("0F", $size);
    }
}
<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;

/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/types/BlobTypeTest.java
 *
 * XRPL.JS
 * untested...
 */
final class BlobTest extends TestCase
{
    public function testDecode(): void
    {
        $width = 1;
        $this->assertEquals($this->getBytes($width), Blob::fromHex($this->getBytes($width))->toString());

        $width = 16;
        $this->assertEquals($this->getBytes($width), Blob::fromHex($this->getBytes($width))->toString());

        $width = 32;
        $this->assertEquals($this->getBytes($width), Blob::fromHex($this->getBytes($width))->toString());

        $width = 64;
        $this->assertEquals($this->getBytes($width), Blob::fromHex($this->getBytes($width))->toString());

        $width = 128;
        $this->assertEquals($this->getBytes($width), Blob::fromHex($this->getBytes($width))->toString());
    }

    public function testEncode(): void
    {
        $this->assertEquals($this->getBytes(16), Blob::fromJson($this->getBytes(16))->toHex());
    }

    private function getBytes(int $size): string
    {
        return str_repeat("0F", $size);
    }
}
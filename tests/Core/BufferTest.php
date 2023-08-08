<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\Buffer;

final class BufferTest extends TestCase
{
    public function testAlloc(): void
    {
        $numElements = 20;
        $buf = Buffer::alloc($numElements);

        $this->assertEquals($numElements, $buf->getLength());
    }

    public function testFrom(): void
    {
        // From byte array
        $buf = Buffer::from([12, 108, 0, 230]);
        $exp = '0C6C00E6';
        $this->assertEquals($exp, $buf->toString());

        // From hex
        $buf = Buffer::from('ff03a5ed', 'hex');
        $exp = [255, 3, 165, 237];
        $this->assertEquals($exp, $buf->toArray());

        $buf = Buffer::from('f03a5ed', 'hex');
        $exp = [15, 3, 165, 237];
        $this->assertEquals($exp, $buf->toArray());

        // From string - 'hello world'

        // From Bricks/BigInteger
    }

    public function testToUtf8(): void
    {
        $elephantBuf = Buffer::from([240, 159, 144, 152]);
        $elephantStr = '🐘';

        $this->assertEquals($elephantStr, $elephantBuf->toUtf8());
    }
}
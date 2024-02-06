<?php
declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test;

use PHPUnit\Framework\TestCase;

final class PolyfillTest extends TestCase
{
    public function setUp(): void
    {
        $this->needsBcmathExtension();
    }

    public function testBchexdec()
    {
        $this->assertEquals('18446744073709551615', bchexdec('FFFFFFFFFFFFFFFF'));
        $this->assertEquals('9223372036854775807', bchexdec('7FFFFFFFFFFFFFFF'));
        $this->assertEquals('9223372036854775808', bchexdec('8000000000000000'));
        $this->assertEquals('0', bchexdec('0'));
    }

    public function testBcdechex()
    {
        $this->assertEquals('FFFFFFFFFFFFFFFF', bcdechex('18446744073709551615'));
        $this->assertEquals('7FFFFFFFFFFFFFFF', bcdechex('9223372036854775807'));
        $this->assertEquals('8000000000000000', bcdechex('9223372036854775808'));
        $this->assertEquals('0', bcdechex('0'));
    }

    public function testHex2str(): void
    {
        $this->assertEquals('Hello World!', hex2str('48656c6c6f20576f726c6421'));
    }

    public function testStr2hex(): void
    {
        $this->assertEquals('48656c6c6f20576f726c6421', str2hex('Hello World!'));
    }

    private function needsBcmathExtension()
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('The bcmath extension is not available.');
        }
    }
}
<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PhpUnitTest extends TestCase
{
    public function testInitial(): void
    {
        $this->assertEquals(
            true,
            true
        );
    }

}
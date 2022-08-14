<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\Utilities;

class UtilitiesTest extends TestCase
{
    public function testSingletonUniqueness()
    {
        $firstCall = Utilities::getInstance();
        $secondCall = Utilities::getInstance();

        $this->assertInstanceOf(Utilities::class, $firstCall);
        $this->assertSame($firstCall, $secondCall);
    }
}
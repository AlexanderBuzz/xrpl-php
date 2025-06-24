<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core;

use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\CoreUtilities;

final class UtilitiesTest extends TestCase
{
    public function testSingletonUniqueness(): void
    {
        $firstCall = CoreUtilities::getInstance();
        $secondCall = CoreUtilities::getInstance();

        $this->assertInstanceOf(CoreUtilities::class, $firstCall);
        $this->assertSame($firstCall, $secondCall);
    }

    public function testEncodeCustomCurrency(): void
    {
        $customCurrency = "SOLO";
        $hash = CoreUtilities::encodeCustomCurrency($customCurrency);
        $this->assertEquals('534F4C4F00000000000000000000000000000000', $hash);
    }

    public function testDecodeCustomCurrency(): void
    {
        $hash = '534F4C4F00000000000000000000000000000000';
        $currency = CoreUtilities::decodeCustomCurrency($hash);
        $this->assertEquals('SOLO', $currency);
    }

}
<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Sugar;

use donatj\MockWebServer\MockWebServer;
use PHPUnit\Framework\TestCase;
use function Hardcastle\XRPL_PHP\Sugar\dropsToXrp;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

/**
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/xrpl/test/utils/dropsToXrp.ts
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/xrpl/test/utils/xrpToDrops.ts
 */
class BalancesTest  extends TestCase
{
    protected static MockWebServer $server;

    public static function setUpBeforeClass(): void {
        self::$server = new MockWebServer();
        self::$server->start();
    }

    public function testGetXrpBalance(): void
    {
        //TODO: Implement test
        $this->assertEquals(true, true);
    }

    public function testGetBalances(): void
    {
        //TODO: Implement test
        $this->assertEquals(true, true);
    }
}
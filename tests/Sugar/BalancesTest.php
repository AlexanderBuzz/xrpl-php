<?php

namespace XRPL_PHP\Test\Sugar;

use donatj\MockWebServer\MockWebServer;
use PHPUnit\Framework\TestCase;
use XRPL_PHP\Client\JsonRpcClient;
use function XRPL_PHP\Sugar\dropsToXrp;
use function XRPL_PHP\Sugar\xrpToDrops;

/**
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/xrpl/test/utils/dropsToXrp.ts
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/xrpl/test/utils/xrpToDrops.ts
 */
class BalancesTest  extends TestCase
{
    protected static MockWebServer $server;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private JsonRpcClient $client;

    public static function setUpBeforeClass(): void {
        self::$server = new MockWebServer();
        self::$server->start();
    }

    public function setUp(): void
    {
        $mockRippledUrl = self::$server->getServerRoot();
        $this->client = new JsonRpcClient($mockRippledUrl);
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
<?php

namespace Sugar;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use PHPUnit\Framework\TestCase;
use XRPL_PHP\Client\JsonRpcClient;
use function XRPL_PHP\Sugar\dropsToXrp;
use function XRPL_PHP\Sugar\xrpToDrops;

/**
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/xrpl/test/client/autofill.ts
 */

class AutofillTest  extends TestCase
{
    protected static MockWebServer $server;

    private const TESTNET_URL = "https://s.altnet.rippletest.net:51234";
    private const LOCAL_URL = "http://host.docker.internal:5005";

    private const Fee = '10';
    private const Sequence = 1432;
    private const LastLedgerSequence = 2908734;

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
    public function testAutofill(): void
    {
        //should not autofill if fields are present
        $tx = [
            "TransactionType" => 'DepositPreauth',
            "Account" => 'rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf',
            "Authorize" => 'rpZc4mVfWUif9CRoHRKKcmhu1nx2xktxBo',
            "Fee" => self::Fee,
            "Sequence" => self::Sequence,
            "LastLedgerSequence" => self::LastLedgerSequence,
        ];

        $autofillTx = $this->client->autofill($tx);

        $this->assertEquals(self::Fee, $autofillTx['Fee']);
        $this->assertEquals(self::Sequence, $autofillTx['Sequence']);
        $this->assertEquals(self::LastLedgerSequence, $autofillTx['LastLedgerSequence']);
    }
}
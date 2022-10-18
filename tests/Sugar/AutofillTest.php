<?php

namespace Sugar;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Client\JsonRpcClient;
use function XRPL_PHP\Sugar\dropsToXrp;
use function XRPL_PHP\Sugar\xrpToDrops;

/**
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/xrpl/test/client/autofill.ts
 */

class AutofillTest  extends TestCase
{
    private const TESTNET_URL = "https://s.altnet.rippletest.net:51234";
    private const LOCAL_URL = "http://host.docker.internal";

    private const Fee = '10';
    private const Sequence = 1432;
    private const LastLedgerSequence = 2908734;

    private JsonRpcClient $client;

    public function setUp(): void
    {
        $this->client = new JsonRpcClient(self::TESTNET_URL);
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

        $result = $this->client->autofill()->wait();
    }
}
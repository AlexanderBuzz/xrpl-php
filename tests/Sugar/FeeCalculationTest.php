<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Sugar;

use donatj\MockWebServer\MockWebServer;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Test\MockRippled\RpcMethodResponse;

/**
 * The transaction cost that autofill() puts on a transaction.
 *
 * Most types pay the network fee, but AccountDelete and AMMCreate burn one
 * owner reserve instead, and EscrowFinish pays a surcharge that scales with the
 * size of its fulfillment. Both special cases were broken until 2.0.0 and had
 * no test, because the fee path needs four different rippled responses and the
 * mock server used to route by URL path rather than by JSON-RPC method.
 */
final class FeeCalculationTest extends TestCase
{
    private const ACCOUNT = 'rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf';

    private const DESTINATION = 'rpZc4mVfWUif9CRoHRKKcmhu1nx2xktxBo';

    /** The owner reserve rippled reports, in drops */
    private const RESERVE_INC = '2000000';

    // rippled sends this as a JSON number, not a string
    private const BASE_FEE_XRP = 0.00001;

    private const LEDGER_INDEX = 2908714;

    private const SEQUENCE = 1432;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private static MockWebServer $server;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private JsonRpcClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$server = new MockWebServer();
        self::$server->start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    protected function setUp(): void
    {
        self::$server->setDefaultResponse(new RpcMethodResponse([
            'server_info' => [
                'info' => [
                    'validated_ledger' => ['base_fee_xrp' => self::BASE_FEE_XRP],
                    'load_factor' => 1,
                ],
            ],
            'server_state' => [
                'state' => ['validated_ledger' => ['reserve_inc' => self::RESERVE_INC]],
            ],
            'account_info' => [
                'account_data' => ['Sequence' => self::SEQUENCE],
            ],
            'ledger' => ['ledger_index' => self::LEDGER_INDEX],
            'account_objects' => ['account_objects' => []],
        ]));

        $this->client = new JsonRpcClient(self::$server->getServerRoot());
    }

    /**
     * base_fee_xrp 0.00001 XRP = 10 drops, times the default cushion of 1.2,
     * rounded up.
     */
    public function testOrdinaryTransactionPaysTheNetworkFee(): void
    {
        $tx = $this->client->autofill([
            'TransactionType' => 'Payment',
            'Account' => self::ACCOUNT,
            'Destination' => self::DESTINATION,
            'Amount' => '1000',
        ]);

        $this->assertEquals('12', $tx['Fee']);
    }

    /**
     * AMMCreate burns one owner reserve. Before 2.0.0 it got the network fee
     * and every AMMCreate was rejected with telINSUF_FEE_P.
     */
    public function testAmmCreatePaysTheOwnerReserve(): void
    {
        $tx = $this->client->autofill([
            'TransactionType' => 'AMMCreate',
            'Account' => self::ACCOUNT,
            'Amount' => '1000',
            'Amount2' => ['currency' => 'USD', 'issuer' => self::DESTINATION, 'value' => '10'],
            'TradingFee' => 10,
        ]);

        $this->assertEquals(self::RESERVE_INC, $tx['Fee']);
    }

    public function testAccountDeletePaysTheOwnerReserve(): void
    {
        $tx = $this->client->autofill([
            'TransactionType' => 'AccountDelete',
            'Account' => self::ACCOUNT,
            'Destination' => self::DESTINATION,
        ]);

        $this->assertEquals(self::RESERVE_INC, $tx['Fee']);
    }

    /**
     * The owner reserve is a protocol requirement, so maxFeeXrp must not cap
     * it - the default cap of 2 XRP is lower than many networks' reserve.
     */
    public function testOwnerReserveIsNotCappedByMaxFeeXrp(): void
    {
        $client = new JsonRpcClient(self::$server->getServerRoot(), null, '0.1');

        $tx = $client->autofill([
            'TransactionType' => 'AMMCreate',
            'Account' => self::ACCOUNT,
            'Amount' => '1000',
            'Amount2' => ['currency' => 'USD', 'issuer' => self::DESTINATION, 'value' => '10'],
            'TradingFee' => 10,
        ]);

        $this->assertEquals(self::RESERVE_INC, $tx['Fee']);
    }

    /**
     * The ordinary fee, in contrast, is capped.
     */
    public function testNetworkFeeIsCappedByMaxFeeXrp(): void
    {
        $client = new JsonRpcClient(self::$server->getServerRoot(), null, '0.000005');

        $tx = $client->autofill([
            'TransactionType' => 'Payment',
            'Account' => self::ACCOUNT,
            'Destination' => self::DESTINATION,
            'Amount' => '1000',
        ]);

        $this->assertEquals('5', $tx['Fee']);
    }

    /**
     * net fee x (33 + fulfillment bytes / 16), where the net fee is the
     * cushioned one, as in xrpl.js. The size used to be computed as
     * strlen($fulfillment / 2) instead of strlen($fulfillment) / 2, so the hex
     * string was cast to a number and the size was always 1 byte.
     */
    public function testEscrowFinishFeeScalesWithFulfillmentSize(): void
    {
        // 32 hex characters = 16 bytes, net fee 10 x 1.2 = 12 drops
        // -> 12 x (33 + 16/16) = 408
        $fulfillment = str_repeat('A0', 16);

        $tx = $this->client->autofill([
            'TransactionType' => 'EscrowFinish',
            'Account' => self::ACCOUNT,
            'Owner' => self::DESTINATION,
            'OfferSequence' => 7,
            'Fulfillment' => $fulfillment,
        ]);

        $this->assertEquals('408', $tx['Fee']);
    }

    /**
     * A larger fulfillment has to cost more - with the old bug every size gave
     * the same fee.
     */
    public function testLargerFulfillmentCostsMore(): void
    {
        $small = $this->client->autofill([
            'TransactionType' => 'EscrowFinish',
            'Account' => self::ACCOUNT,
            'Owner' => self::DESTINATION,
            'OfferSequence' => 7,
            'Fulfillment' => str_repeat('A0', 16),
        ]);

        $large = $this->client->autofill([
            'TransactionType' => 'EscrowFinish',
            'Account' => self::ACCOUNT,
            'Owner' => self::DESTINATION,
            'OfferSequence' => 7,
            'Fulfillment' => str_repeat('A0', 256),
        ]);

        $this->assertGreaterThan((int)$small['Fee'], (int)$large['Fee']);
    }

    /**
     * autofill() fills in what the transaction does not carry already.
     */
    public function testAutofillSetsSequenceAndLastLedgerSequence(): void
    {
        $tx = $this->client->autofill([
            'TransactionType' => 'Payment',
            'Account' => self::ACCOUNT,
            'Destination' => self::DESTINATION,
            'Amount' => '1000',
        ]);

        $this->assertEquals(self::SEQUENCE, $tx['Sequence']);
        $this->assertEquals(self::LEDGER_INDEX + 20, $tx['LastLedgerSequence']);
    }

    /**
     * An account holding Escrows, PayChannels, RippleStates or Checks cannot be
     * deleted. The check was unreachable: it was guarded by
     * `!isset($tx['TransactionType'])` instead of a comparison against
     * AccountDelete, and it counted blockers with the JavaScript idiom
     * `$objects['length']`, which is an undefined key in PHP.
     */
    public function testAccountDeleteIsRejectedWhenBlockersExist(): void
    {
        self::$server->setDefaultResponse(new RpcMethodResponse([
            'server_info' => [
                'info' => [
                    'validated_ledger' => ['base_fee_xrp' => self::BASE_FEE_XRP],
                    'load_factor' => 1,
                ],
            ],
            'server_state' => [
                'state' => ['validated_ledger' => ['reserve_inc' => self::RESERVE_INC]],
            ],
            'account_info' => ['account_data' => ['Sequence' => self::SEQUENCE]],
            'ledger' => ['ledger_index' => self::LEDGER_INDEX],
            'account_objects' => [
                'account_objects' => [
                    ['LedgerEntryType' => 'Escrow'],
                ],
            ],
        ]));

        $this->expectExceptionMessage('cannot be deleted');

        $this->client->autofill([
            'TransactionType' => 'AccountDelete',
            'Account' => self::ACCOUNT,
            'Destination' => self::DESTINATION,
        ]);
    }

    /**
     * The blocker lookup must not run for other transaction types.
     */
    public function testOtherTypesAreNotCheckedForBlockers(): void
    {
        self::$server->setDefaultResponse(new RpcMethodResponse([
            'server_info' => [
                'info' => [
                    'validated_ledger' => ['base_fee_xrp' => self::BASE_FEE_XRP],
                    'load_factor' => 1,
                ],
            ],
            'account_info' => ['account_data' => ['Sequence' => self::SEQUENCE]],
            'ledger' => ['ledger_index' => self::LEDGER_INDEX],
            'account_objects' => [
                'account_objects' => [['LedgerEntryType' => 'Escrow']],
            ],
        ]));

        $tx = $this->client->autofill([
            'TransactionType' => 'Payment',
            'Account' => self::ACCOUNT,
            'Destination' => self::DESTINATION,
            'Amount' => '1000',
        ]);

        $this->assertEquals('12', $tx['Fee']);
    }
}

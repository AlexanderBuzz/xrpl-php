<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Client;

use donatj\MockWebServer\MockWebServer;
use Exception;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\BatchFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\GlobalFlags;
use Hardcastle\XRPL_PHP\Test\MockRippled\RpcMethodResponse;

/**
 * What autofill() does to a Batch (XLS-56): the inner transactions get
 * their sequences, flag, zero fee and empty signing key; the outer
 * transaction gets the fee the ledger charges for all of it.
 *
 * The mock node answers every account_info with the same Sequence, so the
 * outer account and the other account start from the same number; the
 * difference between them is the point of the sequence test.
 */
final class AutofillerBatchTest extends TestCase
{
    private const OUTER = 'rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf';

    private const OTHER = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    private const SEQUENCE = 1432;

    /** base_fee_xrp 0.00001 times the default cushion 1.2 */
    private const NET_FEE_DROPS = 12;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private static MockWebServer $server;

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
                    'validated_ledger' => ['base_fee_xrp' => 0.00001],
                    'load_factor' => 1,
                ],
            ],
            'account_info' => ['account_data' => ['Sequence' => self::SEQUENCE]],
            'ledger' => ['ledger_index' => 2908714],
        ]));
    }

    private function client(): JsonRpcClient
    {
        return new JsonRpcClient(self::$server->getServerRoot());
    }

    private static function payment(string $account, string $destination, string $drops): array
    {
        return [
            'TransactionType' => 'Payment',
            'Account' => $account,
            'Destination' => $destination,
            'Amount' => $drops,
        ];
    }

    private static function batch(array $inner, int $flags = BatchFlags::tfAllOrNothing): array
    {
        return [
            'TransactionType' => 'Batch',
            'Account' => self::OUTER,
            'Flags' => $flags,
            'RawTransactions' => array_map(static fn (array $tx) => ['RawTransaction' => $tx], $inner),
        ];
    }

    public function testFillsTheInnerTransactionsAndSequencesThemPerAccount(): void
    {
        $filled = $this->client()->autofill(self::batch([
            self::payment(self::OUTER, self::OTHER, '1000'),
            self::payment(self::OTHER, self::OUTER, '2000'),
            self::payment(self::OUTER, self::OTHER, '3000'),
        ]));

        $this->assertSame(self::SEQUENCE, $filled['Sequence']);
        $inner = array_column(array_column($filled['RawTransactions'], 'RawTransaction'), null);

        // The outer account's inner transactions follow the outer Sequence
        $this->assertSame(self::SEQUENCE + 1, $inner[0]['Sequence']);
        $this->assertSame(self::SEQUENCE + 2, $inner[2]['Sequence']);
        // Another account starts at its own current sequence
        $this->assertSame(self::SEQUENCE, $inner[1]['Sequence']);

        foreach ($inner as $tx) {
            $this->assertSame(GlobalFlags::tfInnerBatchTxn, $tx['Flags']);
            $this->assertSame('0', $tx['Fee']);
            $this->assertSame('', $tx['SigningPubKey']);
            $this->assertArrayNotHasKey('LastLedgerSequence', $tx, 'inner transactions carry no expiry of their own');
        }
        $this->assertArrayHasKey('LastLedgerSequence', $filled);
    }

    public function testKeepsAnInnerSequenceOrTicketThatIsSet(): void
    {
        $ticketed = self::payment(self::OTHER, self::OUTER, '2000') + ['TicketSequence' => 99, 'Sequence' => 0];
        $pinned = self::payment(self::OUTER, self::OTHER, '1000') + ['Sequence' => 5000];

        $filled = $this->client()->autofill(self::batch([$ticketed, $pinned]));

        $this->assertSame(99, $filled['RawTransactions'][0]['RawTransaction']['TicketSequence']);
        $this->assertSame(0, $filled['RawTransactions'][0]['RawTransaction']['Sequence']);
        $this->assertSame(5000, $filled['RawTransactions'][1]['RawTransaction']['Sequence']);
    }

    public function testKeepsAnInnerFlagAndAddsTheBatchFlag(): void
    {
        $inner = self::payment(self::OUTER, self::OTHER, '1000') + ['Flags' => 0x00020000];

        $filled = $this->client()->autofill(self::batch([$inner, self::payment(self::OTHER, self::OUTER, '2000')]));

        $this->assertSame(0x00020000 | GlobalFlags::tfInnerBatchTxn, $filled['RawTransactions'][0]['RawTransaction']['Flags']);
    }

    /**
     * XLS-56 §2.2: (n + 2) base fees plus the inner transactions' fees, n
     * being the signatures in BatchSigners.
     */
    public function testTheFeeCoversTheOuterTransactionAndEveryInnerOne(): void
    {
        $oneAccount = $this->client()->autofill(self::batch([
            self::payment(self::OUTER, self::OTHER, '1000'),
            self::payment(self::OUTER, self::OTHER, '2000'),
        ]));

        $this->assertSame((string) (self::NET_FEE_DROPS * (2 + 2)), $oneAccount['Fee']);

        $withSignatureHint = self::batch([
            self::payment(self::OUTER, self::OTHER, '1000'),
            self::payment(self::OTHER, self::OUTER, '2000'),
        ]) + ['BatchSignatures' => 1];
        $filled = $this->client()->autofill($withSignatureHint);

        $this->assertSame((string) (self::NET_FEE_DROPS * (3 + 2)), $filled['Fee']);
        $this->assertArrayNotHasKey('BatchSignatures', $filled, 'the hint never reaches the transaction');
    }

    public function testTheFeeCountsTheSignaturesOfPresentBatchSigners(): void
    {
        $batch = self::batch([
            self::payment(self::OUTER, self::OTHER, '1000'),
            self::payment(self::OTHER, self::OUTER, '2000'),
        ]);
        $batch['Sequence'] = self::SEQUENCE;
        $batch['BatchSigners'] = [
            ['BatchSigner' => ['Account' => self::OTHER, 'Signers' => [
                ['Signer' => ['Account' => 'rA', 'SigningPubKey' => '00', 'TxnSignature' => '00']],
                ['Signer' => ['Account' => 'rB', 'SigningPubKey' => '00', 'TxnSignature' => '00']],
            ]]],
        ];

        $filled = $this->client()->autofill($batch);

        // two signatures in Signers, two base fees, two inner Payments
        $this->assertSame((string) (self::NET_FEE_DROPS * (2 + 2 + 2)), $filled['Fee']);
    }

    public function testTheFeeIncludesWhatAnInnerTransactionWouldCostOnItsOwn(): void
    {
        $escrowFinish = [
            'TransactionType' => 'EscrowFinish',
            'Account' => self::OUTER,
            'Owner' => self::OTHER,
            'OfferSequence' => 7,
            'Condition' => 'A0258020E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855810100',
            'Fulfillment' => 'A0028000',
        ];

        $filled = $this->client()->autofill(self::batch([$escrowFinish, self::payment(self::OUTER, self::OTHER, '1000')]));

        // the fulfillment surcharge: net fee x (33 + 4 bytes / 16), rounded up
        $surcharge = (int) ceil((float) self::NET_FEE_DROPS * 33.25);
        $this->assertSame((string) (self::NET_FEE_DROPS * 2 + $surcharge + self::NET_FEE_DROPS), $filled['Fee']);
        $this->assertSame('0', $filled['RawTransactions'][0]['RawTransaction']['Fee']);
    }

    public function testRefusesWhatTheLedgerWouldRefuse(): void
    {
        $client = $this->client();
        $a = self::payment(self::OUTER, self::OTHER, '1000');
        $b = self::payment(self::OTHER, self::OUTER, '2000');

        $cases = [
            'no mode' => [self::batch([$a, $b], 0), 'exactly one of the BatchFlags'],
            'two modes' => [self::batch([$a, $b], BatchFlags::tfAllOrNothing | BatchFlags::tfOnlyOne), 'exactly one of the BatchFlags'],
            'one inner' => [self::batch([$a]), '2 to 8 inner transactions'],
            'nine inner' => [self::batch(array_fill(0, 9, $a)), '2 to 8 inner transactions'],
            'nested' => [self::batch([$a, self::batch([$a, $b])]), 'cannot nest'],
            'inner fee' => [self::batch([$a + ['Fee' => '12'], $b]), 'Fee "0"'],
            'inner key' => [self::batch([$a + ['SigningPubKey' => 'ED00'], $b]), 'empty SigningPubKey'],
            'inner signature' => [self::batch([$a + ['TxnSignature' => '00'], $b]), 'must not be signed'],
        ];

        foreach ($cases as $name => [$batch, $message]) {
            try {
                $client->autofill($batch);
                $this->fail("{$name} has to be refused");
            } catch (Exception $e) {
                $this->assertStringContainsString($message, $e->getMessage(), $name);
            }
        }
    }
}

<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Sugar;

use donatj\MockWebServer\MockWebServer;
use Exception;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Test\MockRippled\RpcMethodResponse;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

use function Hardcastle\XRPL_PHP\Sugar\getLastLedgerSequence;
use function Hardcastle\XRPL_PHP\Sugar\getSignedTx;
use function Hardcastle\XRPL_PHP\Sugar\isAccountDelete;
use function Hardcastle\XRPL_PHP\Sugar\isSigned;
use function Hardcastle\XRPL_PHP\Sugar\submitRequest;

/**
 * Behaviour of the submission path, captured before it moves into a class.
 *
 * submitAndWait() polls the ledger with a three second sleep between attempts,
 * so only the paths that do not reach the polling loop are covered here.
 */
final class SubmitTest extends TestCase
{
    private const SEED = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';

    private const DESTINATION = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    private const LEDGER_INDEX = 2908714;

    private const SEQUENCE = 1432;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private static MockWebServer $server;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private JsonRpcClient $client;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private Wallet $wallet;

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
            'server_state' => [
                'state' => ['validated_ledger' => ['reserve_inc' => '2000000']],
            ],
            'account_info' => ['account_data' => ['Sequence' => self::SEQUENCE]],
            'ledger' => ['ledger_index' => self::LEDGER_INDEX],
            'account_objects' => ['account_objects' => []],
            'submit' => ['engine_result' => 'tesSUCCESS', 'tx_json' => []],
        ]));

        $this->client = new JsonRpcClient(self::$server->getServerRoot());
        $this->wallet = Wallet::fromSeed(self::SEED);
    }

    private function unsignedPayment(): array
    {
        return [
            'TransactionType' => 'Payment',
            'Account' => $this->wallet->getAddress(),
            'Destination' => self::DESTINATION,
            'Amount' => '1000',
            'Fee' => '12',
            'Sequence' => 1,
            'LastLedgerSequence' => self::LEDGER_INDEX + 20,
        ];
    }

    public function testIsSigned(): void
    {
        $this->assertFalse(isSigned($this->unsignedPayment()));
        $this->assertTrue(isSigned($this->signedPayment()));
    }

    /**
     * The signed transaction as an array, which is the shape the submission
     * path passes around.
     */
    private function signedPayment(): array
    {
        return (new BinaryCodec())->decode($this->wallet->sign($this->unsignedPayment())['tx_blob']);
    }

    public function testGetLastLedgerSequenceFromArray(): void
    {
        $this->assertEquals(self::LEDGER_INDEX + 20, getLastLedgerSequence($this->unsignedPayment()));
        $tx = $this->unsignedPayment();
        unset($tx['LastLedgerSequence']);
        $this->assertNull(getLastLedgerSequence($tx));
    }

    public function testGetLastLedgerSequenceFromBlob(): void
    {
        $blob = $this->wallet->sign($this->unsignedPayment())['tx_blob'];

        $this->assertEquals(self::LEDGER_INDEX + 20, getLastLedgerSequence($blob));
    }

    public function testIsAccountDelete(): void
    {
        $this->assertFalse(isAccountDelete($this->unsignedPayment()));

        $delete = $this->unsignedPayment();
        $delete['TransactionType'] = 'AccountDelete';
        unset($delete['Amount']);

        $this->assertTrue(isAccountDelete($delete));
        $this->assertTrue(isAccountDelete($this->wallet->sign($delete)['tx_blob']));
    }

    public function testGetSignedTxReturnsAnAlreadySignedTransaction(): void
    {
        $signed = $this->signedPayment();

        $this->assertEquals($signed, getSignedTx($this->client, $signed));
    }

    public function testGetSignedTxRequiresAWalletForUnsignedTransactions(): void
    {
        $this->expectExceptionMessage('Wallet must be provided when submitting an unsigned transaction');

        getSignedTx($this->client, $this->unsignedPayment());
    }

    /**
     * getSignedTx() used to return the tx_blob/hash envelope of Wallet::sign()
     * while every caller expected a transaction array, so submitting an
     * unsigned transaction together with a wallet always failed with
     * "Transaction must be signed".
     */
    public function testGetSignedTxSignsWithTheWallet(): void
    {
        $result = getSignedTx($this->client, $this->unsignedPayment(), false, $this->wallet);

        $this->assertEquals('Payment', $result['TransactionType']);
        $this->assertArrayHasKey('TxnSignature', $result);
        $this->assertTrue(isSigned($result));
    }

    /**
     * With autofill the fields the transaction does not carry are filled in
     * before signing.
     */
    public function testGetSignedTxAutofills(): void
    {
        $tx = [
            'TransactionType' => 'Payment',
            'Account' => $this->wallet->getAddress(),
            'Destination' => self::DESTINATION,
            'Amount' => '1000',
        ];

        $result = getSignedTx($this->client, $tx, true, $this->wallet);

        $this->assertEquals(self::SEQUENCE, $result['Sequence']);
        $this->assertEquals(self::LEDGER_INDEX + 20, $result['LastLedgerSequence']);
        $this->assertEquals('12', $result['Fee']);
    }

    public function testSubmitRequestRejectsUnsignedTransactions(): void
    {
        $this->expectExceptionMessage('Transaction must be signed');

        submitRequest($this->client, $this->unsignedPayment());
    }

    public function testSubmitReturnsTheEngineResult(): void
    {
        $response = $this->client->submit($this->unsignedPayment(), true, false, $this->wallet);

        $this->assertEquals('tesSUCCESS', $response->getResult()['engine_result']);
    }

    /**
     * submitAndWait() needs a LastLedgerSequence to know when to stop polling.
     */
    public function testSubmitAndWaitRequiresLastLedgerSequence(): void
    {
        $tx = $this->unsignedPayment();
        unset($tx['LastLedgerSequence']);
        $signed = (new BinaryCodec())->decode($this->wallet->sign($tx)['tx_blob']);

        $this->expectExceptionMessage('LastLedgerSequence');

        $this->client->submitAndWait($signed);
    }
}

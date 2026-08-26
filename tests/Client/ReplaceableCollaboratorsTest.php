<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Client;

use donatj\MockWebServer\MockWebServer;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\Autofiller;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Client\Submitter;
use Hardcastle\XRPL_PHP\Test\MockRippled\RpcMethodResponse;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * A subclass has to be able to replace what the client works with.
 *
 * The concrete case is a package for another network: Xahau prices a
 * transaction individually, because hooks may fire, so its autofiller has to
 * ask the server what a specific transaction costs instead of applying the XRP
 * Ledger's formula. Overriding a collaborator has to reach every path that uses
 * it, including the ones the client does not call directly - submitAndWait()
 * autofills through the Submitter, not through the client.
 */
final class ReplaceableCollaboratorsTest extends TestCase
{
    private const SEED = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';

    private const DESTINATION = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    /** The fee the replacement puts on every transaction */
    public const CUSTOM_FEE = '4242';

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
            'account_info' => ['account_data' => ['Sequence' => 1432]],
            'ledger' => ['ledger_index' => 2908714],
            'submit' => ['engine_result' => 'tesSUCCESS'],
        ]));
    }

    private function client(): JsonRpcClient
    {
        return new JsonRpcClient(self::$server->getServerRoot());
    }

    private function customClient(): JsonRpcClient
    {
        return new class(self::$server->getServerRoot()) extends JsonRpcClient {
            public function getAutofiller(): Autofiller
            {
                return new class($this) extends Autofiller {
                    public function calculateFeePerTransactionType(array &$tx, ?int $signersCount = 0): void
                    {
                        $tx['Fee'] = ReplaceableCollaboratorsTest::CUSTOM_FEE;
                    }
                };
            }
        };
    }

    private function payment(): array
    {
        return [
            'TransactionType' => 'Payment',
            'Account' => 'rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf',
            'Destination' => self::DESTINATION,
            'Amount' => '1000',
        ];
    }

    public function testDefaultClientUsesTheOwnCollaborators(): void
    {
        $this->assertInstanceOf(Autofiller::class, $this->client()->getAutofiller());
        $this->assertInstanceOf(Submitter::class, $this->client()->getSubmitter());
    }

    public function testOverriddenAutofillerIsUsedByTheClient(): void
    {
        $this->assertEquals('12', $this->client()->autofill($this->payment())['Fee']);
        $this->assertEquals(self::CUSTOM_FEE, $this->customClient()->autofill($this->payment())['Fee']);
    }

    /**
     * The path that matters: submitAndWait() autofills through the Submitter,
     * which used to construct an Autofiller of its own, so an override never
     * reached it.
     */
    public function testOverriddenAutofillerReachesTheSubmitter(): void
    {
        $client = $this->customClient();
        $wallet = Wallet::fromSeed(self::SEED);

        $tx = $this->payment();
        $tx['Account'] = $wallet->getAddress();

        $signed = $client->getSubmitter()->getSignedTx($tx, true, $wallet);

        $this->assertEquals(self::CUSTOM_FEE, $signed['Fee']);
    }

    /**
     * The fee lookup is reached from inside the Autofiller, so it has to come
     * from the client as well.
     */
    public function testOverriddenFeeCalculatorReachesTheAutofiller(): void
    {
        $client = new class(self::$server->getServerRoot()) extends JsonRpcClient {
            public function getFeeCalculator(): \Hardcastle\XRPL_PHP\Client\FeeCalculator
            {
                return new class($this) extends \Hardcastle\XRPL_PHP\Client\FeeCalculator {
                    public function getFeeXrp(?float $cushion = null): string
                    {
                        return '0.000500';
                    }
                };
            }
        };

        // 0.0005 XRP = 500 drops, and the cushion is already included
        $this->assertEquals('500', $client->autofill($this->payment())['Fee']);
    }
}

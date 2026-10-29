<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Models\BaseResponse;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\BatchFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\TxRequest;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

/**
 * Batch (XLS-56, Batch V1.1) against the Testnet, where the amendment is
 * enabled: a one-account Batch signed like any transaction, and a
 * two-account swap whose second party signs with signBatch().
 */
#[Group('integration-slow')]
final class BatchTest extends TestCase
{
    private const TESTNET_URL = 'https://s.altnet.rippletest.net:51234';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private JsonRpcClient $client;

    public function setUp(): void
    {
        $this->client = new JsonRpcClient(self::TESTNET_URL);
    }

    public function testOneAccountNeedsNoBatchSigners(): void
    {
        $alice = $this->client->fundWallet();
        $bob = $this->client->fundWallet();

        $batch = $this->client->autofill([
            'TransactionType' => 'Batch',
            'Account' => $alice->getAddress(),
            'Flags' => BatchFlags::tfAllOrNothing,
            'RawTransactions' => [
                ['RawTransaction' => $this->payment($alice, $bob, '1')],
                ['RawTransaction' => $this->payment($alice, $bob, '2')],
            ],
        ]);

        $signed = $alice->sign($batch);
        $result = $this->client->submitAndWait($signed['tx_blob'])->getResult();

        $this->assertSame('tesSUCCESS', $result['meta']['TransactionResult']);
        $this->assertArrayNotHasKey('BatchSigners', $result['tx_json'] ?? $result);

        foreach ((new BinaryCodec())->innerTransactionIds($batch) as $innerId) {
            $this->assertInnerTransactionSucceeded($innerId, $signed['hash']);
        }
    }

    public function testTwoAccountsSwapAtomically(): void
    {
        $alice = $this->client->fundWallet();
        $bob = $this->client->fundWallet();
        $bobBefore = $this->client->getXrpBalance($bob->getAddress());

        // Alice prepares the Batch: her 1 XRP against Bob's 2 XRP
        $batch = $this->client->autofill([
            'TransactionType' => 'Batch',
            'Account' => $alice->getAddress(),
            'Flags' => BatchFlags::tfAllOrNothing,
            'RawTransactions' => [
                ['RawTransaction' => $this->payment($alice, $bob, '1')],
                ['RawTransaction' => $this->payment($bob, $alice, '2')],
            ],
            'BatchSignatures' => 1,
        ]);

        // Bob signs his part and hands the Batch back
        $byBob = $bob->signBatch($batch);

        // Alice combines what came back, signs the outer transaction and submits
        $combined = Wallet::combineBatchSigners([$byBob]);
        $signed = $alice->sign($combined);
        $result = $this->client->submitAndWait($signed['tx_blob'])->getResult();

        $this->assertSame('tesSUCCESS', $result['meta']['TransactionResult']);
        $this->assertSame($bob->getAddress(), ($result['tx_json'] ?? $result)['BatchSigners'][0]['BatchSigner']['Account']);

        foreach ((new BinaryCodec())->innerTransactionIds($batch) as $innerId) {
            $this->assertInnerTransactionSucceeded($innerId, $signed['hash']);
        }

        // Bob paid 2 XRP, received 1 XRP and no fee: one XRP less
        $bobAfter = $this->client->getXrpBalance($bob->getAddress());
        $this->assertSame(
            (int) xrpToDrops('1'),
            (int) xrpToDrops($bobBefore) - (int) xrpToDrops($bobAfter)
        );
    }

    private function payment(Wallet $from, Wallet $to, string $xrp): array
    {
        return [
            'TransactionType' => 'Payment',
            'Account' => $from->getAddress(),
            'Destination' => $to->getAddress(),
            'Amount' => xrpToDrops($xrp),
        ];
    }

    /**
     * An applied inner transaction is a transaction of its own in the ledger,
     * with its own metadata, which names the Batch in ParentBatchID
     * (XLS-56 §2.5.2).
     */
    private function assertInnerTransactionSucceeded(string $transactionId, string $batchHash): void
    {
        $response = $this->client->syncRequest(new TxRequest($transactionId));
        $this->assertInstanceOf(BaseResponse::class, $response, "inner transaction {$transactionId} has to be found");
        $result = $response->getResult();

        $this->assertTrue($result['validated'] ?? false, "inner transaction {$transactionId} has to be validated");
        $this->assertSame('tesSUCCESS', $result['meta']['TransactionResult']);
        $this->assertSame($batchHash, $result['meta']['ParentBatchID'] ?? null, 'an applied inner transaction names its Batch');
    }
}

<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;
use Hardcastle\XRPL_PHP\Models\BaseResponse;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\BatchFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\TxRequest;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

/**
 * Batch (XLS-56, Batch V1.1) against the Testnet, where the amendment is
 * enabled: a one-account Batch signed like any transaction, a
 * two-account swap whose second party signs with signBatch(), the sequence
 * rules around tickets, and a party that signs through its signer list.
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

    /**
     * The sequence rules of autofill() on the ledger: an inner TicketCreate
     * reserves its tickets before the next inner sequence, an outer Batch on
     * a ticket consumes no sequence, and an inner transaction on a ticket
     * carries Sequence 0.
     */
    public function testSequencesAroundTickets(): void
    {
        $alice = $this->client->fundWallet();
        $bob = $this->client->fundWallet();

        // TicketCreate inside the Batch: the Payment after it has to skip its two tickets
        $batch = $this->client->autofill([
            'TransactionType' => 'Batch',
            'Account' => $alice->getAddress(),
            'Flags' => BatchFlags::tfAllOrNothing,
            'RawTransactions' => [
                ['RawTransaction' => [
                    'TransactionType' => 'TicketCreate',
                    'Account' => $alice->getAddress(),
                    'TicketCount' => 2,
                ]],
                ['RawTransaction' => $this->payment($alice, $bob, '1')],
            ],
        ]);
        $ticketCreate = $batch['RawTransactions'][0]['RawTransaction'];
        $this->assertSame($ticketCreate['Sequence'] + 3, $batch['RawTransactions'][1]['RawTransaction']['Sequence']);

        $this->submitAndAssertEveryInnerApplied($alice, $batch, 'Batch with an inner TicketCreate');
        $tickets = [$ticketCreate['Sequence'] + 1, $ticketCreate['Sequence'] + 2];

        // The outer Batch and one inner Payment on those tickets, one inner Payment on the sequence
        $batch = $this->client->autofill([
            'TransactionType' => 'Batch',
            'Account' => $alice->getAddress(),
            'Sequence' => 0,
            'TicketSequence' => $tickets[0],
            'Flags' => BatchFlags::tfAllOrNothing,
            'RawTransactions' => [
                ['RawTransaction' => $this->payment($alice, $bob, '1') + ['TicketSequence' => $tickets[1]]],
                ['RawTransaction' => $this->payment($alice, $bob, '2')],
            ],
        ]);
        $this->assertSame(0, $batch['RawTransactions'][0]['RawTransaction']['Sequence']);
        $accountInfo = $this->client->syncRequest(new AccountInfoRequest(account: $alice->getAddress(), ledgerIndex: 'validated'));
        $this->assertInstanceOf(BaseResponse::class, $accountInfo);
        $accountData = $accountInfo->getResult()['account_data'];
        $this->assertSame($accountData['Sequence'], $batch['RawTransactions'][1]['RawTransaction']['Sequence'], 'the ticketed Batch consumes no sequence');

        $this->submitAndAssertEveryInnerApplied($alice, $batch, 'Batch on a ticket');
    }

    /**
     * An account with a signer list (quorum 2) signs its part of a Batch
     * through two of its signers; combineBatchSigners() puts both into one
     * BatchSigner entry, and the BatchSignatures hint pays for both.
     */
    public function testASignerListSignsItsPartWithSeveralSigners(): void
    {
        $alice = $this->client->fundWallet();
        $bob = $this->client->fundWallet();
        $carol = Wallet::generate();
        $dave = Wallet::generate();

        $signerListSet = $this->client->autofill([
            'TransactionType' => 'SignerListSet',
            'Account' => $bob->getAddress(),
            'SignerQuorum' => 2,
            'SignerEntries' => [
                ['SignerEntry' => ['Account' => $carol->getAddress(), 'SignerWeight' => 1]],
                ['SignerEntry' => ['Account' => $dave->getAddress(), 'SignerWeight' => 1]],
            ],
        ]);
        $result = $this->client->submitAndWait($bob->sign($signerListSet)['tx_blob'])->getResult();
        $this->assertSame('tesSUCCESS', $result['meta']['TransactionResult'], 'SignerListSet');

        $batch = $this->client->autofill([
            'TransactionType' => 'Batch',
            'Account' => $alice->getAddress(),
            'Flags' => BatchFlags::tfAllOrNothing,
            'RawTransactions' => [
                ['RawTransaction' => $this->payment($alice, $bob, '1')],
                ['RawTransaction' => $this->payment($bob, $alice, '2')],
            ],
            'BatchSignatures' => 2,
        ]);

        $byCarol = $carol->signBatch($batch, $bob->getAddress(), true);
        $byDave = $dave->signBatch($batch, $bob->getAddress(), true);
        $combined = Wallet::combineBatchSigners([$byDave, $byCarol]);

        $this->assertCount(1, $combined['BatchSigners']);
        $this->assertCount(2, $combined['BatchSigners'][0]['BatchSigner']['Signers']);

        $this->submitAndAssertEveryInnerApplied($alice, $combined, 'Batch signed by a signer list');
    }

    /**
     * Sign the outer Batch, submit it, check the outer and every inner result,
     * and print the hashes, so a run leaves its Testnet evidence in the log.
     */
    private function submitAndAssertEveryInnerApplied(Wallet $outer, array $batch, string $label): void
    {
        $signed = $outer->sign($batch);
        $result = $this->client->submitAndWait($signed['tx_blob'])->getResult();
        $this->assertSame('tesSUCCESS', $result['meta']['TransactionResult'], $label);

        $innerIds = (new BinaryCodec())->innerTransactionIds($batch);
        foreach ($innerIds as $innerId) {
            $this->assertInnerTransactionSucceeded($innerId, $signed['hash']);
        }

        fwrite(STDERR, sprintf("\n%s: %s\n  inner: %s\n", $label, $signed['hash'], implode(', ', $innerIds)));
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

<?php

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\BatchFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\TxRequest;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

/**
 * Two Batch transactions (XLS-56) on the Testnet.
 *
 * First a Batch of one account: Alice pays Bob twice in one transaction, all
 * or nothing. It is signed like any other transaction.
 *
 * Then an atomic swap between two accounts: Alice's XRP against Bob's XRP,
 * in one Batch that either applies both payments or neither. Bob is not the
 * outer Account, so he signs the Batch with signBatch() and hands it back;
 * Alice merges his entry with combineBatchSigners(), signs the outer
 * transaction and submits. Nobody has to trust the other to pay second.
 *
 * https://xrpl.org/docs/references/protocol/transactions/types/batch
 */

$client = new JsonRpcClient('testnet');

print_r(Color::YELLOW . 'Funding two Testnet wallets' . Color::WHITE . PHP_EOL);
$alice = $client->fundWallet();
$bob = $client->fundWallet();

function payment(Wallet $from, Wallet $to, string $xrp): array
{
    return [
        'TransactionType' => 'Payment',
        'Account' => $from->getAddress(),
        'Destination' => $to->getAddress(),
        'Amount' => xrpToDrops($xrp),
    ];
}

function report(JsonRpcClient $client, array $batch, array $result): void
{
    $resultCode = $result['meta']['TransactionResult'];
    if ($resultCode !== 'tesSUCCESS') {
        print_r(Color::RED . "Batch failed with {$resultCode}! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL);
        exit(1);
    }

    print_r(Color::GREEN . "Batch applied: " . Color::WHITE . "{$result['hash']}" . PHP_EOL);

    // Every inner transaction is a transaction of its own in the ledger
    foreach ((new BinaryCodec())->innerTransactionIds($batch) as $innerId) {
        $inner = $client->syncRequest(new TxRequest($innerId))->getResult();
        print_r("  inner {$innerId}: {$inner['meta']['TransactionResult']}" . PHP_EOL);
    }
}

// 1. One account, two payments, all or nothing. autofill() sequences the inner
//    transactions, zeroes their fees, empties their signing keys and sets the
//    outer fee; the inner transactions are never signed on their own.
print_r(Color::YELLOW . 'Alice pays Bob 1 and 2 XRP in one Batch' . Color::WHITE . PHP_EOL);
$batch = $client->autofill([
    'TransactionType' => 'Batch',
    'Account' => $alice->getAddress(),
    'Flags' => BatchFlags::tfAllOrNothing,
    'RawTransactions' => [
        ['RawTransaction' => payment($alice, $bob, '1')],
        ['RawTransaction' => payment($alice, $bob, '2')],
    ],
]);
$signed = $alice->sign($batch);
report($client, $batch, $client->submitAndWait($signed['tx_blob'])->getResult());

// 2. Two accounts. The Batch has to be complete before anyone signs: every
//    BatchSigners entry signs over the outer Account, Sequence, Flags and the
//    inner transaction IDs. BatchSignatures tells autofill() how many
//    signatures the fee has to cover; it is a hint, not a field.
print_r(Color::YELLOW . 'Alice swaps 1 XRP for 2 XRP of Bob, atomically' . Color::WHITE . PHP_EOL);
$batch = $client->autofill([
    'TransactionType' => 'Batch',
    'Account' => $alice->getAddress(),
    'Flags' => BatchFlags::tfAllOrNothing,
    'RawTransactions' => [
        ['RawTransaction' => payment($alice, $bob, '1')],
        ['RawTransaction' => payment($bob, $alice, '2')],
    ],
    'BatchSignatures' => 1,
]);

// Bob's side: he reviews the Batch, signs his part, hands it back (in a real
// application as JSON or a tx_blob)
$byBob = $bob->signBatch($batch);

// Alice's side: merge the entries that came back (sorted the way the ledger
// requires), sign the outer transaction, submit
$combined = Wallet::combineBatchSigners([$byBob]);
$signed = $alice->sign($combined);
report($client, $combined, $client->submitAndWait($signed['tx_blob'])->getResult());

print_r(Color::GREEN . 'Alice: ' . Color::WHITE . $client->getXrpBalance($alice->getAddress()) . ' XRP, ' .
    Color::GREEN . 'Bob: ' . Color::WHITE . $client->getXrpBalance($bob->getAddress()) . ' XRP' . PHP_EOL);

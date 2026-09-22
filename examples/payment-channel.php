<?php

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\PaymentChannel\ChannelVerifyRequest;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\PaymentChannelClaimFlags;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

/**
 * A payment channel from end to end: the payer opens it with 10 XRP, signs
 * three claims offline (1, 2 and 3 XRP), the payee verifies them locally and
 * redeems the last one, and the payer asks for the channel to be closed.
 *
 * The point is the middle part. Claims are signed and verified without a
 * node, so a payer can stream thousands of them per second and the payee
 * checks each one in microseconds; only the final claim goes to the ledger.
 *
 * https://xrpl.org/docs/concepts/payment-types/payment-channels
 */

/**
 * Autofill, sign and submit a transaction, then stop the example if the ledger
 * did not accept it.
 */
function submit(JsonRpcClient $client, Wallet $wallet, array $tx): array
{
    $signedTx = $wallet->sign($client->autofill($tx));
    $result = $client->submitAndWait($signedTx['tx_blob'])->getResult();

    $resultCode = $result['meta']['TransactionResult'];
    if ($resultCode !== 'tesSUCCESS') {
        print_r(Color::RED . "{$tx['TransactionType']} failed with {$resultCode}! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);
        exit(1);
    }

    return $result;
}

print_r(PHP_EOL . Color::GREEN);
print_r("┌────────────────────────────┐" . PHP_EOL);
print_r("│   Payment channel example  │" . PHP_EOL);
print_r("└────────────────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);

const NETWORK = 'testnet';

$client = new JsonRpcClient(NETWORK);

print_r(Color::YELLOW . "Funding payer wallet, please wait..." . PHP_EOL);
$payer = $client->fundWallet();
print_r(Color::GREEN . "Payer: " . Color::WHITE . "{$payer->getAddress()}" . PHP_EOL);

print_r(Color::YELLOW . "Funding payee wallet, please wait..." . PHP_EOL);
$payee = $client->fundWallet();
print_r(Color::GREEN . "Payee: " . Color::WHITE . "{$payee->getAddress()}" . PHP_EOL . PHP_EOL);

/**
 * 1. The payer opens the channel.
 *
 * PublicKey is the key the claims will be signed with; the ledger stores it
 * in the channel, which is how the payee - and the ledger - later verify
 * claims without knowing anything else about the payer.
 */
print_r(Color::YELLOW . "Opening a channel with 10 XRP, please wait..." . PHP_EOL);
$result = submit($client, $payer, [
    "TransactionType" => "PaymentChannelCreate",
    "Account" => $payer->getAddress(),
    "Destination" => $payee->getAddress(),
    "Amount" => xrpToDrops("10"),
    "SettleDelay" => 60,
    "PublicKey" => $payer->getPublicKey(),
]);

$channelId = null;
foreach ($result['meta']['AffectedNodes'] as $node) {
    if (($node['CreatedNode']['LedgerEntryType'] ?? null) === 'PayChannel') {
        $channelId = $node['CreatedNode']['LedgerIndex'];
    }
}
print_r(Color::GREEN . "Channel: " . Color::WHITE . "{$channelId}" . PHP_EOL . PHP_EOL);

/**
 * 2. The payer signs claims offline.
 *
 * A claim is the channel ID and a cumulative amount in drops, signed with
 * the channel's key. Nothing here touches the network, and the secret never
 * leaves the process - unlike the channel_authorize RPC method, which has to
 * be sent the secret.
 */
print_r(Color::YELLOW . "Payer signs three claims offline:" . PHP_EOL);
$claims = [];
foreach (["1", "2", "3"] as $xrp) {
    $drops = xrpToDrops($xrp);
    $claims[$drops] = $payer->signPaymentChannelClaim($channelId, $drops);
    print_r(Color::GREEN . "  {$xrp} XRP: " . Color::WHITE . substr($claims[$drops], 0, 32) . "..." . PHP_EOL);
}
print_r(PHP_EOL);

/**
 * 3. The payee verifies them, locally.
 *
 * The same check rippled's channel_verify makes, without the round trip.
 * A tampered amount fails. The one call to channel_verify below is only
 * there to show that the node agrees.
 */
print_r(Color::YELLOW . "Payee verifies the claims locally:" . PHP_EOL);
foreach ($claims as $drops => $signature) {
    $ok = Wallet::verifyPaymentChannelClaim($channelId, (string) $drops, $signature, $payer->getPublicKey());
    print_r(Color::GREEN . "  {$drops} drops: " . Color::WHITE . ($ok ? "valid" : "INVALID") . PHP_EOL);
}
$tampered = Wallet::verifyPaymentChannelClaim($channelId, xrpToDrops("4"), $claims[xrpToDrops("3")], $payer->getPublicKey());
print_r(Color::GREEN . "  3 XRP claim presented as 4 XRP: " . Color::WHITE . ($tampered ? "valid" : "rejected") . PHP_EOL);

$nodeSays = $client->syncRequest(
    new ChannelVerifyRequest($channelId, xrpToDrops("3"), $payer->getPublicKey(), $claims[xrpToDrops("3")])
)->getResult();
print_r(Color::GREEN . "  channel_verify on the 3 XRP claim: " . Color::WHITE . ($nodeSays['signature_verified'] ? "valid" : "INVALID") . PHP_EOL . PHP_EOL);

/**
 * 4. The payee redeems the latest claim.
 *
 * Balance is the cumulative amount to have been delivered after this
 * transaction, Amount the amount the signature authorizes. Earlier claims
 * are simply never submitted.
 */
print_r(Color::YELLOW . "Payee redeems the 3 XRP claim, please wait..." . PHP_EOL);
$result = submit($client, $payee, [
    "TransactionType" => "PaymentChannelClaim",
    "Account" => $payee->getAddress(),
    "Channel" => $channelId,
    "Balance" => xrpToDrops("3"),
    "Amount" => xrpToDrops("3"),
    "Signature" => $claims[xrpToDrops("3")],
    "PublicKey" => $payer->getPublicKey(),
]);
print_r(Color::GREEN . "Redeemed! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 5. The payer asks for the channel to be closed.
 *
 * The source cannot close a channel at once; tfClose schedules the close
 * SettleDelay seconds ahead, which gives the payee time to redeem any claim
 * still in hand. The remaining 7 XRP return to the payer when it expires.
 */
print_r(Color::YELLOW . "Payer requests the close, please wait..." . PHP_EOL);
$result = submit($client, $payer, [
    "TransactionType" => "PaymentChannelClaim",
    "Account" => $payer->getAddress(),
    "Channel" => $channelId,
    "Flags" => PaymentChannelClaimFlags::tfClose,
]);
print_r(Color::GREEN . "Close scheduled! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

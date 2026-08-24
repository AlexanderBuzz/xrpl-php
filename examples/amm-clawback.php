<?php

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * AMMClawback (live on Mainnet) lets an issuer claw back its own token out of
 * an AMM pool. An ordinary Clawback cannot reach tokens that sit in an AMM,
 * which is why this transaction type exists.
 *
 * https://xrpl.org/docs/references/protocol/transactions/types/ammclawback
 */

/**
 * Autofill, sign and submit a transaction, then stop the example if the ledger
 * did not accept it.
 *
 * A tec result still makes it into the ledger, so submitAndWait() returning
 * without an exception is not by itself a success - the result code has to be
 * checked, otherwise a failed step would only surface as a confusing error
 * further down.
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
print_r("┌───────────────────────────┐" . PHP_EOL);
print_r("│   AMMClawback example     │" . PHP_EOL);
print_r("└───────────────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);

const NETWORK = 'testnet';
const CURRENCY = 'USD';

// AccountSet flags
const ASF_DEFAULT_RIPPLE = 8;
// Clawback has to be enabled before any trust line to the issuer exists
const ASF_ALLOW_TRUSTLINE_CLAWBACK = 16;

$client = new JsonRpcClient(NETWORK);

print_r(Color::YELLOW . "Funding issuer wallet, please wait..." . PHP_EOL);
$issuer = $client->fundWallet();
print_r(Color::GREEN . "Issuer: " . Color::WHITE . "{$issuer->getAddress()}" . PHP_EOL);

print_r(Color::YELLOW . "Funding holder wallet, please wait..." . PHP_EOL);
$holder = $client->fundWallet();
print_r(Color::GREEN . "Holder: " . Color::WHITE . "{$holder->getAddress()}" . PHP_EOL . PHP_EOL);

/**
 * 1. Prepare the issuer. Clawback only works while the issuer has no trust
 *    lines yet, so it has to be enabled before the token is handed out.
 *    Default Ripple is what lets the token move between third parties at all -
 *    without it the AMM cannot hold the token and AMMCreate fails with
 *    terNO_RIPPLE. SetFlag takes one flag at a time.
 */
print_r(Color::YELLOW . "Enabling clawback on the issuer, please wait..." . PHP_EOL);
$result = submit($client, $issuer, [
    "TransactionType" => "AccountSet",
    "Account" => $issuer->getAddress(),
    "SetFlag" => ASF_ALLOW_TRUSTLINE_CLAWBACK,
]);
print_r(Color::GREEN . "Clawback enabled! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL);

print_r(Color::YELLOW . "Enabling default ripple on the issuer, please wait..." . PHP_EOL);
$result = submit($client, $issuer, [
    "TransactionType" => "AccountSet",
    "Account" => $issuer->getAddress(),
    "SetFlag" => ASF_DEFAULT_RIPPLE,
]);
print_r(Color::GREEN . "Default ripple enabled! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 2. The holder opens a trust line and receives the token.
 */
print_r(Color::YELLOW . "Creating trust line, please wait..." . PHP_EOL);
$result = submit($client, $holder, [
    "TransactionType" => "TrustSet",
    "Account" => $holder->getAddress(),
    "LimitAmount" => [
        "currency" => CURRENCY,
        "issuer" => $issuer->getAddress(),
        "value" => "10000",
    ],
]);
print_r(Color::GREEN . "Trust line created! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

print_r(Color::YELLOW . "Issuing 1000 " . CURRENCY . " to the holder, please wait..." . PHP_EOL);
$result = submit($client, $issuer, [
    "TransactionType" => "Payment",
    "Account" => $issuer->getAddress(),
    "Destination" => $holder->getAddress(),
    "Amount" => [
        "currency" => CURRENCY,
        "issuer" => $issuer->getAddress(),
        "value" => "1000",
    ],
]);
print_r(Color::GREEN . "Token issued! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 3. The holder puts the token into an AMM together with XRP. A plain Clawback
 *    can no longer reach these tokens.
 */
print_r(Color::YELLOW . "Creating AMM pool (500 " . CURRENCY . " / 10 XRP), please wait..." . PHP_EOL);
$result = submit($client, $holder, [
    "TransactionType" => "AMMCreate",
    "Account" => $holder->getAddress(),
    "Amount" => [
        "currency" => CURRENCY,
        "issuer" => $issuer->getAddress(),
        "value" => "500",
    ],
    "Amount2" => "10000000",
    "TradingFee" => 500,
]);
print_r(Color::GREEN . "AMM created! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 4. AMMClawback pulls the token back out of the pool. Asset is the token being
 *    clawed back, Asset2 the other side of the pool. Leaving out Amount claws
 *    back the holder's entire share.
 */
print_r(Color::YELLOW . "Clawing back 200 " . CURRENCY . " from the pool, please wait..." . PHP_EOL);
$result = submit($client, $issuer, [
    "TransactionType" => "AMMClawback",
    "Account" => $issuer->getAddress(),
    "Holder" => $holder->getAddress(),
    "Asset" => [
        "currency" => CURRENCY,
        "issuer" => $issuer->getAddress(),
    ],
    "Asset2" => ["currency" => "XRP"],
    "Amount" => [
        "currency" => CURRENCY,
        "issuer" => $issuer->getAddress(),
        "value" => "200",
    ],
]);
print_r(Color::GREEN . "Clawback done! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 5. Read the pool back so the effect is visible.
 */
$body = json_encode([
    'method' => 'amm_info',
    'params' => [[
        'asset' => ['currency' => CURRENCY, 'issuer' => $issuer->getAddress()],
        'asset2' => ['currency' => 'XRP'],
    ]],
]);
$amm = json_decode($client->rawSyncRequest('POST', '', $body)->getBody()->getContents(), true)['result']['amm'] ?? null;
if ($amm !== null) {
    print_r(Color::GREEN . "Pool now holds: " . Color::WHITE . "{$amm['amount']['value']} " . CURRENCY . Color::GREEN . " and " . Color::WHITE . "{$amm['amount2']} drops" . PHP_EOL . PHP_EOL);
}

print_r(Color::RESET . "You can check the accounts and transactions on https://test.bithomp.com" . PHP_EOL . PHP_EOL);

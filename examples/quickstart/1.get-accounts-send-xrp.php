<?php

require __DIR__ . '/../../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Networks;
use XRPL_PHP\Models\Transaction\TransactionTypes\Payment;
use function XRPL_PHP\Sugar\xrpToDrops;

$testnetUrl = Networks::getNetwork('testnet')['jsonRpcUrl'];
$client = new JsonRpcClient($testnetUrl);

print_r(PHP_EOL . Color::GREEN);
print_r("┌──────────────────┐" . PHP_EOL);
print_r("│ Send XRP example │" . PHP_EOL);
print_r("└──────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);

print_r(Color::YELLOW . "Funding standby wallet, please wait..." . PHP_EOL);
$standbyWallet = $client->fundWallet();
print_r(Color::GREEN . "Created standby wallet - address: " . Color::WHITE . "{$standbyWallet->getAddress()} " . Color::GREEN . "seed: " . Color::WHITE . "{$standbyWallet->getSeed()}" . PHP_EOL);

print_r(Color::YELLOW . "Funding operational wallet, please wait..." . PHP_EOL);
$operationalWallet = $client->fundWallet();
print_r(Color::GREEN . "Created operational wallet - address: {$operationalWallet->getAddress()} seed: {$operationalWallet->getSeed()}" . PHP_EOL);

$xrpAmount = '100';
print_r(Color::YELLOW . "Sending {$xrpAmount} XRP from standby wallet to operational wallet, please wait..." . PHP_EOL);
$tx = [
    'TransactionType' => 'Payment',
    'Account' => $standbyWallet->getAddress(),
    'Amount' => xrpToDrops($xrpAmount),
    'Destination' => $operationalWallet->getAddress()
];
$autofilledTx = $client->autofill($tx);
$signedTx = $standbyWallet->sign($autofilledTx);

$body = json_encode([
    "method" => "submit",
    "params" => [
        ["tx_blob" => $signedTx['tx_blob']]
    ]
]);
$response = $client->rawSyncRequest('POST', '', $body);
$content = $response->getBody()->getContents();
$parsedContent = json_decode($content, true);
print_r(Color::GREEN . "XRP payment done! TxHash: " . Color::WHITE . "{$parsedContent['result']['tx_json']['hash']}" . PHP_EOL . PHP_EOL);

print_r(Color::RESET . "You can check wallets/accounts and transactions on https://test.bithomp.com"  . PHP_EOL . PHP_EOL);
<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/_const.php';

use XRPL_PHP\Client\JsonRpcClient;
use function XRPL_PHP\Sugar\xrpToDrops;

print_r(PHP_EOL . "--- Send XRP example ---" . PHP_EOL);

$client = new JsonRpcClient(RPC_TESTNET_URL);
$standbyWallet = $client->fundWallet($client);
$operationalWallet = $client->fundWallet($client);

print_r("Created standby wallet - address: {$standbyWallet->getAddress()} seed: {$standbyWallet->getSeed()}" . PHP_EOL);
print_r("Created operational wallet - address: {$operationalWallet->getAddress()} seed: {$operationalWallet->getSeed()}" . PHP_EOL);

$xrpAmount = '100';

// ------------------------------------------------------- Prepare transaction
$tx = [
    'TransactionType' => 'Payment',
    'Account' => $standbyWallet->getAddress(),
    'Amount' => xrpToDrops($xrpAmount),
    'Destination' => $operationalWallet->getAddress()
];
$autofilledTx = $client->autofill($tx);

// ------------------------------------------------ Sign prepared instructions
$signedTx = $standbyWallet->sign($autofilledTx);

// -------------------------------------------------------- Submit signed blob
$body = json_encode([
    "method" => "submit",
    "params" => [
        ["tx_blob" => $signedTx['tx_blob']]
    ]
]);
$response = $client->rawSyncRequest('POST', '', $body);
$content = $response->getBody()->getContents();

print_r(PHP_EOL . PHP_EOL . "Payment transaction of {$xrpAmount} XRP from standby wallet to operational wallet result:" . PHP_EOL);
print_r(json_decode($content, true));
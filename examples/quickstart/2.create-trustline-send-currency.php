<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/_const.php';

use XRPL_PHP\Client\JsonRpcClient;

print_r(PHP_EOL . "--- Send currency example ---" . PHP_EOL);

$client = new JsonRpcClient(RPC_TESTNET_URL);
$standbyWallet = $client->fundWallet($client);
$operationalWallet = $client->fundWallet($client);

print_r("Created standby wallet - address: {$standbyWallet->getAddress()} seed: {$standbyWallet->getSeed()}" . PHP_EOL);
print_r("Created operational wallet - address: {$operationalWallet->getAddress()} seed: {$operationalWallet->getSeed()}" . PHP_EOL);
print_r("Created operational wallet - address: {$operationalWallet->getAddress()} seed: {$operationalWallet->getSeed()}");

/*
 * Create a TrustLine
 */

$trustSet_tx = [
    "TransactionType" => "TrustSet",
    "Account" => $operationalWallet->getAddress(),
    "LimitAmount" => [
        "currency" => 'USD',
        "issuer" => $standbyWallet->getAddress(),
        "value" => 100
    ]
];

$ts_prepared = $client->autofill($trustSet_tx);

// operational wallet issues the trustline, so standby wallet can send currency
$signedTx = $operationalWallet->sign($ts_prepared);

print_r('Creating trust line from operational account to standby account...');

$ts_result = $client->submitAndWait($signedTx['tx_blob']);

print_r($ts_result->getResult());
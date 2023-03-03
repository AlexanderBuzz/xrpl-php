<?php

require __DIR__ . '/../../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Networks;
use XRPL_PHP\Wallet\Wallet;

print_r(PHP_EOL . "--- Send currency example ---" . PHP_EOL);

$testnetUrl = Networks::getNetwork('testnet')['jsonRpcUrl'];
$client = new JsonRpcClient($testnetUrl);
//$standbyWallet = $client->fundWallet($client);
//sleep(2); // TODO: Check for race condition in fundWallet()
//$operationalWallet = $client->fundWallet($client);
//sleep(2); // TODO: Check for race condition in fundWallet()

$standbyWallet = Wallet::fromSeed('sEdT9FpSc2R7yUypCYYvTP5fCE9dqnc');
$operationalWallet = Wallet::fromSeed('sEdVKPdy5gkpK7hbCUWqgHP2HrL1oDw');

print_r("Created standby wallet - address: {$standbyWallet->getAddress()} seed: {$standbyWallet->getSeed()}" . PHP_EOL);
print_r("Created operational wallet - address: {$operationalWallet->getAddress()} seed: {$operationalWallet->getSeed()}" . PHP_EOL);

/*
 * Create a TrustLine
 */
/*
$trustSetTx = [
    "TransactionType" => "TrustSet",
    "Account" => $operationalWallet->getAddress(),
    "LimitAmount" => [
        "currency" => 'USD',
        "issuer" => $standbyWallet->getAddress(),
        "value" => 100
    ]
];

$trustSetPreparedTx = $client->autofill($trustSetTx);

// operational wallet issues the trustline, so standby wallet can send currency
$signedTx = $operationalWallet->sign($trustSetPreparedTx);

print_r('Creating trust line from operational account to standby account...');

$trustSetResponse = $client->submitAndWait($signedTx['tx_blob']);

print_r($trustSetResponse->getResult());
*/

// Send IOU

$sendTokenTx = [
    "TransactionType" => "Payment",
    "Account" => $standbyWallet->getAddress(),
    "Amount" => [
        "currency" => 'USD',
        "value" => '10',
        "issuer" => $standbyWallet->getAddress()
    ],
    "Destination" => $operationalWallet->getAddress()
];

$preparedPaymentTx = $client->autofill($sendTokenTx);

$signedPaymentTx = $standbyWallet->sign($preparedPaymentTx);

$paymentResponse = $client->submitAndWait($signedPaymentTx['tx_blob']);

print_r($paymentResponse);
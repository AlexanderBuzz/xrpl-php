<?php

require __DIR__ . '/../../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Networks;
use XRPL_PHP\Wallet\Wallet;
use XRPL_PHP\Models\Transaction\SubmitRequest;
use XRPL_PHP\Models\Transaction\TransactionTypes\AccountSet;

print_r(PHP_EOL . "--- Send currency example ---" . PHP_EOL);

$testnetUrl = Networks::getNetwork('testnet')['jsonRpcUrl'];
$client = new JsonRpcClient($testnetUrl);

print_r("Funding cold wallet, please wait...", PHP_EOL);
$coldWallet = $client->fundWallet();
print_r("Created cold wallet - address: {$coldWallet->getAddress()} seed: {$coldWallet->getSeed()}" . PHP_EOL);

/*
print_r("Configuring cold wallet, please wait...", PHP_EOL);
$coldWalletConfigTx = [
    "TransactionType" => "AccountSet",
    "Account" => $coldWallet->getAddress(),
    "TransferRate" => 0,
    "TickSize" => 5
];
$coldConfigTxPrepared = $client->autofill($coldWalletConfigTx);
print_r($coldConfigTxPrepared);
$coldConfigTxSigned = $coldWallet->sign($coldConfigTxPrepared);
$accountSetResponse = $client->submitAndWait($coldConfigTxSigned['tx_blob']);
*/
print_r("Funding hot wallet, please wait...", PHP_EOL);
$hotWallet = $client->fundWallet();
print_r("Created hot wallet - address: {$hotWallet->getAddress()} seed: {$hotWallet->getSeed()}" . PHP_EOL);

/*
$hotWalletConfigTx = new AccountSet([
    "Account" => $hotWallet->getAddress(),
    "TransferRate" => 0,
    "TickSize" => 5,
]);
$hotConfigTxPrepared = $client->autofill($hotWalletConfigTx);
$hotConfigTxSigned = $coldWallet->sign($hotConfigTxPrepared);
$accountSetResponse = $client->submitAndWait($hotConfigTxSigned['tx_blob']);
*/

print_r('Creating trust line from cold wallet to hot wallet...');
$trustSetTx = [
    "TransactionType" => "TrustSet",
    "Account" => $hotWallet->getAddress(),
    "LimitAmount" => [
        "currency" => 'USD',
        "issuer" => $coldWallet->getAddress(),
        "value" => '10000'
    ]
];

$trustSetPreparedTx = $client->autofill($trustSetTx);
$signedTx = $hotWallet->sign($trustSetPreparedTx);
$trustSetResponse = $client->submitAndWait($signedTx['tx_blob']);

print_r($trustSetResponse->getResult());


// Send Token

$sendTokenTx = [
    "TransactionType" => "Payment",
    "Account" => $coldWallet->getAddress(),
    "Amount" => [
        "currency" => 'USD',
        "value" => '10',
        "issuer" => $coldWallet->getAddress()
    ],
    "Destination" => $hotWallet->getAddress()
];
$preparedPaymentTx = $client->autofill($sendTokenTx);
$signedPaymentTx = $coldWallet->sign($preparedPaymentTx);
$paymentResponse = $client->submitAndWait($signedPaymentTx['tx_blob']);

print_r($paymentResponse);
<?php

require __DIR__ . '/../../vendor/autoload.php';
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Networks;
use XRPL_PHP\Models\Transaction\TransactionTypes\Payment;
use function XRPL_PHP\Sugar\xrpToDrops;

print_r(PHP_EOL . "--- Send XRP example ---" . PHP_EOL);

$testnetUrl = Networks::getNetwork('testnet')['jsonRpcUrl'];
$client = new JsonRpcClient($testnetUrl);

$standbyWallet = $client->fundWallet();
print_r("Created standby wallet - address: {$standbyWallet->getAddress()} seed: {$standbyWallet->getSeed()}" . PHP_EOL);

$operationalWallet = $client->fundWallet();
print_r("Created operational wallet - address: {$operationalWallet->getAddress()} seed: {$operationalWallet->getSeed()}" . PHP_EOL);

$xrpAmount = '100';

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

print_r(PHP_EOL . PHP_EOL . "Payment transaction of {$xrpAmount} XRP from standby wallet to operational wallet result:" . PHP_EOL);
print_r(json_decode($content, true));
/*
$payment = new Payment([
    'Account' => $standbyWallet->getAddress(),
    'Amount' => xrpToDrops('123'),
    'Destination' => $operationalWallet->getAddress()
]);print_r($payment);
$autofilledPayment = $client->autofill($payment);
$trustSetResponse = $client->submitAndWait($autofilledPayment);*/
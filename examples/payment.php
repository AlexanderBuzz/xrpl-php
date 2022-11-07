<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;

use XRPL_PHP\Wallet\Wallet;
use function XRPL_PHP\Sugar\autofill;
use function XRPL_PHP\Sugar\xrpToDrops;

$testnetStandbyAccountSeed = 'sEdTcvQ9k4UUEHD9y947QiXEs93Fp2k';
$testnetStandbyAccountAddress = 'raJNboPDvjLrYZropPFrxvz2Qm7A9guEVd';
$standbyWallet = Wallet::fromSeed($testnetStandbyAccountSeed);

$testnetOperationalAccountSeed = 'sEdVHf8rNEaRveJw4NdVKxm3iYWFuRb';
$testnetOperationalAccountAddress = 'rEQ3ik2kmAvajqpFweKgDghJFZQGpXxuRN';
$operationalWallet = Wallet::fromSeed($testnetStandbyAccountSeed);

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$tx = [
    "TransactionType" => "Payment",
    "Account" => $testnetStandbyAccountAddress,
    "Amount" => xrpToDrops("100"),
    "Destination" => $testnetOperationalAccountAddress
];

$autofilledTx = $client->autofill($tx);

$signedTx = $standbyWallet->sign($autofilledTx);

//https://xrpl.org/submit.html

$body = json_encode([
    "method" => "submit",
    "params" => [
        ["tx_blob" => $signedTx['tx_blob']]
    ]
]);

$response = $client->rawSyncRequest('POST', '', $body);
$content = $response->getBody()->getContents();

print_r(json_decode($content, true));
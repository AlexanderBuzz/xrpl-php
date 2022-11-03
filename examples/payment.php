<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;

use XRPL_PHP\Wallet\Wallet;
use function XRPL_PHP\Sugar\autofill;
use function XRPL_PHP\Sugar\xrpToDrops;

$testnetStandbyAccountSeed = 'sEd7FjgfnVmTnjvtQBe9qy2SYydiRqz';
$testnetStandbyAccountAddress = 'r9yqc2rDs43YLX6uAy6fhdZs8FKykNS6zC';
$standbyWallet = Wallet::fromSeed($testnetStandbyAccountSeed);

$testnetOperationalAccountSeed = 'sEdSgEUuDQXSCH9Z1FuUezSW2zdkuFH';
$testnetOperationalAccountAddress = 'rBfWDieu16DVYFKzS4FvCbCKXP9LCG2tVH';
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
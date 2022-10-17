<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Transactions\Payment;
use XRPL_PHP\Wallet\Wallet;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$wallet = new Wallet(); //Mostly hardcoded so far

$payment = new Payment();

$payment->setAccount($wallet->getAddress());
$payment->setAmount("2000000");
$payment->setDestination("22000000");
$payment->autofill();

$signed = $wallet->sign($payment);

print_r($signed);

$body = json_encode([
    "method" => "submit",
    "params" => [
        ["tx_blob" => $signed["tx_blob"]]
    ]
]);

print_r($body);

$response = $client->rawSyncRequest('POST', '', $body);

$content = $response->getBody()->getContents();

print_r($content);


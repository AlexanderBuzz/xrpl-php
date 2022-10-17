<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Ledger\LedgerRequest;
use XRPL_PHP\Models\Utility\PingRequest;
use XRPL_PHP\Models\Transactions\Hash256;
use XRPL_PHP\Models\Transactions\Payment;
use XRPL_PHP\Wallet\Wallet;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$wallet = Wallet::generate(); //Mostly hardcoded so far

$pingRequest = new PingRequest();

$body = json_encode($pingRequest->getBody());

print_r(PHP_EOL . PHP_EOL . "--- Ping Request: ---" . PHP_EOL);
print_r($body);

$response = $client->rawSyncRequest('POST', '', $body);
$content = $response->getBody()->getContents();

print_r(PHP_EOL . PHP_EOL . "--- Ping Response: ---" . PHP_EOL);
print_r($content);

$ledgerRequest = new LedgerRequest(id:1, ledgerHash: new Hash256('1234'));

$body = json_encode($ledgerRequest->getBody());

print_r(PHP_EOL . PHP_EOL . "--- Ledger Request: ---" . PHP_EOL);
print_r($body);

$response = $client->rawSyncRequest('POST', '', $body);
$content = $response->getBody()->getContents();

print_r(PHP_EOL . PHP_EOL . "--- Ledger Response: ---" . PHP_EOL);
print_r($content);
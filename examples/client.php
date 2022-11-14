<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountObjectsRequest;

$testnetStandbyAccountSeed = 'sEd7r9n11TmibXPBNL3zEGE3aNcof9R';
$testnetStandbyAccountAddress = 'raKXrkYfbh4Uzqc481jTXbaKsWnW5XRMjp';

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$tx = [
    "Account" => $testnetStandbyAccountAddress
];

$request = new AccountObjectsRequest(
    account: $tx['Account'],
    ledgerIndex: 'validated',
    deletionBlockersOnly: true
);

//Test synchronous request
/* @var $pingResponse \XRPL_PHP\Models\Account\AccountObjectsResponse */
$accountObjectsResponse = $client->syncRequest($request);
print_r('AccountObjectResult: ' . PHP_EOL);
print_r($accountObjectsResponse);

print_r(PHP_EOL . PHP_EOL);

//Test asnychronous request
$response = $client->request($request)->wait();
$json = json_decode($response->getBody());
print_r('raw AccountObjectsRequest response: ' . PHP_EOL);
print_r($json);
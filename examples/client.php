<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountObjectsRequest;
use XRPL_PHP\Models\Account\AccountObjectsResponse;

/**
 * This script can be used with the examples from
 * https://live-xrpl.pantheonsite.io/course/code-with-the-xrpl/lesson/create-accounts-and-send-xrp/
 *
 * Note that the TesNet gets reset regularly, so the given addresses may be
 * out of date by the time you are using this example. Just generate new ones
 * by using the above link
 *
 * Purpose: Show a basic interaction with the ledger with sync and async requests
 */

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
/* @var $pingResponse AccountObjectsResponse */
$accountObjectsResponse = $client->syncRequest($request);
print_r('AccountObjectResult: ' . PHP_EOL);
print_r($accountObjectsResponse);
print_r(PHP_EOL . PHP_EOL);

//Test asnychronous request
$response = $client->request($request)->wait();
$json = $response->getResult();
print_r('raw AccountObjectsRequest response: ' . PHP_EOL);
print_r($json);
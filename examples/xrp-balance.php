<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountInfoRequest;

/**
 * This script can be used with the examples from
 * https://live-xrpl.pantheonsite.io/course/code-with-the-xrpl/lesson/create-accounts-and-send-xrp/
 *
 * Note that the TesNet gets reset regularly, so the given addresses may be
 * out of date by the time you are using this example. Just generate new ones
 * by using the above link
 */

$testnetStandbyAccountAddress = 'raKXrkYfbh4Uzqc481jTXbaKsWnW5XRMjp'; //Address, not seed!
$testnetOperationalAccountAddress = 'rBfXsGX5V8jcyKaPMCTcPvfVQzb4nEQymz'; //Address, not seed!

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$xrpBalanceRequest = new AccountInfoRequest($testnetStandbyAccountAddress);
$body = json_encode($xrpBalanceRequest->getBody());
$response = $client->syncRequest($xrpBalanceRequest);

$content = $response->getBody()->getContents();
$json = json_decode($content, true);

print_r("XRP Balance for Wallet {$testnetOperationalAccountAddress} is {$json['result']['account_data']['Balance']} XRP");


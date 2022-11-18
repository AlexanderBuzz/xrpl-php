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

$testnetAccount = 'rN7T1bzCHSwQu6adkqPJAtvF4mdf1FMuG6'; //Address, not seed!

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$xrpBalanceRequest = new AccountInfoRequest($testnetAccount);
$body = json_encode($xrpBalanceRequest->getBody());
$response = $client->syncRequest($xrpBalanceRequest, true);

$content = $response->getBody()->getContents();
$json = json_decode($content, true);

print_r(PHP_EOL);
print_r("XRP Balance for Wallet {$testnetAccount} is {$json['result']['account_data']['Balance']} XRP");
print_r(PHP_EOL);


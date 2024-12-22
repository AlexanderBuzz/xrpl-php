<?php

require __DIR__.'/../vendor/autoload.php';

use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;
use Hardcastle\XRPL_PHP\Models\Account\AccountTxRequest;

/**
 * This script can be used with the examples from
 * https://live-xrpl.pantheonsite.io/course/code-with-the-xrpl/lesson/create-accounts-and-send-xrp/
 *
 * Note that the TesNet gets reset regularly, so the given addresses may be
 * out of date by the time you are using this example. Just generate new ones
 * by using the above link
 */

$testnetAccount = 'rNmDxPqCiPj65nXbvaxyqNn3JDqFYUEjd8'; //Address, not seed!

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$xrpBalanceRequest = new AccountInfoRequest($testnetAccount);
$body = json_encode($xrpBalanceRequest->getBody());
$response = $client->syncRequest($xrpBalanceRequest, true);

$content = $response->getBody()->getContents();
$json = json_decode($content, true);

print_r(PHP_EOL);
print_r("XRP Balance for Wallet {$testnetAccount} is {$json['result']['account_data']['Balance']} XRP");
print_r(PHP_EOL);

$req = new AccountTxRequest($testnetAccount);
$res = $client->syncRequest($req, true);

$content = $response->getBody()->getContents();
$json = json_decode($content, true);

print_r(PHP_EOL);
print_r($json);
print_r(PHP_EOL);
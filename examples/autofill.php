<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountObjectsRequest;

use function XRPL_PHP\Sugar\fundWallet;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$fundWalletResponse = fundWallet($client)->wait();

//$json = json_decode($fundWalletResponse->getBody(), true);

print_r($fundWalletResponse);


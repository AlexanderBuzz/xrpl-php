<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountObjectsRequest;

use function XRPL_PHP\Sugar\fundWallet;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$fundWalletResponse = fundWallet(
    client: $client,
    amount: '12000'
);

$fundedWallet = $fundWalletResponse['wallet']; // Wallet object for further use

print_r(PHP_EOL);
print_r('New wallet address: ' . $fundWalletResponse['wallet']->getAddress() . PHP_EOL);
print_r('New wallet balance: ' . $fundWalletResponse['balance']  . ' XRP' .  PHP_EOL);


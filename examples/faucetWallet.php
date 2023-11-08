<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Wallet\Wallet;
use function XRPL_PHP\Sugar\fundWallet;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$wallet = Wallet::generate();

fundWallet($client, $wallet);

print_r(PHP_EOL);
print_r('Wallet address: ' . $wallet->getAddress() . PHP_EOL);
print_r('Wallet seed: ' . $wallet->getSeed());


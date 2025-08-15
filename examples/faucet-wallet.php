<?php

require __DIR__.'/../vendor/autoload.php';

use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\fundWallet;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$wallet = Wallet::generate();

$res = fundWallet($client, $wallet);

print_r($res);

print_r(PHP_EOL);
print_r('Wallet address: ' . $wallet->getAddress() . PHP_EOL);
print_r('Wallet seed: ' . $wallet->getSeed());
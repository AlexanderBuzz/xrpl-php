<?php

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Core\Stablecoin\RLUSD;

print_r(PHP_EOL . Color::GREEN);
print_r("┌───────────────────────┐" . PHP_EOL);
print_r("│     RLUSD example     │" . PHP_EOL);
print_r("└───────────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);

const NETWORK = 'testnet';

$client = new JsonRpcClient(NETWORK);

print_r(Color::YELLOW . "Funding wallet, please wait..." . PHP_EOL);
$wallet = $client->fundWallet();
print_r(Color::GREEN . "Created wallet - address: " . Color::WHITE . "{$wallet->getAddress()} " . Color::GREEN . "seed: " . Color::WHITE . "{$wallet->getSeed()}" . PHP_EOL);


print_r(Color::YELLOW . "Creating RLUSD trust line, please wait..." . PHP_EOL);
$trustSetTx = [
    "TransactionType" => "TrustSet",
    "Account" => $wallet->getAddress(),
    "LimitAmount" => RLUSD::getAmount(NETWORK, '10000')
];
$trustSetPreparedTx = $client->autofill($trustSetTx);
$signedTx = $wallet->sign($trustSetPreparedTx);
$trustSetResponse = $client->submitAndWait($signedTx['tx_blob']);
print_r(Color::GREEN . "Trust line created! TxHash: " . Color::WHITE . "{$trustSetResponse->getResult()['hash']}" . PHP_EOL);

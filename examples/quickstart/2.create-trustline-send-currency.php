<?php

require __DIR__ . '/../../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Networks;
use XRPL_PHP\Wallet\Wallet;
use XRPL_PHP\Models\Transaction\SubmitRequest;
use XRPL_PHP\Models\Transaction\TransactionTypes\AccountSet;

print_r(PHP_EOL . Color::GREEN);
print_r("┌───────────────────────┐" . PHP_EOL);
print_r("│ Send currency example │" . PHP_EOL);
print_r("└───────────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);

$testnetUrl = Networks::getNetwork('testnet')['jsonRpcUrl'];
$client = new JsonRpcClient($testnetUrl);

print_r(Color::YELLOW . "Funding cold wallet, please wait..." . PHP_EOL);
$coldWallet = $client->fundWallet();
print_r(Color::GREEN . "Created cold wallet - address: " . Color::WHITE . "{$coldWallet->getAddress()} " . Color::GREEN . "seed: " . Color::WHITE . "{$coldWallet->getSeed()}" . PHP_EOL);

/*
print_r("Configuring cold wallet, please wait...", PHP_EOL);
$coldWalletConfigTx = [
    "TransactionType" => "AccountSet",
    "Account" => $coldWallet->getAddress(),
    "TransferRate" => 0,
    "TickSize" => 5
];
$coldConfigTxPrepared = $client->autofill($coldWalletConfigTx);
print_r($coldConfigTxPrepared);
$coldConfigTxSigned = $coldWallet->sign($coldConfigTxPrepared);
$accountSetResponse = $client->submitAndWait($coldConfigTxSigned['tx_blob']);
*/

print_r(Color::YELLOW . "Funding hot wallet, please wait..." . PHP_EOL);
$hotWallet = $client->fundWallet();
sleep(2);
print_r(Color::GREEN . "Created hot wallet - address: " . Color::WHITE . "{$hotWallet->getAddress()} " . Color::GREEN . "seed: " . Color::WHITE . "{$hotWallet->getSeed()}" . PHP_EOL);

/*
$hotWalletConfigTx = new AccountSet([
    "Account" => $hotWallet->getAddress(),
    "TransferRate" => 0,
    "TickSize" => 5,
]);
$hotConfigTxPrepared = $client->autofill($hotWalletConfigTx);
$hotConfigTxSigned = $coldWallet->sign($hotConfigTxPrepared);
$accountSetResponse = $client->submitAndWait($hotConfigTxSigned['tx_blob']);
*/

print_r(Color::YELLOW . "Creating trust line from cold wallet to hot wallet, please wait..." . PHP_EOL);
$trustSetTx = [
    "TransactionType" => "TrustSet",
    "Account" => $hotWallet->getAddress(),
    "LimitAmount" => [
        "currency" => 'USD',
        "issuer" => $coldWallet->getAddress(),
        "value" => '10000'
    ]
];

$trustSetPreparedTx = $client->autofill($trustSetTx);
$signedTx = $hotWallet->sign($trustSetPreparedTx);
$trustSetResponse = $client->submitAndWait($signedTx['tx_blob']);
print_r(Color::GREEN . "Trust line created! TxHash: " . Color::WHITE . "{$trustSetResponse->getResult()['hash']}" . PHP_EOL);

// Send Token
$numTokens = '12';
print_r(Color::YELLOW . "Sending {$numTokens} Tokens from cold wallet to hot wallet, please wait..." . PHP_EOL);
$sendTokenTx = [
    "TransactionType" => "Payment",
    "Account" => $coldWallet->getAddress(),
    "Amount" => [
        "currency" => 'USD',
        "value" => $numTokens,
        "issuer" => $coldWallet->getAddress()
    ],
    "Destination" => $hotWallet->getAddress()
];
$preparedPaymentTx = $client->autofill($sendTokenTx);
$signedPaymentTx = $coldWallet->sign($preparedPaymentTx);
$paymentResponse = $client->submitAndWait($signedPaymentTx['tx_blob']);
print_r(Color::GREEN . "Token payment done! TxHash: " . Color::WHITE . "{$paymentResponse->getResult()['hash']}" . PHP_EOL . PHP_EOL);

print_r(Color::RESET . "You can check wallets/accounts and transactions on https://test.bithomp.com"  . PHP_EOL . PHP_EOL);

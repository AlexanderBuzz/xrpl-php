<?php

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Networks;
use XRPL_PHP\Wallet\Wallet;
use XRPL_PHP\Models\Transaction\SubmitRequest;
use XRPL_PHP\Models\Transaction\TransactionTypes\AccountSet;

$howToRunExample = "php token-create.php --tokenName LOP --issuerBalance 100000 --customerBalance 1000";

$issuerWallet = null;
$bankWallet = null;
$merchantWallet = null;
$customerWallet = null;

$options = getopt("", [
    "tokenName:",
    "issuerBalance:",
    "customerBalance:"
]);

if (!isset($options["tokenName"]) || !isset($options["issuerBalance"]) || !isset($options["customerBalance"])) {
    print_r(PHP_EOL . Color::YELLOW);
    print_r("Error: Missing Parameter");
    print_r(PHP_EOL . Color::RED);
    print_r($howToRunExample);
    print_r(PHP_EOL . Color::RESET);
    die();
}

$tokenName = $options["tokenName"];
$issuerBalance = $options["issuerBalance"];
$customerBalance = $options["customerBalance"];

print_r(PHP_EOL . Color::GREEN);
print_r("┌───────────────────────────┐" . PHP_EOL);
print_r("│ Token & Trustline Example │" . PHP_EOL);
print_r("└───────────────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);


$testnetUrl = Networks::getNetwork('testnet')['jsonRpcUrl'];
$client = new JsonRpcClient($testnetUrl);

//print_r(Color::YELLOW . "Funding issuer wallet, please wait..." . PHP_EOL);
//$issuerWallet = $client->fundWallet();
//sleep(2);
//print_r(Color::GREEN . "Created issuer wallet - address: " . Color::WHITE . "{$issuerWallet->getAddress()} " . Color::GREEN . "seed: " . Color::WHITE . "{$issuerWallet->getSeed()}" . PHP_EOL);

// Configure Wallet ...

print_r(Color::YELLOW . "Funding bank wallet, please wait..." . PHP_EOL);
$bankWallet = $client->fundWallet();
print_r(Color::GREEN . "Created bank wallet - address: " . Color::WHITE . "{$bankWallet->getAddress()} " . Color::GREEN . "seed: " . Color::WHITE . "{$bankWallet->getSeed()}" . PHP_EOL);

//print_r("Configuring bank wallet, please wait..." . PHP_EOL);
$bankWalletConfigTx = [
    "TransactionType" => "AccountSet",
    "Account" => $bankWallet->getAddress(),
    "SetFlag" => 8
];
$bankWalletConfigTxPrepared = $client->autofill($bankWalletConfigTx);
$bankWalletConfigTxSigned = $bankWallet->sign($bankWalletConfigTxPrepared);
$accountSetResponse = $client->submitAndWait($bankWalletConfigTxSigned['tx_blob']);

print_r(Color::YELLOW . "Funding merchant wallet, please wait..." . PHP_EOL);
$merchantWallet = $client->fundWallet();
print_r(Color::GREEN . "Created merchant wallet - address: " . Color::WHITE . "{$merchantWallet->getAddress()} " . Color::GREEN . "seed: " . Color::WHITE . "{$merchantWallet->getSeed()}" . PHP_EOL);

// Configure Wallet ...

print_r(Color::YELLOW . "Creating trust line from merchant wallet to bank wallet, please wait..." . PHP_EOL);
$trustSetTx = [
    "TransactionType" => "TrustSet",
    "Account" => $merchantWallet->getAddress(),
    "LimitAmount" => [
        "currency" => $tokenName,
        "issuer" => $bankWallet->getAddress(),
        "value" => $issuerBalance
    ]
];

$trustSetPreparedTx = $client->autofill($trustSetTx);
$signedTx = $merchantWallet->sign($trustSetPreparedTx);
$trustSetResponse = $client->submitAndWait($signedTx['tx_blob']);
print_r(Color::GREEN . "Trust line created! TxHash: " . Color::WHITE . "{$trustSetResponse->getResult()['hash']}" . PHP_EOL);

print_r(Color::YELLOW . "Funding customer wallet, please wait..." . PHP_EOL);
$customerWallet = $client->fundWallet();
print_r(Color::GREEN . "Created customer wallet - address: " . Color::WHITE . "{$customerWallet->getAddress()} " . Color::GREEN . "seed: " . Color::WHITE . "{$customerWallet->getSeed()}" . PHP_EOL);

// Configure Wallet ...

print_r(Color::YELLOW . "Creating trust line from customer wallet to bank wallet, please wait..." . PHP_EOL);
$trustSetTx = [
    "TransactionType" => "TrustSet",
    "Account" => $customerWallet->getAddress(),
    "LimitAmount" => [
        "currency" => $tokenName,
        "issuer" => $bankWallet->getAddress(),
        "value" => $issuerBalance
    ]
];

$trustSetPreparedTx = $client->autofill($trustSetTx);
$signedTx = $customerWallet->sign($trustSetPreparedTx);
$trustSetResponse = $client->submitAndWait($signedTx['tx_blob']);
print_r(Color::GREEN . "Trust line created! TxHash: " . Color::WHITE . "{$trustSetResponse->getResult()['hash']}" . PHP_EOL);

print_r(Color::YELLOW . "Sending {$customerBalance} Tokens from issuer wallet to customer wallet, please wait..." . PHP_EOL);
$sendTokenTx = [
    "TransactionType" => "Payment",
    "Account" => $bankWallet->getAddress(),
    "Amount" => [
            "currency" => $tokenName,
            "value" => $customerBalance,
            "issuer" => $bankWallet->getAddress()
    ],
    "Destination" => $customerWallet->getAddress()
];
$preparedPaymentTx = $client->autofill($sendTokenTx);
$signedPaymentTx = $bankWallet->sign($preparedPaymentTx);
$paymentResponse = $client->submitAndWait($signedPaymentTx['tx_blob']);
print_r(Color::GREEN . "Token payment done! TxHash: " . Color::WHITE . "{$paymentResponse->getResult()['hash']}" . PHP_EOL . PHP_EOL);

$examplePaymentAmount = '15';
print_r(Color::YELLOW . "Sending {$examplePaymentAmount} Tokens from customer wallet to merchant wallet, please wait..." . PHP_EOL);
$sendTokenTx = [
    "TransactionType" => "Payment",
    "Account" => $customerWallet->getAddress(),
    "Amount" => [
        "currency" => $tokenName,
        "value" => $examplePaymentAmount,
        "issuer" => $bankWallet->getAddress()
    ],
    "Destination" => $merchantWallet->getAddress()
];
$preparedPaymentTx = $client->autofill($sendTokenTx);
$signedPaymentTx = $customerWallet->sign($preparedPaymentTx);
$paymentResponse = $client->submitAndWait($signedPaymentTx['tx_blob']);
print_r(Color::GREEN . "Token payment done! TxHash: " . Color::WHITE . "{$paymentResponse->getResult()['hash']}" . PHP_EOL . PHP_EOL);

print_r(Color::RESET . "You can check wallets/accounts and transactions on https://test.bithomp.com"  . PHP_EOL . PHP_EOL);

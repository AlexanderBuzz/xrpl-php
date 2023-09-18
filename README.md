# PHP XRPL

PHP SDK / Client Library to interact with the XRP Ledger. It offers all the functionality available in the JavaScript 
and Java Versions emphasizing robustness and code readability for those interested in looking under the 
hood and getting into the nitty-gritty of XRPL development.

![Build Status](https://github.com/shopware/shopware/workflows/PHPUnit/badge.svg)
[![License](https://img.shields.io/badge/license-ISC-blue.svg)](http://opensource.org/licenses/ISC)

## Features

1. Managing keys & creating test credentials
2. Submitting transactions to the XRP Ledger
3. Sending requests to observe the ledger
4. Creating and signing transactions (e.g. Payments) to modify the ledger state
5. Parsing ledger data into more convenient formats

## Installation

This library is installable via [Composer](https://getcomposer.org/):

`composer require hardcastle/xrpl_php`

## Requirements

This library requires PHP 8.1 or later as well as the PHP extension [GMP](http://php.net/manual/en/book.gmp.php).

## Examples 

### The "Quickstart" Examples

These examples reproduce the functionality from the [JavaScript quickstart examples](https://learn.xrpl.org/course/code-with-the-xrpl/):

```console
php 1.get-accounts-send-xrp.php
php 2.create-trustline-send-currency.php
php 3.mint-nfts.php
```

### How-to Examples

These examples show how to use key features:

```console
php examples/client.php
php examples/fundWallet.php
php examples/payment.php
php examples/token-create.php // IOU + Token + CBDC - Wallet Matrix with Trustlines
etc...
```

### Core Examples

These examples can be used to explore XRPL core functionality:

```console
php examples/internal/address-codec.php
php examples/internal/binary-codec.php
etc...
```

### Run the project via Docker

1. In the project directory, start the project and open a shell:

```console
docker-compose up -d
docker-compose exec -u 0 php bash
```

2. In the container shell, install the composer dependencies:

```console
composer install
```

### Run Tests

You can run the tests with the following command:

```console
./vendor/bin/phpunit tests
```

You can perform static code analysis with psalm with the following command:

```console
./vendor/bin/psalm --config=psalm.xml
```

## Try it yourself

### Issuing an [Account Info request](https://xrpl.org/account_info.html):

```php
require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountObjectsRequest;

// Those will be purged from the Testnet in regular intervals, you can use fundWallet()
// to generate prefunded Wallets on the Testnet
$testnetAccountAddress = 'raKXrkYfbh4Uzqc481jTXbaKsWnW5XRMjp';

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$request = new AccountObjectsRequest(
    account: $testnetAccountAddress,
    ledgerIndex: 'validated',
    deletionBlockersOnly: true
);

// Using synchronous request
$response = $client->syncRequest($request);
$json = json_decode($response->getBody());
print_r($json);

// Using asynchronous request
// $response = $client->request($request)->wait();
// $json = json_decode($response->getBody());
// print_r($json);
```

### Making a payment:

```php
// Use your own credentials here:
$testnetStandbyAccountSeed = 'sEdTcvQ9k4UUEHD9y947QiXEs93Fp2k';
$testnetStandbyAccountAddress = 'raJNboPDvjLrYZropPFrxvz2Qm7A9guEVd';
$standbyWallet = Wallet::fromSeed($testnetStandbyAccountSeed);

// Use your own credentials here:
$testnetOperationalAccountSeed = 'sEdVHf8rNEaRveJw4NdVKxm3iYWFuRb';
$testnetOperationalAccountAddress = 'rEQ3ik2kmAvajqpFweKgDghJFZQGpXxuRN';
$operationalWallet = Wallet::fromSeed($testnetStandbyAccountSeed);

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$tx = [
    "TransactionType" => "Payment",
    "Account" => $testnetStandbyAccountAddress,
    "Amount" => xrpToDrops("100"),
    "Destination" => $testnetOperationalAccountAddress
];
$autofilledTx = $client->autofill($tx);
$signedTx = $standbyWallet->sign($autofilledTx);

$txResponse = $client->submitAndWait($signedTx['tx_blob']);
$result = $txResponse->getResult();
if ($result['meta']['TransactionResult'] === 'tecUNFUNDED_PAYMENT') {
    print_r("Error: The sending account is unfunded! TxHash: {$result['hash']}" . PHP_EOL);
} else {
    print_r("Token payment done! TxHash: {$result['hash']}" . PHP_EOL);
}
```
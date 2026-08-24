# PHP XRPL

PHP SDK / Client Library to interact with the XRP Ledger and the Xahau Network. It offers all the functionality available in the JavaScript 
and Java Versions emphasizing robustness and code readability for those interested in looking under the 
hood and getting into the nitty-gritty of XRPL development.

[![Latest Stable Version](https://poser.pugx.org/hardcastle/xrpl_php/version.svg)](https://packagist.org/packages/hardcastle/xrpl_php)
[![Total Downloads](https://poser.pugx.org/hardcastle/xrpl_php/d/total.svg)](https://packagist.org/packages/hardcastle/xrpl_php)
[![PHPUnit](https://github.com/AlexanderBuzz/xrpl-php/actions/workflows/unit_test.yml/badge.svg)](https://phpunit.de/index.html)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)

## Features

1. Managing & creating keys and wallets
2. Submitting transactions to the XRP Ledger
3. Sending requests to observe the ledger
4. Creating and signing transactions (e.g. Payments) to modify the ledger state
5. Parsing ledger data into more convenient formats
6. Xahau Network Compatibility (Hooks, UNLReport, GenesisMint, etc.) — see [Xahau support](#xahau-support) for the current scope

## Xahau support

The library ships the Xahau transaction types alongside the XRP Ledger ones, but
the two networks have drifted apart: Xahau kept its URIToken types on the
ordinals 45–49 and moved everything the XRP Ledger added afterwards further up.
`MPTokenIssuanceCreate`, for instance, is 54 on the XRP Ledger and 63 on Xahau.
A single set of definitions cannot be correct for both networks, and this
library resolves the overlap in favour of the XRP Ledger.

**Works on Xahau:**

- All classic transaction types — `Payment`, `AccountSet`, `TrustSet`,
  `OfferCreate`/`OfferCancel`, `Escrow*`, `Check*`, `PaymentChannel*`,
  `NFToken*`, `AMM*`, `Clawback`, `TicketCreate`, `SignerListSet`,
  `DepositPreauth`, `AccountDelete`, `SetRegularKey`. These carry the same
  ordinal on both networks.
- The Xahau-specific types — `SetHook`, `Invoke`, `Import`, `ClaimReward`,
  `GenesisMint`, `UNLReport`, `URIToken*`, `TicketCancel`.

**Does not work on Xahau yet:**

- Every type the XRP Ledger added from ordinal 41 onwards: `XChain*`, `DID*`,
  `Oracle*`, `MPToken*`, `Credential*`, `PermissionedDomain*`, `NFTokenModify`.
  These encode with the XRP Ledger ordinal, which means a different transaction
  type on Xahau — without an error. **Do not submit them to Xahau.**
- Decoding is ambiguous for the five shared ordinals: a Xahau `URITokenMint`
  decodes as `XChainAddClaimAttestation`, and the Xahau `Blob` field decodes as
  `DIDDocument`. The bytes are correct, only the names are read through the
  XRP Ledger definitions.
- `hooksDefinitions.json` predates the current Xahau release and is missing
  `Remit`, `SetRemarks`, `Cron` and `CronSet`.

A network aware `Definitions` instance that resolves this properly is planned
for the next release.

## Installation

This library is installable via [Composer](https://getcomposer.org/):

`composer require hardcastle/xrpl_php`

## Requirements

This library requires PHP 8.2 or later and two PHP extensions:

- [bcmath](https://www.php.net/manual/en/book.bc.php) — used directly for the
  ledger's fixed point arithmetic.
- [gmp](https://www.php.net/manual/en/book.gmp.php) — required by
  `simplito/elliptic-php`, which does the secp256k1 signing. Composer will
  refuse to install without it.

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
php examples/faucet-wallet.php
php examples/payment.php
php examples/token-create.php // IOU + Token + CBDC - Wallet Matrix with Trustlines
php examples/mptoken.php // Multi-Purpose Token: issue, authorize, send, claw back
php examples/permissioned-domain.php // Credentials + PermissionedDomain + PermissionedDEX
php examples/amm-clawback.php // Claw a token back out of an AMM pool
php examples/nftoken-modify.php // Mint a mutable NFT and change its URI
etc...
```

All of these run against the Testnet and fund their own wallets from the faucet.

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
docker compose up -d
docker compose exec -u 0 php bash
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

use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\Account\AccountObjectsRequest;

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
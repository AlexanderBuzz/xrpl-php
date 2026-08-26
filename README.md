# PHP XRPL

PHP SDK / Client Library to interact with the XRP Ledger and the Xahau Network. It offers all the functionality available in the JavaScript 
and Java Versions emphasizing robustness and code readability for those interested in looking under the 
hood and getting into the nitty-gritty of XRPL development.

[![Latest Stable Version](https://poser.pugx.org/hardcastle/xrpl_php/version.svg)](https://packagist.org/packages/hardcastle/xrpl_php)
[![Total Downloads](https://poser.pugx.org/hardcastle/xrpl_php/d/total.svg)](https://packagist.org/packages/hardcastle/xrpl_php)
[![PHPUnit](https://github.com/AlexanderBuzz/xrpl-php/actions/workflows/unit_test.yml/badge.svg)](https://phpunit.de/index.html)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)

## Features

1. XRP Ledger / rippled version 3.3.0 compatible
2. Managing & creating keys and wallets
3. Submitting transactions to the XRP Ledger
4. Sending requests to observe the ledger
5. Creating and signing transactions (e.g. Payments) to modify the ledger state
6. Parsing ledger data into more convenient formats

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
php examples/rlusd.php // Trust line and payment in Ripple USD
php examples/custom-currency-codes.php // Currency codes beyond the three character form
php examples/payment-with-destination-tag.php
php examples/xrp-balance.php
php examples/provoke-error.php // What an error response looks like
```

All of these run against the Testnet and fund their own wallets from the faucet.

### Core Examples

These examples can be used to explore XRPL core functionality:

```console
php examples/internal/address-codec.php
php examples/internal/binary-codec.php
etc...
```

## Try it yourself

### Issuing an [account_objects request](https://xrpl.org/account_objects.html)

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

// Synchronous
$response = $client->syncRequest($request);
print_r($response->getResult());

// Asynchronous - the promise resolves to the same response object
// $response = $client->request($request)->wait();
// print_r($response->getResult());
```

### Making a payment

```php
// Use your own credentials here:
$senderWallet = Wallet::fromSeed('sEdTcvQ9k4UUEHD9y947QiXEs93Fp2k');
$destination  = 'rEQ3ik2kmAvajqpFweKgDghJFZQGpXxuRN';

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$tx = [
    "TransactionType" => "Payment",
    "Account" => $senderWallet->getAddress(),
    "Amount" => xrpToDrops("10"),
    "Destination" => $destination
];

// Fills in Sequence, Fee and LastLedgerSequence, signs, submits, and waits
// until the transaction is in a validated ledger.
$txResponse = $client->submitAndWait($tx, autofill: true, wallet: $senderWallet);
$result = $txResponse->getResult();

// A tec result still reaches the ledger, so the code has to be checked -
// submitAndWait() returning is not by itself a success.
if ($result['meta']['TransactionResult'] !== 'tesSUCCESS') {
    print_r("Payment failed with {$result['meta']['TransactionResult']}! TxHash: {$result['hash']}" . PHP_EOL);
} else {
    print_r("Payment done! TxHash: {$result['hash']}" . PHP_EOL);
}
```

Signing yourself and submitting the blob works just as well, and is what the
files in `examples/` do:

```php
$signedTx = $senderWallet->sign($client->autofill($tx));
$txResponse = $client->submitAndWait($signedTx['tx_blob']);
```

### The objects behind the client

`JsonRpcClient` is a facade. Behind each of its operations sits a class that can
also be used on its own:

| Class | What it does |
|---|---|
| `Autofiller` | fills in Sequence, Fee and LastLedgerSequence |
| `Submitter` | submits transactions and polls for their outcome |
| `AccountReader` | balances and transaction history |
| `OrderbookReader` | the offers in one order book |
| `FeeCalculator` | the current network fee |
| `Faucet` | funds a wallet on a test network |

```php
$balances = (new AccountReader($client))->getBalances($address);
// same as
$balances = $client->getBalances($address);
```

The functions in the `Hardcastle\XRPL_PHP\Sugar` namespace do the same and keep
working, but they are deprecated as of 2.2.0 and delegate to these classes.

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

### Using your own definitions

Since 2.1.0 the codec works against definitions handed in from outside, so a
package for another network can supply its own `definitions.json` instead of the
bundled one. Every entry point takes an optional `Definitions` instance and
falls back to the XRP Ledger when it is omitted:

```php
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

$definitions = Definitions::fromFile('/path/to/xahau-definitions.json');
// or Definitions::fromArray($decodedJson);

$codec  = new BinaryCodec($definitions);
$wallet = Wallet::fromSeed($seed, $definitions);
$client = new JsonRpcClient('https://xahau.network', null, null, 3.0, $definitions);
```

A node serves its own definitions, so they can be fetched rather than vendored:

```console
curl -X POST https://xahau.network -H 'Content-Type: application/json' \
  -d '{"method":"server_definitions","params":[{}]}'
```

The definitions travel through the whole encode and decode, including nested
objects and arrays. They do not touch the shared default instance, so a process
can talk to both networks at once.

### Replacing what the client works with

The definitions decide how a transaction is encoded, but not everything a network
does differently. Xahau prices a transaction individually, because hooks may
fire, so the XRP Ledger's fee formula produces too low a fee there.

Since 2.3.0 the client exposes the objects it works with, and a subclass can
substitute one:

```php
use Hardcastle\XRPL_PHP\Client\Autofiller;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;

class XahauClient extends JsonRpcClient
{
    public function getAutofiller(): Autofiller
    {
        return new XahauAutofiller($this);
    }
}
```

The replacement is used wherever that object is reached, including
`submitAndWait()`, which autofills through the `Submitter` rather than through
the client. The same works for `getSubmitter()`, `getAccountReader()`,
`getOrderbookReader()`, `getFeeCalculator()` and `getFaucet()`.

A dedicated Xahau package building on this is planned; the Xahau types will then
move out of this library.

## Development

### Running the project via Docker

1. Tell the container which user to run as, so the files it writes belong to
   you. The values differ between Linux and macOS, so they come from `.env`:

```console
printf 'DOCKER_UID=%s\nDOCKER_GID=%s\n' "$(id -u)" "$(id -g)" > .env
```

2. Start the project and open a shell:

```console
docker compose up -d
docker compose exec php bash
```

3. In the container shell, install the composer dependencies:

```console
composer install
```

The image is built from `docker/`. Xdebug is preconfigured to reach the host on
port 9090 via `host.docker.internal`, which works on Linux as well because the
compose file maps it to the host gateway. For anything else that is specific to
your machine, add a `docker-compose.override.yml`; it is gitignored.

### Running the tests

You can run the tests with the following command:

```console
./vendor/bin/phpunit tests
```

You can perform static code analysis with psalm with the following command:

```console
./vendor/bin/psalm --config=psalm.xml
```

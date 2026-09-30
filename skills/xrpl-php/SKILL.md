---
name: xrpl-php
description: Guides XRP Ledger development in PHP with the hardcastle/xrpl_php package. Use when generating or reviewing PHP code that talks to the XRP Ledger - wallets and keys, funding on the Testnet, building, autofilling, signing and submitting transactions, checking results, reading balances and ledger objects through the JSON-RPC API methods, transaction flags, tokens (IOU, MPT, RLUSD), NFTs, credentials and permissioned domains, AMM, payment channel claims, the binary codec and custom definitions for other networks. HTTP calls are synchronous by default and built on Guzzle.
license: MIT
compatibility: Requires PHP 8.2+, ext-bcmath, ext-gmp and Composer
metadata:
  version: "1.0.0"
  sdk_version: "2.6.0"
  last_updated: "2026-09-29"
---

# XRPL PHP SDK (`hardcastle/xrpl_php`)

## Overview

`hardcastle/xrpl_php` is a PHP 8.2+ library for the XRP Ledger, at parity with rippled 3.4.0. Root namespace `Hardcastle\XRPL_PHP`. It talks to a node over JSON-RPC (not WebSocket), signs locally with secp256k1 or Ed25519, and ships the binary codec, address codec, every transaction model, every flag as a named constant, and request classes for the public API methods. The reference files in `references/` are generated from the code and list every model field, flag and request parameter; grep them for exact names.

## Installation

```bash
composer require hardcastle/xrpl_php
```

> All examples assume `<?php declare(strict_types=1);`, `require 'vendor/autoload.php';` and the `use` imports shown. Transactions are plain PHP arrays with the ledger's own field names (`TransactionType`, `Account`, ...). Amounts of XRP are strings in drops (1 XRP = 1,000,000 drops), never floats.

## 1. Client and networks

```php
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;

$client = new JsonRpcClient('testnet');            // alias: mainnet | testnet | devnet
$client = new JsonRpcClient('https://xrplcluster.com'); // or any JSON-RPC URL
```

The constructor also takes `?float $feeCushion` (default 1.2), `?string $maxFeeXrp` (default `'2'`), `float $timeout` (seconds, default 3.0) and `?Definitions $definitions` (see §9). Network URLs and IDs live in `Hardcastle\XRPL_PHP\Core\Networks::getNetwork('testnet')`.

## 2. Wallets and keys

```php
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use Hardcastle\XRPL_PHP\Core\RippleKeyPairs\KeyPair;

$wallet = Wallet::generate();                 // Ed25519 by default
$wallet = Wallet::generate(KeyPair::EC);      // secp256k1
$wallet = Wallet::fromSeed(getenv('XRPL_SEED')); // algorithm follows from the seed

$wallet->getAddress();       // r... classic address (same as getClassicAddress())
$wallet->getPublicKey();     // hex, ED... for Ed25519, 02/03... for secp256k1
$wallet->getSeed();          // s... - never log it
$wallet->getXAddress(1337, isTestnet: true);
```

A generated wallet does not exist on the ledger until it holds the reserve (funded by a Payment). On the Testnet, the faucet does that:

```php
$wallet = $client->fundWallet();          // new, funded wallet
$wallet = $client->fundWallet($wallet);   // fund an existing one
```

## 3. Transactions: build, autofill, sign, submit, check

The lifecycle every ledger change goes through. `autofill()` fills `Sequence`, `Fee` (per transaction type, including the reserve for `AccountDelete`/`AMMCreate` and the fulfillment surcharge for `EscrowFinish`) and `LastLedgerSequence`. `sign()` returns `tx_blob` and `hash`. `submitAndWait()` submits and polls until the transaction is in a validated ledger or can never be.

```php
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

$client = new JsonRpcClient('testnet');
$wallet = Wallet::fromSeed(getenv('XRPL_SEED'));

$tx = [
    'TransactionType' => 'Payment',
    'Account' => $wallet->getAddress(),
    'Destination' => 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe',
    'Amount' => xrpToDrops('10'),           // '10000000'
    'DestinationTag' => 12345,              // optional
];

$prepared = $client->autofill($tx);
$signed = $wallet->sign($prepared);         // ['tx_blob' => hex, 'hash' => hex]
$response = $client->submitAndWait($signed['tx_blob']);
$result = $response->getResult();

// A tec result is IN the ledger and cost the fee; only tesSUCCESS did what was asked.
if ($result['meta']['TransactionResult'] !== 'tesSUCCESS') {
    throw new RuntimeException("Payment failed: {$result['meta']['TransactionResult']} ({$result['hash']})");
}
echo $result['hash'];
```

The short form does autofill and signing for you:

```php
$response = $client->submitAndWait($tx, autofill: true, wallet: $wallet);
```

`submit()` has the same signature and returns at once with the preliminary `engine_result` (`getResult()['engine_result']`); it is the wrong call for anything that has to be sure. Neither method retries. `submitAndWait()` throws an `Exception` when the ledger passed `LastLedgerSequence` without including the transaction, with the preliminary result in the message.

Transaction models are optional and typed: `new Payment([...])` validates the type and gives `toArray()`; every field of every type is in `references/transactions.md`.

## 4. Amounts

```php
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;
use function Hardcastle\XRPL_PHP\Sugar\dropsToXrp;

'Amount' => xrpToDrops('1.5');                                    // XRP: drops as a string
'Amount' => ['currency' => 'USD', 'issuer' => $issuer, 'value' => '25'];  // issued token (IOU)
'Amount' => ['mpt_issuance_id' => $issuanceId, 'value' => '1000'];       // Multi-Purpose Token
dropsToXrp('1500000');                                            // '1.5'
```

Rules the ledger enforces, which the SDK checks where it can: drops are integers up to 10^17; `xrpToDrops()` throws on more than six decimals rather than rounding; an issued value has at most 16 significant digits and an exponent between -96 and 80; `'XRP'` is never a `currency` in the array form. The codec canonicalizes issued values (`'123.4000'` becomes `'123.4'` in the signed blob), so compare amounts after a round trip through `BinaryCodec::decode()`, not against the input string.

Currency codes longer than three characters are 40 hex characters: `CoreUtilities::encodeCustomCurrency('MyToken')` and `decodeCustomCurrency()`. `Hardcastle\XRPL_PHP\Core\Stablecoin\RLUSD::getAmount('mainnet', '10')` and `USDC::getAmount(...)` build the IOU array for those stablecoins with the right issuer per network.

## 5. Flags

Never write flag numbers. Every `tf`, `asf` and `lsf` flag rippled defines is a constant under rippled's own name; `references/flags.md` lists them all.

```php
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\PaymentFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\AccountSetAsfFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\AccountRootFlags;

'Flags' => PaymentFlags::tfPartialPayment,                 // bit flags: combine with |
'SetFlag' => AccountSetAsfFlags::asfRequireDest,           // AccountSet: one asf value per transaction
AccountRootFlags::parse($accountData['Flags']);            // ['lsfRequireDestTag', 'lsfDefaultRipple']
AccountRootFlags::has($accountData['Flags'], AccountRootFlags::lsfGlobalFreeze);
```

## 6. Reading the ledger

Convenience readers on the client:

```php
$client->getXrpBalance($address);        // '99.999988' (XRP, not drops)
$client->getBalances($address);          // [['currency' => 'XRP', 'value' => ...], ['currency' => 'USD', 'issuer' => ..., 'value' => ...]]
$client->getTransactions($address);      // account_tx result
$client->getOrderbook($takerGets, $takerPays);
$client->getFeeXrp();                    // current fee in XRP, cushioned
$client->getLedgerIndex();
```

Anything else is a request class, one per API method (`references/methods.md`):

```php
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;
use Hardcastle\XRPL_PHP\Models\Transaction\TxRequest;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;

$response = $client->syncRequest(new AccountInfoRequest(account: $address, ledgerIndex: 'validated'));
if ($response instanceof ErrorResponse) {
    throw new RuntimeException($response->getError());   // e.g. actNotFound
}
$accountData = $response->getResult()['account_data'];

$tx = $client->syncRequest(new TxRequest($hash))->getResult();

// Asynchronous: request() returns a Guzzle promise resolving to the same response object
$response = $client->request(new AccountInfoRequest(account: $address))->wait();

// A method without a request class
$raw = $client->rawSyncRequest('POST', '', json_encode(['method' => 'server_definitions', 'params' => [[]]]));
$json = json_decode((string) $raw->getBody(), true);
```

## 7. Signing details

- `sign()` refuses a transaction that already carries `TxnSignature` or `Signers` (`ValidationException`).
- `verifyTransaction($txBlob)` checks a blob against the wallet's own key.
- Multisign: `$signer->sign($tx, true)` signs as one of the account's signers and returns a blob whose transaction carries that signer's entry in `Signers` and an empty `SigningPubKey`; autofill with `signersCount` so the fee covers every signature. Collect the `Signers` entries of all signers into one transaction, **sorted by the numeric value of their `Account`** (rippled rejects other orders and the SDK does not sort them), and send it through `SubmitMultisignedRequest`.
- Never put a seed or private key into a log line, an exception message or a response; show the public key or address instead.
- Payment channel claims are signed and verified without a node: `$wallet->signPaymentChannelClaim($channelId, $amountDrops)` and `Wallet::verifyPaymentChannelClaim($channelId, $amountDrops, $signature, $publicKey)`. Amounts in drops. See `references/recipes.md`.
- `Hardcastle\XRPL_PHP\Utils\Hashes\HashLedger::hashSignedTx($txBlob)` gives the hash of a signed blob.

## 8. Codec and utilities

```php
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\CoreUtilities;
use Hardcastle\XRPL_PHP\Utils\Utilities;

$codec = new BinaryCodec();
$hex = $codec->encode($txArray);          // canonical binary, upper case hex
$array = $codec->decode($hex);            // back to the ledger's JSON form
$codec->encodeForSigning($txArray);       // what a signature is computed over

CoreUtilities::isValidClassicAddress($address);
CoreUtilities::classicAddressToXAddress($address, $tag, false);
CoreUtilities::xAddressToClassicAddress($xAddress);   // ['classicAddress' => ..., 'tag' => ...]
Utilities::convertStringToHex('https://example.com/nft.json');  // for URI, MemoData, Domain, MPTokenMetadata
Utilities::convertHexToString($hex);
```

`Hardcastle\XRPL_PHP\Core\Ctid` builds and parses Concise Transaction Identifiers.

## 9. Other networks and custom definitions

The codec works against a `Definitions` instance; the bundled one is the XRP Ledger's (rippled 3.4.0, verbatim from a node's `server_definitions`). Another network supplies its own:

```php
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;

$definitions = Definitions::fromFile('/path/to/definitions.json');   // or Definitions::fromArray($decoded)
$client = new JsonRpcClient('https://xahau.network', null, null, 3.0, $definitions);
$wallet = Wallet::fromSeed($seed, $definitions);
```

`Definitions::getInstance()` is the default; it exposes `getTransactionFlags($type)`, `getAccountSetFlags()` and `getLedgerEntryFlags($type)`. The Xahau transaction types shipped under `Hardcastle\XRPL_PHP\Hooks\...` are for Xahau only; they move to `hardcastle/xahau_php` in 3.0.0.

## 10. The objects behind the client

`JsonRpcClient` is a facade. `Autofiller`, `Submitter`, `AccountReader`, `OrderbookReader`, `FeeCalculator` and `Faucet` (all in `Hardcastle\XRPL_PHP\Client`) do the work and can be used or replaced on their own: a subclass overriding `getAutofiller()` etc. changes what every path uses. The functions in `Hardcastle\XRPL_PHP\Sugar` (`autofill`, `submit`, `submitAndWait`, `fundWallet`, `getBalances`, ...) are deprecated since 2.2.0 and delegate to these classes; `xrpToDrops` and `dropsToXrp` stay.

## Reference documentation

- `references/transactions.md`: every transaction model and its fields, with required/optional per rippled's format (generated).
- `references/flags.md`: every flag constant with value and meaning (generated).
- `references/methods.md`: every request class, its API method and parameters (generated).
- `references/recipes.md`: tokens and trust lines, RLUSD, MPT, NFTs, credentials and permissioned domains, AMM, payment channels, escrow, checks.
- `references/troubleshooting.md`: result codes, exceptions, Testnet quirks.

## Common pitfalls

- **Checking `submitAndWait()` for success.** It returns for any result that made it into a ledger, `tec*` included. Read `getResult()['meta']['TransactionResult']`.
- **Floats for amounts.** Use strings: `'10000000'`, `xrpToDrops('10')`, `'value' => '25.5'`.
- **`Fee` in XRP.** `Fee` is drops; let `autofill()` set it.
- **Strings where hex is expected.** `URI`, `MemoData`, `MemoType`, `Domain`, `MPTokenMetadata`, `CredentialType` are hex: `Utilities::convertStringToHex()` or `bin2hex()`.
- **Flag numbers from memory.** Use the constants; `Flags` of `AccountSet` is not where `asf*` values go, `SetFlag` is.
- **`Wallet::generate()` is not an account.** It needs the reserve on the ledger first; on Testnet use `$client->fundWallet()`.
- **Reusing a Testnet seed from a tutorial.** The Testnet is reset periodically; fund a fresh wallet instead.
- **`Batch` and `DelegateSet`.** The models decode but the library cannot sign a Batch (V1.1 rules); do not build one with it.
- **`syncRequest()` may return `ErrorResponse`.** Check `instanceof` before `getResult()`.
- **Stripping fields the SDK does not know.** rippled adds fields and result variants over time; pass responses through as arrays and read what you need rather than validating them against a fixed shape.
- **Xahau types on the XRP Ledger.** `SetHook`, `Invoke`, `URIToken*` etc. are Xahau; and `XChain*`, `DID*`, `Oracle*`, `MPToken*`, `Credential*`, `PermissionedDomain*` must not be sent to Xahau through this package.

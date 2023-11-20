---
layout: documentation
title: Quickstart
current_menu: quickstart
---

# Getting started with XRPL_PHP

## Installation

You can install XRPL_PHP with [Composer](http://getcomposer.org/doc/00-intro.md):

```
composer require hardcastle/xrpl_php
```

XRPL_PHP requires PHP 8.1 or above. Required is the PHP extension [GMP](http://php.net/manual/en/book.gmp.php). Optional 
but recommended is the PHP extension [BCMath](https://www.php.net/manual/de/book.bc.php), which will speed up operations 
involving arbitrary length numbers like encryption and serialisation.

## Sandbox

You can try XRPL_PHP in the following sandbox environment: [XRPL_PHP Sandbox](https://phpsandbox.io/n/yellow-kit-fisto-31c5a)

## Docker

[WIP]

## Basic usage

Interacting with the XRPL basically involves two kinds of operations: 

1. Querying the ledger, e.g. looking up the XRP balance of a given account or requesting the details of a specific transaction.
2. Doing [Transactions](https://xrpl.org/transactions.html), which are operations that modify the ledger state. Examples are issuing Payments or minting NFTs.

### Query Example - Pinging the XRP Ledger

First, let's do a very basic request to the XRPL Testnet:

```php
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Utility\PingRequest;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$pingRequest = new PingRequest();

$pingResponse = $client->syncRequest($pingRequest);

$result = $pingResponse->getResult();

print_r($result);
```

### Transaction example - Issuing a Payment

In this more complex example, we create two Wallets from their corresponding Seeds and issue a Payment from one to the other. 

```php
use Codedungeon\PHPCliColors\Color;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Wallet\Wallet;
use function XRPL_PHP\Sugar\xrpToDrops;

// Use your own credentials here:
$aliceSeed = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';
$aliceWallet = Wallet::fromSeed($aliceSeed);

// Use your own credentials here:
$bobSeed = 'sEd7XWKCuXENqLjPopq6WWFvoTnG3dX';
$bobWallet = Wallet::fromSeed($bobSeed);

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$xrpAmount = '50';
$tx = [
    "TransactionType" => "Payment",
    "Account" => $aliceWallet->getAddress(),
    "Amount" => xrpToDrops($xrpAmount),
    "Destination" => $bobWallet->getAddress(),
    "DestinationTag" => 1937215245
];
$autofilledTx = $client->autofill($tx);
$signedTx = $aliceWallet->sign($autofilledTx);

$txResponse = $client->submitAndWait($signedTx['tx_blob']);
$result = $txResponse->getResult();
```

The steps are as follows:
1. We create a `Transaction` array (you can also use dedicated [Method](methods.md) objects)
2. We use the `autofill()` function to add necessary fields like `Sequence`, `Fee` and `LastLedgerSequence`
3. We sign our Transaction (which will result in an array with the fields tx_blob and hash)
4. We submit our transaction using `submitAndWait()`, which submits the serialized `Transaction` and waits for validation

## Further Reading
In those two examples we've touched most of the basic needs for most use cases. To get a better understanding, you can take a look at 
more [examples](https://github.com/AlexanderBuzz/xrpl-php/tree/master/examples) or explore this documentation. Tip: This documentation is
structured in a conceptually successive order. So just skimming it in order will give you a birds eye view on how all the 
parts fit into the big picture. 
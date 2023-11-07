---
layout: documentation
title: Wallet
current_menu: wallet
---

# Wallet

### Creating a new Wallet 

To create a new `Wallet`, you can use the static `generate()` method of the `Wallet` class:

```php
use XRPL_PHP\Wallet\Wallet;

$exampleWallet = Wallet::generate();
```

It provides the necessary
properties for an Account on the ledger to exist, e.g. Seed, Keys and Address etc. Note that this Account does not
yet exist on the Ledger: It has to be activated by a `Payment Transaction`. If you are using the testnet, you can use the
`fundWallet()` utility method or use a [Faucet](https://test.bithomp.com/faucet/) to get your `Wallet` funded and active.


### Creating a Wallet from Seed

If you already have an active `Account` on the XRPL, you can create a corresponding `Wallet` from its seed by using the 
static `fromSeed()`method:

```php
use XRPL_PHP\Wallet\Wallet;

// Use your own credentials here:
$exampleSeed = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';
$exampleWallet = Wallet::fromSeed($exampleSeed);
```

### Creating a Faucet Wallet on the Testnet

An Account on the XRPL has to be funded to be activated. On the Mainnet, this is done by sending a `Payment Transaction`. 
On the Testnet, you can create a prefunded Wallet in the following way:

```php
use XRPL_PHP\Client\JsonRpcClient;

use function XRPL_PHP\Sugar\fundWallet;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$fundWalletResponse = fundWallet(
    client: $client,
    amount: '12000'
);

$fundedWallet = $fundWalletResponse['wallet'];
```

### Signing a Transaction

To sign a `Transaction`, you can use the `sign()` method of a `Wallet`:

```php
$tx = [
    "TransactionType" => "Payment",
    "Account" => $aliceWallet->getAddress(),
    "Amount" => xrpToDrops($xrpAmount),
    "Destination" => $bobWallet->getAddress(),
    "DestinationTag" => 1937215245,
    "Sequence" => 42538726,
    "Fee" => 12,
    "LastLedgerSequence" => 42697421  
];
$signedTx = $wallet->sign($tx);
```

The result will be an array comprised of two fields:
1. `$signedTx['tx_blob']`, representing the signed, serialized `Transaction`
2. `$signedTx['hash']`, the hashed `tx_blob`
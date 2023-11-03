### Creating a Wallet

### Creating a wallet

```php
use XRPL_PHP\Wallet\Wallet;

// Use your own credentials here:
$exampleSeed = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';
$exampleWallet = Wallet::fromSeed($exampleSeed);
```

### Creating a Faucet Wallet on the Testnet

An account on the XRPL has to be funded / activated. On the Mainnet, this is done by sending a Payment transaction On the Testnet, you can create a

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

### Create a Wallet from seed

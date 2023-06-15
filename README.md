# PHP XRPL

PHP Client Library to develop XRP Ledger. It offers all the functionality available in the JavaScript 
and Java Versions emphasizing robustness and code readability for those interested in looking under the 
hood and getting into the nitty-gritty of XRPL development.

![Build Status](https://github.com/shopware/shopware/workflows/PHPUnit/badge.svg)
[![License](https://img.shields.io/badge/license-ISC-blue.svg)](http://opensource.org/licenses/ISC)

## How to install

`composer require hardcastle/xrpl_php dev-master`

## How to run

### Build Container
In the host directory:
```
docker-compose up  -d
docker-compose exec -u 0 php bash
```
In the container shell:
```
composer install
```

### Run Quickstart Examples
These examples reproduce the functionality from the [JavaScript 
quickstart examples](https://learn.xrpl.org/course/code-with-the-xrpl/)
```
php 1.get-accounts-send-xrp.php
php 2.create-trustline-send-currency.php
php 2.create-trustline-send-currency.php
```

### Run Core Examples 
These examples can be used to explore core functionality
```
php examples/internal/address-codec.php
php examples/internal/binary-codec.php
etc
```

### Run Examples
These examples can be used to play around with isolated features
```
php examples/client.php
php examples/fundWallet.php
php examples/payment.php
etc
```

### Run Tests
`./vendor/bin/phpunit tests`

### Try it yourself
```php
require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountObjectsRequest;

//Those will be purged from the Testnet in regular intervals, you can use fundWallet()
// to generate prefunden Wallets on Testnet
$testnetStandbyAccountSeed = 'sEd7r9n11TmibXPBNL3zEGE3aNcof9R';
$testnetStandbyAccountAddress = 'raKXrkYfbh4Uzqc481jTXbaKsWnW5XRMjp';

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$tx = [
    "Account" => $testnetStandbyAccountAddress
];

$request = new AccountObjectsRequest(
    account: $tx['Account'],
    ledgerIndex: 'validated',
    deletionBlockersOnly: true
);

//Test synchronous request
$response = $client->syncRequest($request);
$json = json_decode($response->getBody());
print_r($json);

//Test asynchronous request
$response = $client->request($request)->wait();
$json = json_decode($response->getBody());
print_r($json);
```
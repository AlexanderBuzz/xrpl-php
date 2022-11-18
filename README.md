# PHP XRPL [WIP]

PHP Client Library to develop XRP Ledger. It offers all the functionality available in the JavaScript 
and Java Versions emphasizing robustness and code readability for those interested in looking under the 
hood and getting into the nitty-gritty of XRPL development.

It is currently work in progress nearing the finishing line, with intended use in PHP ecommerce platforms 
in mind when it comes to feature priority. The fully featured and tested Version will be available somewhere 
around late September / middle of October.

![Build Status](https://github.com/shopware/shopware/workflows/PHPUnit/badge.svg)
[![License](https://img.shields.io/badge/license-ISC-blue.svg)](http://opensource.org/licenses/ISC)

## How to install

[WIP] 
`composer require gndlf/xrpl_php`

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

### Run Examples 
```
php examples/client.php
php examples/fundWallet.php
php examples/payment.php
etc
```

### Run Quickstart Examples [WIP]
These examples repoduce the functionality from the [JavaScript 
quickstart examples](https://learn.xrpl.org/course/intro-to-the-xrpl/)
```
php 1.get-accounts-send-xrp.php
etc [WIP]
```

### Run Core Examples 
These examples can be used to explore core functionality
```
php examples/internal/address-codec.php
php examples/internal/binary-codec.php
etc
```

### Run Tests
`./vendor/bin/phpunit tests`

### Try it yourself
```php
require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountObjectsRequest;

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
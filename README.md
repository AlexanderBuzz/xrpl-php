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
```
docker-compose up  -d
docker-compose exec -u 0 fpm bash
composer install
```

### Run Examples 
```
cd /app
php bin/address-codec.php
php bin/binary-codec.php
```

### Run Tests
`./vendor/bin/phpunit tests`
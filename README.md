# PHP XRPL [WIP]

PHP Client Library to access to access the XRP Ledger

![Build Status](https://github.com/shopware/shopware/workflows/PHPUnit/badge.svg)
[![License](https://img.shields.io/badge/license-ISC-blue.svg)](http://opensource.org/licenses/ISC)

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
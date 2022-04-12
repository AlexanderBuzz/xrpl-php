# PHP XRPL [WIP]

PHP Client Library to access to access the XRP Ledger

## How to run

### Build Container
```
docker-compose up  -d
docker-compose exec -u 0 fpm bash
composer install
```

### Run Examples 
`php bin/address-codec.php`

### Run Tests
`./vendor/bin/phpunit tests`
<?php

require __DIR__.'/../vendor/autoload.php';

use Hardcastle\XRPL_PHP\Core\CoreUtilities;

$customCurrency = "Dropzop";

$hash = CoreUtilities::encodeCustomCurrency($customCurrency);

print_r("Currency -> Hash: " . $customCurrency . ' -> ' . $hash . PHP_EOL);

$currencyBacktest = CoreUtilities::decodeCustomCurrency($hash);

print_r("Hash -> Currency: ". $hash . ' -> ' . $currencyBacktest . PHP_EOL);
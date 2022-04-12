<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Core\RippleAddressCodec\RippleAddressCodec;

$codec = new RippleAddressCodec();


//Should be: 'XVLhHMPHU98es4dbozjVtdWzVrDjtV18pX8yuPT7y4xaEHi'
$encoded = $codec->classicAddressToXAddress('rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf', 4294967295);
print_r("Encoded Address: " . $encoded . PHP_EOL );

//should be: 'T7oKJ3q7s94kDH6tpkBowhetT1JKfcfdSCmAXbS75iATyLD'
$encoded = $codec->classicAddressToXAddress('r3SVzk8ApofDJuVBPKdmbbLjWGCCXpBQ2g', 123, true);
print_r("Testnet Encoded Address: " . $encoded . PHP_EOL );


$decoded = $codec->xAddressToClassicAddress('XVLhHMPHU98es4dbozjVtdWzVrDjtV18pX8yuPT7y4xaEHi');
//should be:
//[
//  "classicAddress" => "rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf",
//  "tag" => 4294967295,
//  "test" => false
//[
print_r("Decoded Address:");
print_r($decoded);
print_r(PHP_EOL );

$valid = $codec->isValidXAddress('XVLhHMPHU98es4dbozjVtdWzVrDjtV18pX8yuPT7y4xaEHi');
//should be: 1
print_r("Test Address validity: " . $valid . PHP_EOL);
<?php

require __DIR__ . '/../../vendor/autoload.php';

use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;

$codec = new AddressCodec();

print_r('Ripple-address-codec decode example, HEX string to JSON object' .PHP_EOL .PHP_EOL);

$input = 'rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf';
$expected = 'XVLhHMPHU98es4dbozjVtdWzVrDjtV18pX8yuPT7y4xaEHi';
print_r("Input: " . $input . PHP_EOL . "Expected: " . $expected . PHP_EOL);
$encoded = $codec->classicAddressToXAddress($input, 4294967295);
print_r("Encoded Address: " . $encoded . PHP_EOL . PHP_EOL);

$input = 'r3SVzk8ApofDJuVBPKdmbbLjWGCCXpBQ2g';
$expected = 'T7oKJ3q7s94kDH6tpkBowhetT1JKfcfdSCmAXbS75iATyLD';
print_r("Input: " . $input . PHP_EOL . "Expected: " . $expected . PHP_EOL);
$encoded = $codec->classicAddressToXAddress($input, 123, true);
print_r("Testnet Encoded Address: " . $encoded . PHP_EOL . PHP_EOL);

$input = 'XVLhHMPHU98es4dbozjVtdWzVrDjtV18pX8yuPT7y4xaEHi';
$expected = [
    "classicAddress" => "rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf",
    "tag" => 4294967295,
    "test" => false
];
print_r("Input: " . $input . PHP_EOL . "Expected: " . print_r($expected, true));
$decoded = $codec->xAddressToClassicAddress($input);
print_r("Decoded Address: " . print_r($decoded, true) . PHP_EOL . PHP_EOL);


$valid = $codec->isValidXAddress('XVLhHMPHU98es4dbozjVtdWzVrDjtV18pX8yuPT7y4xaEHi');
//should be: 1
print_r("Test Address validity: " . $valid . PHP_EOL);
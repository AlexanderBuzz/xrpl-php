<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Core\RippleBinaryCodec\RippleBinaryCodec;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

$codec = new RippleBinaryCodec();

/*
$taArray = [
    "TransactionType" => "Payment",
    "Flags" =>  2147483648,
    "Sequence" => 1,
    "Account" => "r9LqNeG6qHxjeUocjvVki2XR35weJ9mZgQ",
    "Destination" => "rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh"
];
*/

$taArray = [
    "TransactionType" => "Payment",
    "Sequence" => 1,
    "Flags" =>  2147483648
];

$decodedTa = "12000022800000002400000001";

print_r('ripple-binary-codec decode example, HEX string to JSON object' .PHP_EOL);

$decoded = $codec->decode($decodedTa);
print_r($decoded);
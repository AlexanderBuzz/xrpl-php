<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Core\RippleBinaryCodec\RippleBinaryCodec;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

$codec = new RippleBinaryCodec();

$taArray = [
    "TransactionType" => "Payment",
    "Flags" =>  2147483648,
    "Sequence" => 1,
    "Account" => "r9LqNeG6qHxjeUocjvVki2XR35weJ9mZgQ",
    "Destination" => "rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh"
];

$taArray = ["TransactionType" => "Payment"];

$decodedTa = "1200002280000000240000000181145B812C9D57731E27A2DA8B1830195F88EF32A3B68314B5F762798A53D543A014CAF8B297CFF8F2F937E8";
//print_r('source: ' .$decodedTa);

$decoded = $codec->decode($decodedTa);
print_r($decoded);

//TODO: implement all types
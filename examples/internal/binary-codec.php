<?php

require __DIR__ . '/../../vendor/autoload.php';

use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

$codec = new BinaryCodec();

/*
$taArray = [
    "TransactionType" => "Payment",
    "Flags" =>  2147483648,
    "Sequence" => 1,
    "Account" => "r9LqNeG6qHxjeUocjvVki2XR35weJ9mZgQ",
    "Destination" => "rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh"
];
*/

/*
print_r('ripple-binary-codec decode example, HEX string to JSON object' . PHP_EOL. PHP_EOL);

$taArray = [
    "TransactionType" => "Payment",
    "Sequence" => 1,
    "Flags" =>  2147483648
];
$encodedTaArray = "12000022800000002400000001";

$decodedTaArray = $codec->decode($encodedTaArray);

print_r("Input: " . $encodedTaArray . PHP_EOL . "Expected: " . print_r($taArray, true));
$decoded = $codec->decode($encodedTaArray);
print_r("Decoded Transaction: " . print_r($decoded, true) . PHP_EOL . PHP_EOL);
*/

//$encodedTa3 = "120000"; //TransactionType: Payment
//$encodedTa3 = "120001";//TransactionType: EscrowCreate

//$decoded = $codec->decode($encodedTaArray);
//$decoded = $codec->decode($taArray);
//print_r($decoded);

$memoArray = [
    "MemoType" => "687474703A2F2F6578616D706C652E636F6D2F6D656D6F2F67656E65726963",
    "MemoData" => "72656E74"
];

$encoded = $codec->encode($memoArray);

print_r($encoded);


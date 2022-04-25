<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Core\RippleBinaryCodec\RippleBinaryCodec;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

/*
const MEMO =
    "{\"Memo\":" .
      "{\"MemoType\":\"687474703A2F2F6578616D706C652E636F6D2F6D656D6F2F67656E65726963\"," .
      "\"MemoData\":\"72656E74\"}}";
const  MEMO_HEX =
    "EA7C1F687474703A2F2F6578616D706C652E636F6D2F6D656D6F2F67656E657269637D0472656E74E1";
$json = "["  . MEMO . "," . MEMO . "]";
$hex = MEMO_HEX . MEMO_HEX . StArray::ARRAY_END_MARKER_HEX;
*/
//$codec = new StArray();

//Test decode

/*
$testOutput = $codec->fromHex($hex)->toJson()->toString();


  @Test
  void decode() {
    assertThat(codec.fromHex(HEX).toJson().toString()).isEqualTo(JSON);
  }

@Test
  void encode() {
assertThat(codec.fromJson(JSON).toHex()).isEqualTo(HEX);
  }

*/

$codec = new RippleBinaryCodec();

$taArray = [
    "TransactionType" => "Payment",
    "Flags" =>  2147483648,
    "Sequence" => 1,
    "Account" => "r9LqNeG6qHxjeUocjvVki2XR35weJ9mZgQ",
    "Destination" => "rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh"
];

$decodedTa = "1200002280000000240000000181145B812C9D57731E27A2DA8B1830195F88EF32A3B68314B5F762798A53D543A014CAF8B297CFF8F2F937E8";
print_r('source: ' .$decodedTa);

$decoded = $codec->decode($decodedTa);
print_r($decoded);
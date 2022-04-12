<?php

use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

const MEMO =
    "{\"Memo\":" .
      "{\"MemoType\":\"687474703A2F2F6578616D706C652E636F6D2F6D656D6F2F67656E65726963\"," .
      "\"MemoData\":\"72656E74\"}}";
const  MEMO_HEX =
    "EA7C1F687474703A2F2F6578616D706C652E636F6D2F6D656D6F2F67656E657269637D0472656E74E1";
$json = "["  . MEMO . "," . MEMO . "]";
$hex = MEMO_HEX . MEMO_HEX . StArray::ARRAY_END_MARKER_HEX;

$codec = new StArray();

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
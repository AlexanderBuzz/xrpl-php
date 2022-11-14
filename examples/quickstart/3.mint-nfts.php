<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . './_const.php';

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Client\JsonRpcClient;

function convertStringToHex(string $in): string {
    $hex = '';
    foreach(str_split($in) as $char) {
        $hex .= dechex(ord($char));
    }

    return strtoupper($hex);
}

$client = new JsonRpcClient(RPC_NFT_DEVNET_URL);
$standbyWallet = $client->fundWallet($client);

$standbyTokenUrl = '';
$standbyFlags = '';
$standbyTransferFee = '';

$transactionBlob = [
    "TransactionType" => "NFTokenMint",
    "Account" => $standbyWallet->getClassicAddress(),
    "URI" => convertStringToHex($standbyTokenUrl),
    "Flags" => (int) $standbyFlags,
    "TransferFee" => (int) $standbyTransferFee,
    "NFTokenTaxon" >= 0 //Required, but if you have no use for it, set to zero.
];
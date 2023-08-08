<?php

require __DIR__ . '/../../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Networks;
use XRPL_PHP\Models\Account\AccountNftsRequest;
use XRPL_PHP\Utils\Utilities;

function convertStringToHex(string $in): string {
    $hex = '';
    foreach(str_split($in) as $char) {
        $hex .= dechex(ord($char));
    }

    return strtoupper($hex);
}

print_r(PHP_EOL . "--- NFT Testnet example ---" . PHP_EOL);

$testnetUrl = Networks::getNetwork('testnet')['jsonRpcUrl'];
$client = new JsonRpcClient($testnetUrl);

$wallet = $client->fundWallet();
print_r("Created wallet - address: {$wallet->getAddress()} seed: {$wallet->getSeed()}" . PHP_EOL);

$tokenUrl = 'ipfs://bafybeigdyrzt5sfp7udm7hu76uh7y26nf4dfuylqabf3oclgtqy55fbzdi'; // Seems to be hardcoded in the examples
$flags = 8; // Sets the tsTransferable flag
$transferFee = 1000; // 1% Fee

$tx = [
    "TransactionType" => "NFTokenMint",
    "Account" => $wallet->getClassicAddress(),
    "URI" => Utilities::convertStringToHex($tokenUrl),
    //"URI" => convertStringToHex($tokenUrl),
    "Flags" => $flags,
    "TransferFee" => $transferFee,
    "NFTokenTaxon" => 0 //Required, but if you have no use for it, set to zero.
];
$preparedTx = $client->autofill($tx);
$signedTx = $wallet->sign($preparedTx);
$txResult = $client->submitAndWait($signedTx['tx_blob']);
print_r("NFTokenMint result:" . PHP_EOL);

print_r($txResult->getResult());

$nftsRequest = new AccountNftsRequest(account: $wallet->getClassicAddress());
$nftsResponse = $client->request($nftsRequest)->wait();

print_r("AccountNftsRequest result:" . PHP_EOL);
print_r($nftsResponse->getResult());


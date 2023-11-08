<?php

require __DIR__ . '/../../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Networks;
use XRPL_PHP\Models\Account\AccountNftsRequest;
use XRPL_PHP\Utils\Utilities;

$testnetUrl = Networks::getNetwork('testnet')['jsonRpcUrl'];
$client = new JsonRpcClient($testnetUrl);

print_r(PHP_EOL . Color::GREEN);
print_r("┌──────────────────┐" . PHP_EOL);
print_r("│ Mint NFT example │" . PHP_EOL);
print_r("└──────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);

print_r(Color::YELLOW . "Funding wallet, please wait..." . PHP_EOL);
$wallet = $client->fundWallet();
print_r(Color::GREEN . "Created wallet - address: " . Color::WHITE . "{$wallet->getAddress()} " . Color::GREEN . "seed: " . Color::WHITE . "{$wallet->getSeed()}" . PHP_EOL);

print_r(Color::YELLOW . "Minting NFT, please wait..." . PHP_EOL);

$tokenUrl = 'ipfs://bafybeigdyrzt5sfp7udm7hu76uh7y26nf4dfuylqabf3oclgtqy55fbzdi'; // Sample URL
$flags = 8; // Sets the tsTransferable flag
$transferFee = 1000; // 1% Fee

$tx = [
    "TransactionType" => "NFTokenMint",
    "Account" => $wallet->getClassicAddress(),
    "URI" => Utilities::convertStringToHex($tokenUrl),
    "Flags" => $flags,
    "TransferFee" => $transferFee,
    "NFTokenTaxon" => 0 //Required, but if you have no use for it, set to zero.
];
$preparedTx = $client->autofill($tx);
$signedTx = $wallet->sign($preparedTx);
$txResult = $client->submitAndWait($signedTx['tx_blob']);
print_r(Color::GREEN . "Non fungible token minted!: TxHash: " . Color::WHITE . "{$txResult->getResult()['hash']}" . PHP_EOL . PHP_EOL);

print_r(Color::RESET . "You can check wallets/accounts and transactions on https://test.bithomp.com"  . PHP_EOL . PHP_EOL);

// print_r(Color::RESET . "AccountNftsRequest result:" . PHP_EOL);
// $nftRequest = new AccountNftsRequest(account: $wallet->getClassicAddress());
// $nftResponse = $client->request($nftRequest)->wait();
// print_r($nftResponse->getResult());

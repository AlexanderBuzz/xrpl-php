<?php

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Mutable NFTs (DynamicNFT, live on Mainnet): an NFT minted with tfMutable can
 * have its URI changed afterwards with NFTokenModify. Without that flag the URI
 * is fixed for the lifetime of the token.
 *
 * https://xrpl.org/docs/references/protocol/transactions/types/nftokenmodify
 */

/**
 * Autofill, sign and submit a transaction, then stop the example if the ledger
 * did not accept it.
 *
 * A tec result still makes it into the ledger, so submitAndWait() returning
 * without an exception is not by itself a success - the result code has to be
 * checked, otherwise a failed step would only surface as a confusing error
 * further down.
 */
function submit(JsonRpcClient $client, Wallet $wallet, array $tx): array
{
    $signedTx = $wallet->sign($client->autofill($tx));
    $result = $client->submitAndWait($signedTx['tx_blob'])->getResult();

    $resultCode = $result['meta']['TransactionResult'];
    if ($resultCode !== 'tesSUCCESS') {
        print_r(Color::RED . "{$tx['TransactionType']} failed with {$resultCode}! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);
        exit(1);
    }

    return $result;
}

print_r(PHP_EOL . Color::GREEN);
print_r("┌────────────────────────────┐" . PHP_EOL);
print_r("│   NFTokenModify example    │" . PHP_EOL);
print_r("└────────────────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);

const NETWORK = 'testnet';

// NFTokenMint flags
const TF_TRANSFERABLE = 0x00000008;
const TF_MUTABLE = 0x00000010;

$client = new JsonRpcClient(NETWORK);

print_r(Color::YELLOW . "Funding minter wallet, please wait..." . PHP_EOL);
$minter = $client->fundWallet();
print_r(Color::GREEN . "Minter: " . Color::WHITE . "{$minter->getAddress()}" . PHP_EOL . PHP_EOL);

/**
 * 1. Mint the NFT. tfMutable is what makes NFTokenModify possible later on, and
 *    it can only be set at mint time.
 */
print_r(Color::YELLOW . "Minting mutable NFT, please wait..." . PHP_EOL);
$mintTx = [
    "TransactionType" => "NFTokenMint",
    "Account" => $minter->getAddress(),
    "NFTokenTaxon" => 0,
    "URI" => bin2hex('https://example.com/nft/before.json'),
    "Flags" => TF_TRANSFERABLE | TF_MUTABLE,
];
$result = submit($client, $minter, $mintTx);

$nfTokenId = $result['meta']['nftoken_id'];
print_r(Color::GREEN . "NFT minted! ID: " . Color::WHITE . "{$nfTokenId}" . PHP_EOL);
print_r(Color::GREEN . "TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 2. Change the URI. Owner may be omitted while the minter still holds the
 *    token, and has to be set once somebody else owns it.
 */
print_r(Color::YELLOW . "Modifying NFT URI, please wait..." . PHP_EOL);
$modifyTx = [
    "TransactionType" => "NFTokenModify",
    "Account" => $minter->getAddress(),
    "NFTokenID" => $nfTokenId,
    "URI" => bin2hex('https://example.com/nft/after.json'),
];
$result = submit($client, $minter, $modifyTx);
print_r(Color::GREEN . "URI modified! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 3. Read the token back so the new URI is visible.
 */
$body = json_encode([
    'method' => 'account_nfts',
    'params' => [['account' => $minter->getAddress()]],
]);
$response = $client->rawSyncRequest('POST', '', $body);

$nfts = json_decode($response->getBody()->getContents(), true)['result']['account_nfts'] ?? [];
foreach ($nfts as $nft) {
    if ($nft['NFTokenID'] === $nfTokenId) {
        print_r(Color::GREEN . "URI in the ledger: " . Color::WHITE . hex2bin($nft['URI']) . PHP_EOL . PHP_EOL);
    }
}

print_r(Color::RESET . "You can check the account and transactions on https://test.bithomp.com" . PHP_EOL . PHP_EOL);

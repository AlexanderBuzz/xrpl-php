<?php

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Full lifecycle of a Multi-Purpose Token (MPTokensV1, live on Mainnet since
 * October 2025): the issuer creates an issuance, a holder authorizes it, the
 * issuer sends MPT to the holder and finally claws it back.
 *
 * https://xrpl.org/docs/concepts/tokens/fungible-tokens/multi-purpose-tokens
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
print_r("┌────────────────────────┐" . PHP_EOL);
print_r("│    MPToken example     │" . PHP_EOL);
print_r("└────────────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);

const NETWORK = 'testnet';

$client = new JsonRpcClient(NETWORK);

print_r(Color::YELLOW . "Funding issuer wallet, please wait..." . PHP_EOL);
$issuer = $client->fundWallet();
print_r(Color::GREEN . "Issuer: " . Color::WHITE . "{$issuer->getAddress()}" . PHP_EOL);

print_r(Color::YELLOW . "Funding holder wallet, please wait..." . PHP_EOL);
$holder = $client->fundWallet();
print_r(Color::GREEN . "Holder: " . Color::WHITE . "{$holder->getAddress()}" . PHP_EOL . PHP_EOL);

/**
 * 1. The issuer creates the issuance.
 *
 * AssetScale 2 means the smallest unit is 0.01 of the token, TransferFee is
 * given in units of 1/1000 of a percent. The flags make the token
 * transferable and clawback enabled.
 */
const TF_MPT_CAN_TRANSFER = 0x00000020;
const TF_MPT_CAN_CLAWBACK = 0x00000040;

print_r(Color::YELLOW . "Creating MPToken issuance, please wait..." . PHP_EOL);
$issuanceTx = [
    "TransactionType" => "MPTokenIssuanceCreate",
    "Account" => $issuer->getAddress(),
    "AssetScale" => 2,
    "TransferFee" => 314,
    "MaximumAmount" => "100000000",
    "MPTokenMetadata" => bin2hex('{"name":"Example MPT"}'),
    "Flags" => TF_MPT_CAN_TRANSFER | TF_MPT_CAN_CLAWBACK,
];
$result = submit($client, $issuer, $issuanceTx);

$issuanceId = $result['meta']['mpt_issuance_id'];
print_r(Color::GREEN . "Issuance created! ID: " . Color::WHITE . "{$issuanceId}" . PHP_EOL);
print_r(Color::GREEN . "TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 2. The holder has to opt in before it can receive the token.
 */
print_r(Color::YELLOW . "Authorizing holder, please wait..." . PHP_EOL);
$authorizeTx = [
    "TransactionType" => "MPTokenAuthorize",
    "Account" => $holder->getAddress(),
    "MPTokenIssuanceID" => $issuanceId,
];
$result = submit($client, $holder, $authorizeTx);
print_r(Color::GREEN . "Holder authorized! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 3. An MPT amount is an object of mpt_issuance_id and value, where value is
 *    given in the smallest unit - with AssetScale 2, "1000" means 10.00.
 */
print_r(Color::YELLOW . "Sending 10.00 MPT to the holder, please wait..." . PHP_EOL);
$paymentTx = [
    "TransactionType" => "Payment",
    "Account" => $issuer->getAddress(),
    "Destination" => $holder->getAddress(),
    "Amount" => [
        "mpt_issuance_id" => $issuanceId,
        "value" => "1000",
    ],
];
$result = submit($client, $issuer, $paymentTx);
print_r(Color::GREEN . "Payment done! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 4. Because the issuance was created with tfMPTCanClawback, the issuer can
 *    claw the token back.
 */
print_r(Color::YELLOW . "Clawing back 4.00 MPT, please wait..." . PHP_EOL);
$clawbackTx = [
    "TransactionType" => "Clawback",
    "Account" => $issuer->getAddress(),
    "Holder" => $holder->getAddress(),
    "Amount" => [
        "mpt_issuance_id" => $issuanceId,
        "value" => "400",
    ],
];
$result = submit($client, $issuer, $clawbackTx);
print_r(Color::GREEN . "Clawback done! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

print_r(Color::RESET . "You can check the accounts and transactions on https://test.bithomp.com" . PHP_EOL . PHP_EOL);

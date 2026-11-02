<?php

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Utils\Utilities;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

/**
 * Permission Delegation (XLS-75) on the Testnet, with the roles of the
 * specification: Isaac lets Alice act for him, Bob receives.
 *
 * Isaac grants Alice two permissions with DelegateSet: the whole Payment
 * type and the granular AccountDomainSet. Alice then sends a Payment and an
 * AccountSet that are Isaac's (Account = Isaac) but carry Delegate = Alice:
 * she signs them with her own key and pays their fees, Isaac's Sequence
 * moves. A TrustSet she was not granted is refused with
 * terNO_DELEGATE_PERMISSION. Finally Isaac revokes everything with an empty
 * Permissions list.
 *
 * https://xrpl.org/docs/concepts/accounts/permission-delegation
 */

$client = new JsonRpcClient('testnet');

print_r(Color::YELLOW . 'Funding three Testnet wallets' . Color::WHITE . PHP_EOL);
$isaac = $client->fundWallet();
$alice = $client->fundWallet();
$bob = $client->fundWallet();

function submit(JsonRpcClient $client, Wallet $signer, array $tx, string $label): void
{
    $signed = $signer->sign($client->autofill($tx));
    $result = $client->submitAndWait($signed['tx_blob'])->getResult();
    $resultCode = $result['meta']['TransactionResult'];
    if ($resultCode !== 'tesSUCCESS') {
        print_r(Color::RED . "{$label} failed with {$resultCode}! TxHash: " . Color::WHITE . "{$signed['hash']}" . PHP_EOL);
        exit(1);
    }
    print_r(Color::GREEN . "{$label}: " . Color::WHITE . "{$signed['hash']}" . PHP_EOL);
}

// 1. Isaac grants Alice permissions. A transaction type grants all of it, a
//    granular permission one narrow use (Definitions::GRANULAR_PERMISSIONS).
submit($client, $isaac, [
    'TransactionType' => 'DelegateSet',
    'Account' => $isaac->getAddress(),
    'Authorize' => $alice->getAddress(),
    'Permissions' => [
        ['Permission' => ['PermissionValue' => 'Payment']],
        ['Permission' => ['PermissionValue' => 'AccountDomainSet']],
    ],
], 'Isaac delegates Payment and AccountDomainSet to Alice');

// 2. Alice acts for Isaac: Account is Isaac, Delegate is Alice, Alice signs.
//    autofill() takes Isaac's Sequence; the fee comes out of Alice's balance.
$isaacBefore = $client->getXrpBalance($isaac->getAddress());
$aliceBefore = $client->getXrpBalance($alice->getAddress());

submit($client, $alice, [
    'TransactionType' => 'Payment',
    'Account' => $isaac->getAddress(),
    'Delegate' => $alice->getAddress(),
    'Destination' => $bob->getAddress(),
    'Amount' => xrpToDrops('1'),
], 'Alice pays 1 XRP of Isaac\'s to Bob');

submit($client, $alice, [
    'TransactionType' => 'AccountSet',
    'Account' => $isaac->getAddress(),
    'Delegate' => $alice->getAddress(),
    'Domain' => Utilities::convertStringToHex('example.com'),
], 'Alice sets Isaac\'s Domain');

print_r('  Isaac: ' . $isaacBefore . ' -> ' . $client->getXrpBalance($isaac->getAddress()) . ' XRP (the payment, no fee)' . PHP_EOL);
print_r('  Alice: ' . $aliceBefore . ' -> ' . $client->getXrpBalance($alice->getAddress()) . ' XRP (both fees)' . PHP_EOL);

// 3. What Isaac did not grant is refused before it reaches a ledger
$trustSet = $alice->sign($client->autofill([
    'TransactionType' => 'TrustSet',
    'Account' => $isaac->getAddress(),
    'Delegate' => $alice->getAddress(),
    'LimitAmount' => ['currency' => 'USD', 'issuer' => $bob->getAddress(), 'value' => '100'],
]));
$engineResult = $client->submit($trustSet['tx_blob'])->getResult()['engine_result'] ?? 'unknown';
print_r(Color::YELLOW . "A TrustSet for Isaac, not granted: " . Color::WHITE . $engineResult . PHP_EOL);

// 4. An empty list revokes every permission and deletes the Delegate object
submit($client, $isaac, [
    'TransactionType' => 'DelegateSet',
    'Account' => $isaac->getAddress(),
    'Authorize' => $alice->getAddress(),
    'Permissions' => [],
], 'Isaac revokes Alice\'s permissions');

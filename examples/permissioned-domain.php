<?php

require __DIR__ . '/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Credentials and Permissioned Domains, the building blocks of the
 * institutional DeFi stack:
 *
 *   1. an issuer attests something about a subject (CredentialCreate)
 *   2. the subject accepts the credential (CredentialAccept)
 *   3. a domain owner opens a domain that only accepts that credential
 *      (PermissionedDomainSet)
 *   4. the subject places an offer inside that domain (OfferCreate.DomainID)
 *
 * https://xrpl.org/docs/concepts/decentralized-storage/credentials
 * https://xrpl.org/docs/concepts/tokens/decentralized-exchange/permissioned-dexes
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
print_r("┌──────────────────────────────────────┐" . PHP_EOL);
print_r("│  Credentials & Permissioned Domain   │" . PHP_EOL);
print_r("└──────────────────────────────────────┘" . PHP_EOL);
print_r(PHP_EOL . Color::RESET);

const NETWORK = 'testnet';

$client = new JsonRpcClient(NETWORK);

print_r(Color::YELLOW . "Funding issuer wallet, please wait..." . PHP_EOL);
$issuer = $client->fundWallet();
print_r(Color::GREEN . "Issuer:  " . Color::WHITE . "{$issuer->getAddress()}" . PHP_EOL);

print_r(Color::YELLOW . "Funding subject wallet, please wait..." . PHP_EOL);
$subject = $client->fundWallet();
print_r(Color::GREEN . "Subject: " . Color::WHITE . "{$subject->getAddress()}" . PHP_EOL . PHP_EOL);

// CredentialType is an arbitrary hex blob of up to 64 bytes
$credentialType = bin2hex('KYC');

/**
 * 1. The issuer attests the credential.
 */
print_r(Color::YELLOW . "Creating credential, please wait..." . PHP_EOL);
$createTx = [
    "TransactionType" => "CredentialCreate",
    "Account" => $issuer->getAddress(),
    "Subject" => $subject->getAddress(),
    "CredentialType" => $credentialType,
    "URI" => bin2hex('https://example.com/kyc/1'),
];
$result = submit($client, $issuer, $createTx);
print_r(Color::GREEN . "Credential created! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 2. A credential only counts as valid once the subject has accepted it.
 */
print_r(Color::YELLOW . "Accepting credential, please wait..." . PHP_EOL);
$acceptTx = [
    "TransactionType" => "CredentialAccept",
    "Account" => $subject->getAddress(),
    "Issuer" => $issuer->getAddress(),
    "CredentialType" => $credentialType,
];
$result = submit($client, $subject, $acceptTx);
print_r(Color::GREEN . "Credential accepted! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 3. The issuer opens a domain that accepts exactly this credential. Omitting
 *    DomainID creates a new domain, passing one modifies an existing domain.
 */
print_r(Color::YELLOW . "Creating permissioned domain, please wait..." . PHP_EOL);
$domainTx = [
    "TransactionType" => "PermissionedDomainSet",
    "Account" => $issuer->getAddress(),
    "AcceptedCredentials" => [
        [
            "Credential" => [
                "Issuer" => $issuer->getAddress(),
                "CredentialType" => $credentialType,
            ]
        ]
    ],
];
$result = submit($client, $issuer, $domainTx);

$domainId = null;
foreach ($result['meta']['AffectedNodes'] as $node) {
    if (($node['CreatedNode']['LedgerEntryType'] ?? null) === 'PermissionedDomain') {
        $domainId = $node['CreatedNode']['LedgerIndex'];
    }
}
print_r(Color::GREEN . "Domain created! ID: " . Color::WHITE . "{$domainId}" . PHP_EOL);
print_r(Color::GREEN . "TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

/**
 * 4. The subject holds the required credential, so it may trade inside the
 *    domain. Without DomainID this would be an ordinary open-DEX offer.
 */
print_r(Color::YELLOW . "Placing offer inside the domain, please wait..." . PHP_EOL);
$offerTx = [
    "TransactionType" => "OfferCreate",
    "Account" => $subject->getAddress(),
    "TakerGets" => "1000000",
    "TakerPays" => [
        "currency" => "USD",
        "issuer" => $issuer->getAddress(),
        "value" => "1",
    ],
    "DomainID" => $domainId,
];
$result = submit($client, $subject, $offerTx);
print_r(Color::GREEN . "Offer placed! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);

print_r(Color::RESET . "You can check the accounts and transactions on https://test.bithomp.com" . PHP_EOL . PHP_EOL);

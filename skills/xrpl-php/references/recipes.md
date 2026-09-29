# Recipes

Working patterns, each taken from a file under `examples/` that runs against the Testnet. All assume a `$client = new JsonRpcClient('testnet')` and funded wallets from `$client->fundWallet()`. `submit()` below is the helper every example uses:

```php
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

function submit(JsonRpcClient $client, Wallet $wallet, array $tx): array
{
    $signed = $wallet->sign($client->autofill($tx));
    $result = $client->submitAndWait($signed['tx_blob'])->getResult();
    if ($result['meta']['TransactionResult'] !== 'tesSUCCESS') {
        throw new RuntimeException("{$tx['TransactionType']} failed: {$result['meta']['TransactionResult']}");
    }
    return $result;
}
```

## Issue a token (IOU): issuer settings, trust line, payment

`examples/quickstart/2.create-trustline-send-currency.php`, `examples/token-create.php`. The issuer ("cold" wallet) enables rippling so the token can move between third parties; the holder opens a trust line up to a limit; the issuer pays the token into it.

```php
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\AccountSetAsfFlags;

submit($client, $issuer, [
    'TransactionType' => 'AccountSet',
    'Account' => $issuer->getAddress(),
    'SetFlag' => AccountSetAsfFlags::asfDefaultRipple,
]);
submit($client, $holder, [
    'TransactionType' => 'TrustSet',
    'Account' => $holder->getAddress(),
    'LimitAmount' => ['currency' => 'USD', 'issuer' => $issuer->getAddress(), 'value' => '10000'],
]);
submit($client, $issuer, [
    'TransactionType' => 'Payment',
    'Account' => $issuer->getAddress(),
    'Destination' => $holder->getAddress(),
    'Amount' => ['currency' => 'USD', 'issuer' => $issuer->getAddress(), 'value' => '1000'],
]);
$client->getBalances($holder->getAddress());
```

`SetFlag` takes one flag per transaction. Clawback (`asfAllowTrustLineClawback`) has to be enabled before the issuer has any trust line. Currency codes with more than three characters: `CoreUtilities::encodeCustomCurrency('MyToken')` (`examples/custom-currency-codes.php`).

## RLUSD or USDC

`examples/rlusd.php`. The stablecoin classes know the issuer per network.

```php
use Hardcastle\XRPL_PHP\Core\Stablecoin\RLUSD;

submit($client, $wallet, [
    'TransactionType' => 'TrustSet',
    'Account' => $wallet->getAddress(),
    'LimitAmount' => RLUSD::getAmount('testnet', '10000'),
]);
// then pay with 'Amount' => RLUSD::getAmount('testnet', '25')
```

## Multi-Purpose Token (MPT): issue, authorize, send, claw back

`examples/mptoken.php`. The issuance ID comes from the metadata of the create transaction.

```php
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\MPTokenIssuanceCreateFlags;

$result = submit($client, $issuer, [
    'TransactionType' => 'MPTokenIssuanceCreate',
    'Account' => $issuer->getAddress(),
    'AssetScale' => 2,
    'TransferFee' => 314,                       // 0.314 %, in 1/1000 of a percent
    'MaximumAmount' => '100000000',
    'MPTokenMetadata' => bin2hex('{"name":"Example MPT"}'),
    'Flags' => MPTokenIssuanceCreateFlags::tfMPTCanTransfer | MPTokenIssuanceCreateFlags::tfMPTCanClawback,
]);
$issuanceId = $result['meta']['mpt_issuance_id'];

submit($client, $holder, ['TransactionType' => 'MPTokenAuthorize', 'Account' => $holder->getAddress(), 'MPTokenIssuanceID' => $issuanceId]);
submit($client, $issuer, [
    'TransactionType' => 'Payment',
    'Account' => $issuer->getAddress(),
    'Destination' => $holder->getAddress(),
    'Amount' => ['mpt_issuance_id' => $issuanceId, 'value' => '1000'],
]);
submit($client, $issuer, [
    'TransactionType' => 'Clawback',
    'Account' => $issuer->getAddress(),
    'Holder' => $holder->getAddress(),
    'Amount' => ['mpt_issuance_id' => $issuanceId, 'value' => '400'],
]);
```

## Mint an NFT, change its URI

`examples/quickstart/3.mint-nfts.php`, `examples/nftoken-modify.php`.

```php
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\NFTokenMintFlags;
use Hardcastle\XRPL_PHP\Utils\Utilities;

$result = submit($client, $wallet, [
    'TransactionType' => 'NFTokenMint',
    'Account' => $wallet->getAddress(),
    'URI' => Utilities::convertStringToHex('https://example.com/nft/1.json'),
    'Flags' => NFTokenMintFlags::tfTransferable | NFTokenMintFlags::tfMutable,
    'TransferFee' => 5000,                      // 5 %, in 1/100,000
    'NFTokenTaxon' => 0,
]);
$nftokenId = $result['meta']['nftoken_id'];

submit($client, $wallet, [                     // tfMutable allows this
    'TransactionType' => 'NFTokenModify',
    'Account' => $wallet->getAddress(),
    'NFTokenID' => $nftokenId,
    'URI' => Utilities::convertStringToHex('https://example.com/nft/1-v2.json'),
]);
```

Offers: `NFTokenCreateOffer` with `NFTokenCreateOfferFlags::tfSellNFToken`, accepted through `NFTokenAcceptOffer`; `AccountNftsRequest` lists an account's NFTs.

## Credentials and a permissioned domain

`examples/permissioned-domain.php`. An issuer creates a credential for a subject, the subject accepts it, the issuer opens a domain that accepts that credential, and an `OfferCreate` with `DomainID` trades in the permissioned order book.

```php
$credentialType = bin2hex('KYC');
submit($client, $issuer, [
    'TransactionType' => 'CredentialCreate',
    'Account' => $issuer->getAddress(),
    'Subject' => $subject->getAddress(),
    'CredentialType' => $credentialType,
    'URI' => bin2hex('https://example.com/kyc/1'),
]);
submit($client, $subject, [
    'TransactionType' => 'CredentialAccept',
    'Account' => $subject->getAddress(),
    'Issuer' => $issuer->getAddress(),
    'CredentialType' => $credentialType,
]);
$result = submit($client, $issuer, [
    'TransactionType' => 'PermissionedDomainSet',
    'Account' => $issuer->getAddress(),
    'AcceptedCredentials' => [
        ['Credential' => ['Issuer' => $issuer->getAddress(), 'CredentialType' => $credentialType]],
    ],
]);
// DomainID: the LedgerIndex of the CreatedNode of type PermissionedDomain in $result['meta']['AffectedNodes']
```

## AMM: create a pool, claw back out of it

`examples/amm-clawback.php`. `Asset`/`Asset2` name the pool's assets (`['currency' => 'XRP']` for XRP); `AMMClawback` needs `asfAllowTrustLineClawback` on the issuer.

```php
submit($client, $holder, [
    'TransactionType' => 'AMMCreate',
    'Account' => $holder->getAddress(),
    'Amount' => ['currency' => 'USD', 'issuer' => $issuer->getAddress(), 'value' => '500'],
    'Amount2' => '10000000',
    'TradingFee' => 500,                        // 0.5 %, in 1/100,000
]);
submit($client, $issuer, [
    'TransactionType' => 'AMMClawback',
    'Account' => $issuer->getAddress(),
    'Holder' => $holder->getAddress(),
    'Asset' => ['currency' => 'USD', 'issuer' => $issuer->getAddress()],
    'Asset2' => ['currency' => 'XRP'],
    'Amount' => ['currency' => 'USD', 'issuer' => $issuer->getAddress(), 'value' => '200'],
]);
```

`AmmInfoRequest` reads the pool. Deposits and withdrawals pick their mode through `AMMDepositFlags` / `AMMWithdrawFlags` (exactly one mode flag each).

## Payment channel: open, sign claims offline, verify, redeem, close

`examples/payment-channel.php`. Claims are not transactions; both sides work without a node.

```php
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\PaymentChannelClaimFlags;

$result = submit($client, $payer, [
    'TransactionType' => 'PaymentChannelCreate',
    'Account' => $payer->getAddress(),
    'Destination' => $payee->getAddress(),
    'Amount' => '10000000',
    'SettleDelay' => 60,
    'PublicKey' => $payer->getPublicKey(),
]);
// $channelId: the LedgerIndex of the CreatedNode of type PayChannel in $result['meta']['AffectedNodes']

$signature = $payer->signPaymentChannelClaim($channelId, '3000000');                 // payer, offline
$ok = Wallet::verifyPaymentChannelClaim($channelId, '3000000', $signature, $payer->getPublicKey()); // payee, offline

submit($client, $payee, [                     // payee redeems the latest claim
    'TransactionType' => 'PaymentChannelClaim',
    'Account' => $payee->getAddress(),
    'Channel' => $channelId,
    'Balance' => '3000000',
    'Amount' => '3000000',
    'Signature' => $signature,
    'PublicKey' => $payer->getPublicKey(),
]);
submit($client, $payer, [                     // payer schedules the close (SettleDelay applies)
    'TransactionType' => 'PaymentChannelClaim',
    'Account' => $payer->getAddress(),
    'Channel' => $channelId,
    'Flags' => PaymentChannelClaimFlags::tfClose,
]);
```

## Escrow and checks

Time-based escrow: `EscrowCreate` with `Amount`, `Destination` and `FinishAfter` (ripple epoch seconds: Unix time minus 946684800), later `EscrowFinish` with `Owner` and `OfferSequence` (the `Sequence` of the create). Tokens can be escrowed too (`Amount` as an IOU or MPT). Checks: `CheckCreate` with `SendMax`, cashed by the destination with `CheckCash` and either `Amount` or `DeliverMin`.

## Reading what a transaction created

New ledger objects show up in `$result['meta']['AffectedNodes']` as `CreatedNode` entries; their `LedgerIndex` is the object's ID:

```php
foreach ($result['meta']['AffectedNodes'] as $node) {
    if (($node['CreatedNode']['LedgerEntryType'] ?? null) === 'PayChannel') {
        $channelId = $node['CreatedNode']['LedgerIndex'];
    }
}
```

`AccountObjectsRequest` lists an account's objects afterwards; `LedgerEntryRequest` fetches one by ID.

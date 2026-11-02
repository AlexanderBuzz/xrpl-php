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

## Batch: several transactions at once, two accounts atomically

`examples/batch.php`. XLS-56; two to eight inner transactions, one mode flag. Inner transactions are never signed on their own and carry no fee; `autofill()` takes care of that and of their sequences.

```php
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\BatchFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\TxRequest;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

// One account: signed like any transaction
$batch = $client->autofill([
    'TransactionType' => 'Batch',
    'Account' => $alice->getAddress(),
    'Flags' => BatchFlags::tfAllOrNothing,        // or tfOnlyOne, tfUntilFailure, tfIndependent
    'RawTransactions' => [
        ['RawTransaction' => ['TransactionType' => 'Payment', 'Account' => $alice->getAddress(), 'Destination' => $bob->getAddress(), 'Amount' => xrpToDrops('1')]],
        ['RawTransaction' => ['TransactionType' => 'Payment', 'Account' => $alice->getAddress(), 'Destination' => $carol->getAddress(), 'Amount' => xrpToDrops('2')]],
    ],
]);
$signed = $alice->sign($batch);
$result = $client->submitAndWait($signed['tx_blob'])->getResult();
if ($result['meta']['TransactionResult'] !== 'tesSUCCESS') {   // then check the inner transactions, as below
    throw new RuntimeException('Batch failed: ' . $result['meta']['TransactionResult']);
}

// Two accounts, atomic swap: Alice prepares, Bob signs his part, Alice signs the outer transaction
$batch = $client->autofill([
    'TransactionType' => 'Batch',
    'Account' => $alice->getAddress(),
    'Flags' => BatchFlags::tfAllOrNothing,
    'RawTransactions' => [
        ['RawTransaction' => ['TransactionType' => 'Payment', 'Account' => $alice->getAddress(), 'Destination' => $bob->getAddress(), 'Amount' => xrpToDrops('1')]],
        ['RawTransaction' => ['TransactionType' => 'Payment', 'Account' => $bob->getAddress(), 'Destination' => $alice->getAddress(), 'Amount' => xrpToDrops('2')]],
    ],
    'BatchSignatures' => 1,                       // how many BatchSigners signatures the fee covers; a hint, not a field
]);
$byBob = $bob->signBatch($batch);                 // Bob's side; the Batch must not change from here on
$combined = Wallet::combineBatchSigners([$byBob]); // Alice's side: every copy that came back
$signed = $alice->sign($combined);                // Fee was set by the autofill above and stays
$result = $client->submitAndWait($signed['tx_blob'])->getResult();

// The outer tesSUCCESS does not mean the inner transactions applied. Each applied one is a
// transaction of its own whose meta names the Batch in ParentBatchID; one that was not applied
// is not in the ledger, so its lookup is an ErrorResponse with txnNotFound; any other error is a
// failed lookup that says nothing about the outcome.
if ($result['meta']['TransactionResult'] !== 'tesSUCCESS') {
    throw new RuntimeException('Batch failed: ' . $result['meta']['TransactionResult']);
}
foreach ((new BinaryCodec())->innerTransactionIds($combined) as $innerId) {
    $response = $client->syncRequest(new TxRequest($innerId));
    if ($response instanceof ErrorResponse) {
        if ($response->getStatusCode() !== 200 || $response->getError() !== 'txnNotFound') {
            throw new RuntimeException("Inner transaction {$innerId}: outcome unknown ({$response->getError()}), look it up again");
        }
        throw new RuntimeException("Inner transaction {$innerId} was not applied");   // under tfAllOrNothing
    }
    $inner = $response->getResult();
    if ($inner['meta']['TransactionResult'] !== 'tesSUCCESS' || ($inner['meta']['ParentBatchID'] ?? null) !== $result['hash']) {
        throw new RuntimeException("Inner transaction {$innerId}: {$inner['meta']['TransactionResult']}");
    }
}
// With tfOnlyOne, tfUntilFailure or tfIndependent, record which inner transactions applied instead of throwing.
```

## Delegation: another account sends on your behalf

`examples/delegation.php`. XLS-75. Isaac lets Alice send payments for him and set his domain; Alice signs with her own key and pays the fee, the transaction is Isaac's.

```php
use Hardcastle\XRPL_PHP\Utils\Utilities;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

// Isaac grants: whole transaction types by name, or granular permissions (Definitions::GRANULAR_PERMISSIONS)
submit($client, $isaac, [
    'TransactionType' => 'DelegateSet',
    'Account' => $isaac->getAddress(),
    'Authorize' => $alice->getAddress(),
    'Permissions' => [                                                  // at most 10; replaces the old list
        ['Permission' => ['PermissionValue' => 'Payment']],
        ['Permission' => ['PermissionValue' => 'AccountDomainSet']],   // AccountSet with Domain only
    ],
]);

// Alice sends for Isaac: Account is Isaac, Delegate is Alice, Alice signs; Isaac's Sequence, Alice's fee
submit($client, $alice, [
    'TransactionType' => 'Payment',
    'Account' => $isaac->getAddress(),
    'Delegate' => $alice->getAddress(),
    'Destination' => $bob->getAddress(),
    'Amount' => xrpToDrops('1'),
]);
submit($client, $alice, [
    'TransactionType' => 'AccountSet',
    'Account' => $isaac->getAddress(),
    'Delegate' => $alice->getAddress(),
    'Domain' => Utilities::convertStringToHex('example.com'),
]);

// Isaac revokes everything: an empty list deletes the Delegate object
submit($client, $isaac, [
    'TransactionType' => 'DelegateSet',
    'Account' => $isaac->getAddress(),
    'Authorize' => $alice->getAddress(),
    'Permissions' => [],
]);
```

Anything the permissions do not cover (here a `TrustSet`, or any Payment after revoking) fails with `terNO_DELEGATE_PERMISSION`, which `submit()` reports as `engine_result` and which never reaches a ledger. `AccountSet`, `SetRegularKey`, `SignerListSet`, `DelegateSet`, `AccountDelete` and `Batch` cannot be granted as types (`temMALFORMED`); the inner transactions of a Batch can be delegated, see the Batch signing rules in `SKILL.md`. Until `fixCleanup3_4_0` is active, do not grant `PaymentBurn`.

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

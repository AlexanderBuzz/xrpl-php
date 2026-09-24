---
layout: documentation
title: Flags
current_menu: flags
---

# Flags

Transactions and ledger entries carry a `Flags` field, a 32 bit integer whose
bits mean different things per type. The SDK provides every flag rippled 3.4.0
knows as a constant, under the names rippled and xrpl.js use, so you never
have to look a number up.

### Transaction flags

One class per transaction type that has flags, in
`Hardcastle\XRPL_PHP\Models\Transaction\Flags`. Combine flags with `|`:

```php
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\OfferCreateFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\PaymentFlags;

$offer = [
    'TransactionType' => 'OfferCreate',
    // ...
    'Flags' => OfferCreateFlags::tfSell | OfferCreateFlags::tfFillOrKill,
];

$payment = [
    'TransactionType' => 'Payment',
    // ...
    'Flags' => PaymentFlags::tfPartialPayment,
    'DeliverMin' => '900000',
];
```

`GlobalFlags` holds the two flags every type accepts, `tfFullyCanonicalSig`
and `tfInnerBatchTxn`.

The available classes: `AccountSetFlags`, `AMMClawbackFlags`,
`AMMDepositFlags`, `AMMWithdrawFlags`, `BatchFlags`, `EnableAmendmentFlags`,
`GlobalFlags`, `LoanManageFlags`, `LoanPayFlags`, `LoanSetFlags`,
`MPTokenAuthorizeFlags`, `MPTokenIssuanceCreateFlags`,
`MPTokenIssuanceSetFlags`, `NFTokenCreateOfferFlags`, `NFTokenMintFlags`,
`OfferCreateFlags`, `PaymentChannelClaimFlags`, `PaymentFlags`,
`SponsorshipSetFlags`, `SponsorshipTransferFlags`, `TrustSetFlags`,
`VaultCreateFlags`, `XChainModifyBridgeFlags`.

### AccountSet: SetFlag and ClearFlag

AccountSet changes account settings through `SetFlag` and `ClearFlag`, which
take one `asf` value each, not a bit mask. They live in `AccountSetAsfFlags`:

```php
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\AccountSetAsfFlags;

$tx = [
    'TransactionType' => 'AccountSet',
    'Account' => $wallet->getAddress(),
    'SetFlag' => AccountSetAsfFlags::asfDefaultRipple,
];
```

### Ledger entry flags

Objects read from the ledger carry `lsf` flags. One class per entry type in
`Hardcastle\XRPL_PHP\Models\Ledger\Flags`: `AccountRootFlags`,
`CredentialFlags`, `DirectoryNodeFlags`, `LoanFlags`, `MPTokenFlags`,
`MPTokenIssuanceFlags`, `NFTokenOfferFlags`, `OfferFlags`, `RippleStateFlags`,
`SignerListFlags`, `SponsorshipFlags`, `VaultFlags`.

### Reading flags

Every flags class can test a value and name the flags set in it:

```php
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\AccountRootFlags;

$accountRoot = $client->request(new AccountInfoRequest($address))->getResult()['account_data'];

AccountRootFlags::has($accountRoot['Flags'], AccountRootFlags::lsfRequireDestTag); // bool
AccountRootFlags::parse($accountRoot['Flags']); // e.g. ['lsfRequireDestTag', 'lsfDefaultRipple']
AccountRootFlags::all();                        // ['lsfPasswordSpent' => 65536, ...]
```

### Where the values come from

`definitions.json` carries the flag tables of rippled 3.4.0
(`TRANSACTION_FLAGS`, `ACCOUNT_SET_FLAGS`, `LEDGER_ENTRY_FLAGS`), verified
against `TxFlags.h` and `LedgerFormats.h`. The constants are checked against
those tables in the test suite, and `Definitions` exposes them at runtime
through `getTransactionFlags()`, `getAccountSetFlags()` and
`getLedgerEntryFlags()`, so definitions for another network can bring their
own.

Flags of amendments that are not active on Mainnet (Batch, Lending Protocol,
Single Asset Vault, Sponsorship, Confidential MPT) are included for
completeness and say so in their docblocks.

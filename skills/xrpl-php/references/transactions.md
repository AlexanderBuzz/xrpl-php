# Transaction models

Generated from the model classes and `TRANSACTION_FORMATS` in `definitions.json` (rippled 3.4.0). Every model extends `Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction`, takes the transaction as an array in its constructor, validates the `TransactionType` against the class, and gives the array back through `toArray()`. A plain array with the same keys works everywhere a model does; the models exist for type checking and discoverability.

```php
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\Payment;

$payment = new Payment([
    'TransactionType' => 'Payment',
    'Account' => $wallet->getAddress(),
    'Destination' => $destination,
    'Amount' => '1000000',
]);
$signed = $wallet->sign($client->autofill($payment));
```

"Required" is what rippled's format says; `Sequence`, `Fee`, `LastLedgerSequence` and `SigningPubKey` are required too but come from `autofill()` and `sign()`. Type is the codec class the field is serialized with: `Amount` takes drops as a string, an IOU as `['currency','issuer','value']` or an MPT as `['mpt_issuance_id','value']`; `AccountId` an r-address; `Hash256` 64 hex characters; `Blob` hex; `UnsignedInt*` an integer; `StArray` a list of single-key objects.

## Common fields (every type)

| Field | Type | |
|---|---|---|
| `Account` | `AccountId` | required |
| `TransactionType` | `UnsignedInt16` | required |
| `Fee` | `Amount` | required |
| `Sequence` | `UnsignedInt32` | required |
| `AccountTxnID` | `Hash256` | optional |
| `Delegate` | `AccountId` | optional |
| `Flags` | `UnsignedInt32` | optional |
| `LastLedgerSequence` | `UnsignedInt32` | optional |
| `Memos` | `StArray` | optional |
| `NetworkID` | `UnsignedInt32` | optional |
| `Signers` | `StArray` | optional |
| `SourceTag` | `UnsignedInt32` | optional |
| `SigningPubKey` | `Blob` | required |
| `TicketSequence` | `UnsignedInt32` | optional |
| `TxnSignature` | `Blob` | optional |

## AMMBid

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AMMBid`

| Field | Type | |
|---|---|---|
| `Asset` | `Issue` | required |
| `Asset2` | `Issue` | required |
| `BidMin` | `Amount` | optional |
| `BidMax` | `Amount` | optional |
| `AuthAccounts` | `StArray` | optional |

## AMMClawback

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AMMClawback`

| Field | Type | |
|---|---|---|
| `Holder` | `AccountId` | required |
| `Asset` | `Issue` | required |
| `Asset2` | `Issue` | required |
| `Amount` | `Amount` | optional |

## AMMCreate

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AMMCreate`

| Field | Type | |
|---|---|---|
| `Amount` | `Amount` | required |
| `Amount2` | `Amount` | required |
| `TradingFee` | `UnsignedInt16` | required |

## AMMDelete

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AMMDelete`

| Field | Type | |
|---|---|---|
| `Asset` | `Issue` | required |
| `Asset2` | `Issue` | required |

## AMMDeposit

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AMMDeposit`

| Field | Type | |
|---|---|---|
| `Asset` | `Issue` | required |
| `Asset2` | `Issue` | required |
| `Amount` | `Amount` | optional |
| `Amount2` | `Amount` | optional |
| `EPrice` | `Amount` | optional |
| `LPTokenOut` | `Amount` | optional |
| `TradingFee` | `UnsignedInt16` | optional |

## AMMVote

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AMMVote`

| Field | Type | |
|---|---|---|
| `Asset` | `Issue` | required |
| `Asset2` | `Issue` | required |
| `TradingFee` | `UnsignedInt16` | required |

## AMMWithdraw

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AMMWithdraw`

| Field | Type | |
|---|---|---|
| `Asset` | `Issue` | required |
| `Asset2` | `Issue` | required |
| `Amount` | `Amount` | optional |
| `Amount2` | `Amount` | optional |
| `EPrice` | `Amount` | optional |
| `LPTokenIn` | `Amount` | optional |

## AccountDelete

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AccountDelete`

| Field | Type | |
|---|---|---|
| `Destination` | `AccountId` | required |
| `DestinationTag` | `UnsignedInt32` | optional |
| `CredentialIDs` | `Vector256` | optional |

## AccountSet

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AccountSet`

| Field | Type | |
|---|---|---|
| `ClearFlag` | `UnsignedInt32` | optional |
| `Domain` | `Blob` | optional |
| `EmailHash` | `Hash128` | optional |
| `MessageKey` | `Blob` | optional |
| `NFTokenMinter` | `AccountId` | optional |
| `SetFlag` | `UnsignedInt32` | optional |
| `TransferRate` | `UnsignedInt32` | optional |
| `TickSize` | `UnsignedInt8` | optional |
| `WalletLocator` | `Hash256` | optional |
| `WalletSize` | `UnsignedInt32` | optional |

## Batch

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\Batch`

| Field | Type | |
|---|---|---|
| `RawTransactions` | `StArray` | required |
| `BatchSigners` | `StArray` | optional |

## CheckCancel

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\CheckCancel`

| Field | Type | |
|---|---|---|
| `CheckID` | `Hash256` | required |

## CheckCash

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\CheckCash`

| Field | Type | |
|---|---|---|
| `CheckID` | `Hash256` | required |
| `Amount` | `Amount` | optional |
| `DeliverMin` | `Hash256` | optional |

## CheckCreate

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\CheckCreate`

| Field | Type | |
|---|---|---|
| `Destination` | `AccountId` | required |
| `SendMax` | `Amount` | required |
| `DestinationTag` | `UnsignedInt32` | optional |
| `Expiration` | `UnsignedInt32` | optional |
| `InvoiceID` | `Hash256` | optional |

## Clawback

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\Clawback`

| Field | Type | |
|---|---|---|
| `Amount` | `Amount` | required |
| `Holder` | `AccountId` | optional |

## CredentialAccept

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\CredentialAccept`

| Field | Type | |
|---|---|---|
| `Issuer` | `AccountId` | required |
| `CredentialType` | `Blob` | required |

## CredentialCreate

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\CredentialCreate`

| Field | Type | |
|---|---|---|
| `Subject` | `AccountId` | required |
| `CredentialType` | `Blob` | required |
| `Expiration` | `UnsignedInt32` | optional |
| `URI` | `Blob` | optional |

## CredentialDelete

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\CredentialDelete`

| Field | Type | |
|---|---|---|
| `Subject` | `AccountId` | optional |
| `Issuer` | `AccountId` | optional |
| `CredentialType` | `Blob` | required |

## DIDDelete

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\DIDDelete`

No fields beyond the common ones.

## DIDSet

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\DIDSet`

| Field | Type | |
|---|---|---|
| `Data` | `Blob` | optional |
| `DIDDocument` | `Blob` | optional |
| `URI` | `Blob` | optional |

## DelegateSet

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\DelegateSet`

| Field | Type | |
|---|---|---|
| `Authorize` | `AccountId` | required |
| `Permissions` | `StArray` | required |

## DepositPreauth

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\DepositPreauth`

| Field | Type | |
|---|---|---|
| `Authorize` | `AccountId` | optional |
| `Unauthorize` | `AccountId` | optional |
| `AuthorizeCredentials` | `StArray` | optional |
| `UnauthorizeCredentials` | `StArray` | optional |

## EscrowCancel

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\EscrowCancel`

| Field | Type | |
|---|---|---|
| `Owner` | `AccountId` | required |
| `OfferSequence` | `UnsignedInt32` | required |

## EscrowCreate

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\EscrowCreate`

| Field | Type | |
|---|---|---|
| `Amount` | `Amount` | required |
| `Destination` | `AccountId` | required |
| `CancelAfter` | `UnsignedInt32` | optional |
| `FinishAfter` | `UnsignedInt32` | optional |
| `Condition` | `Blob` | optional |
| `DestinationTag` | `UnsignedInt32` | optional |

## EscrowFinish

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\EscrowFinish`

| Field | Type | |
|---|---|---|
| `Owner` | `AccountId` | required |
| `OfferSequence` | `UnsignedInt32` | required |
| `Condition` | `Blob` | optional |
| `Fulfillment` | `Blob` | optional |
| `CredentialIDs` | `Vector256` | optional |

## MPTokenAuthorize

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\MPTokenAuthorize`

| Field | Type | |
|---|---|---|
| `MPTokenIssuanceID` | `Hash192` | required |
| `Holder` | `AccountId` | optional |

## MPTokenIssuanceCreate

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\MPTokenIssuanceCreate`

| Field | Type | |
|---|---|---|
| `AssetScale` | `UnsignedInt8` | optional |
| `TransferFee` | `UnsignedInt16` | optional |
| `MaximumAmount` | `UnsignedInt64` | optional |
| `MPTokenMetadata` | `Blob` | optional |
| `DomainID` | `Hash256` | optional |
| `ImmutableFlags` | `UnsignedInt32` | optional |

## MPTokenIssuanceDestroy

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\MPTokenIssuanceDestroy`

| Field | Type | |
|---|---|---|
| `MPTokenIssuanceID` | `Hash192` | required |

## MPTokenIssuanceSet

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\MPTokenIssuanceSet`

| Field | Type | |
|---|---|---|
| `MPTokenIssuanceID` | `Hash192` | required |
| `Holder` | `AccountId` | optional |
| `DomainID` | `Hash256` | optional |
| `MPTokenMetadata` | `Blob` | optional |
| `TransferFee` | `UnsignedInt16` | optional |
| `ImmutableFlags` | `UnsignedInt32` | optional |
| `IssuerEncryptionKey` | `Blob` | optional |
| `AuditorEncryptionKey` | `Blob` | optional |

## NFTokenAcceptOffer

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenAcceptOffer`

| Field | Type | |
|---|---|---|
| `NFTokenSellOffer` | `Hash256` | optional |
| `NFTokenBuyOffer` | `Hash256` | optional |
| `NFTokenBrokerFee` | `Amount` | optional |

## NFTokenBurn

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenBurn`

| Field | Type | |
|---|---|---|
| `NFTokenID` | `Hash256` | required |
| `Owner` | `AccountId` | optional |

## NFTokenCancelOffer

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenCancelOffer`

| Field | Type | |
|---|---|---|
| `NFTokenOffers` | `Vector256` | required |

## NFTokenCreateOffer

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenCreateOffer`

| Field | Type | |
|---|---|---|
| `Owner` | `AccountId` | optional |
| `NFTokenID` | `Hash256` | required |
| `Amount` | `Amount` | required |
| `Expiration` | `UnsignedInt32` | optional |
| `Destination` | `AccountId` | optional |

## NFTokenMint

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenMint`

| Field | Type | |
|---|---|---|
| `NFTokenTaxon` | `UnsignedInt32` | required |
| `Issuer` | `AccountId` | optional |
| `TransferFee` | `UnsignedInt16` | optional |
| `URI` | `Blob` | optional |
| `Amount` | `Amount` | optional |
| `Destination` | `AccountId` | optional |
| `Expiration` | `UnsignedInt32` | optional |

## NFTokenModify

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenModify`

| Field | Type | |
|---|---|---|
| `NFTokenID` | `Hash256` | required |
| `Owner` | `AccountId` | optional |
| `URI` | `Blob` | optional |

## OfferCancel

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\OfferCancel`

| Field | Type | |
|---|---|---|
| `OfferSequence` | `UnsignedInt32` | required |

## OfferCreate

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\OfferCreate`

| Field | Type | |
|---|---|---|
| `Expiration` | `UnsignedInt32` | optional |
| `OfferSequence` | `UnsignedInt32` | optional |
| `TakerGets` | `Amount` | required |
| `TakerPays` | `Amount` | required |
| `DomainID` | `Hash256` | optional |

## OracleDelete

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\OracleDelete`

| Field | Type | |
|---|---|---|
| `OracleDocumentID` | `UnsignedInt32` | required |

## OracleSet

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\OracleSet`

| Field | Type | |
|---|---|---|
| `OracleDocumentID` | `UnsignedInt32` | required |
| `LastUpdateTime` | `UnsignedInt32` | required |
| `PriceDataSeries` | `StArray` | required |
| `AssetClass` | `Blob` | optional |
| `Provider` | `Blob` | optional |
| `URI` | `Blob` | optional |

## Payment

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\Payment`

| Field | Type | |
|---|---|---|
| `Amount` | `Amount` | required |
| `Destination` | `AccountId` | required |
| `DestinationTag` | `UnsignedInt32` | optional |
| `InvoiceID` | `Hash256` | optional |
| `Paths` | `PathSet` | default |
| `SendMax` | `Amount` | optional |
| `DeliverMin` | `Amount` | optional |
| `CredentialIDs` | `Vector256` | optional |
| `DomainID` | `Hash256` | optional |

## PaymentChannelClaim

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\PaymentChannelClaim`

| Field | Type | |
|---|---|---|
| `Channel` | `Hash256` | required |
| `Balance` | `Amount` | optional |
| `Amount` | `Amount` | optional |
| `Signature` | `Blob` | optional |
| `PublicKey` | `Blob` | optional |
| `CredentialIDs` | `Vector256` | optional |

## PaymentChannelCreate

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\PaymentChannelCreate`

| Field | Type | |
|---|---|---|
| `Amount` | `Amount` | required |
| `Destination` | `AccountId` | required |
| `SettleDelay` | `UnsignedInt32` | required |
| `PublicKey` | `Blob` | required |
| `CancelAfter` | `UnsignedInt32` | optional |
| `DestinationTag` | `UnsignedInt32` | optional |

## PaymentChannelFund

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\PaymentChannelFund`

| Field | Type | |
|---|---|---|
| `Channel` | `Hash256` | required |
| `Amount` | `Amount` | required |
| `Expiration` | `UnsignedInt32` | optional |

## PermissionedDomainDelete

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\PermissionedDomainDelete`

| Field | Type | |
|---|---|---|
| `DomainID` | `Hash256` | required |

## PermissionedDomainSet

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\PermissionedDomainSet`

| Field | Type | |
|---|---|---|
| `DomainID` | `Hash256` | optional |
| `AcceptedCredentials` | `StArray` | required |

## SetRegularKey

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\SetRegularKey`

| Field | Type | |
|---|---|---|
| `RegularKey` | `AccountId` | optional |

## SignerListSet

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\SignerListSet`

| Field | Type | |
|---|---|---|
| `SignerQuorum` | `UnsignedInt32` | required |
| `SignerEntries` | `StArray` | optional |

## TicketCreate

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\TicketCreate`

| Field | Type | |
|---|---|---|
| `TicketCount` | `UnsignedInt32` | required |

## TrustSet

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\TrustSet`

| Field | Type | |
|---|---|---|
| `LimitAmount` | `Amount` | optional |
| `QualityIn` | `UnsignedInt32` | optional |
| `QualityOut` | `UnsignedInt32` | optional |

## XChainAccountCreateCommit

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainAccountCreateCommit`

| Field | Type | |
|---|---|---|
| `XChainBridge` | `XchainBridge` | required |
| `SignatureReward` | `Amount` | required |
| `Destination` | `AccountId` | required |
| `Amount` | `Amount` | required |

## XChainAddAccountCreateAttestation

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainAddAccountCreateAttestation`

| Field | Type | |
|---|---|---|
| `XChainBridge` | `XchainBridge` | required |
| `PublicKey` | `Blob` | required |
| `Signature` | `Blob` | required |
| `OtherChainSource` | `AccountId` | required |
| `Amount` | `Amount` | required |
| `AttestationRewardAccount` | `AccountId` | required |
| `AttestationSignerAccount` | `AccountId` | required |
| `WasLockingChainSend` | `UnsignedInt8` | required |
| `Destination` | `AccountId` | required |
| `XChainAccountCreateCount` | `UnsignedInt64` | required |
| `SignatureReward` | `Amount` | required |

## XChainAddClaimAttestation

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainAddClaimAttestation`

| Field | Type | |
|---|---|---|
| `XChainBridge` | `XchainBridge` | required |
| `PublicKey` | `Blob` | required |
| `Signature` | `Blob` | required |
| `OtherChainSource` | `AccountId` | required |
| `Amount` | `Amount` | required |
| `AttestationRewardAccount` | `AccountId` | required |
| `AttestationSignerAccount` | `AccountId` | required |
| `WasLockingChainSend` | `UnsignedInt8` | required |
| `XChainClaimID` | `UnsignedInt64` | required |
| `Destination` | `AccountId` | optional |

## XChainClaim

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainClaim`

| Field | Type | |
|---|---|---|
| `XChainBridge` | `XchainBridge` | required |
| `XChainClaimID` | `UnsignedInt64` | required |
| `Amount` | `Amount` | required |
| `Destination` | `AccountId` | required |
| `DestinationTag` | `UnsignedInt32` | optional |

## XChainCommit

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainCommit`

| Field | Type | |
|---|---|---|
| `XChainBridge` | `XchainBridge` | required |
| `XChainClaimID` | `UnsignedInt64` | required |
| `Amount` | `Amount` | required |
| `OtherChainDestination` | `AccountId` | optional |

## XChainCreateBridge

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainCreateBridge`

| Field | Type | |
|---|---|---|
| `XChainBridge` | `XchainBridge` | required |
| `SignatureReward` | `Amount` | required |
| `MinAccountCreateAmount` | `Amount` | optional |

## XChainCreateClaimID

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainCreateClaimID`

| Field | Type | |
|---|---|---|
| `XChainBridge` | `XchainBridge` | required |
| `SignatureReward` | `Amount` | required |
| `OtherChainSource` | `AccountId` | required |

## XChainModifyBridge

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainModifyBridge`

| Field | Type | |
|---|---|---|
| `XChainBridge` | `XchainBridge` | required |
| `SignatureReward` | `Amount` | optional |
| `MinAccountCreateAmount` | `Amount` | optional |

## Types without a model

The codec encodes and decodes these (the definitions know them), but no model class exists yet, either because the amendment is not active on Mainnet (Vault, Loan, Sponsorship, Confidential MPT) or because the type is a pseudo-transaction only validators send. Use a plain array if you need one.

`ConfidentialMPTClawback`, `ConfidentialMPTConvert`, `ConfidentialMPTConvertBack`, `ConfidentialMPTMergeInbox`, `ConfidentialMPTSend`, `EnableAmendment`, `LedgerStateFix`, `LoanBrokerCoverClawback`, `LoanBrokerCoverDeposit`, `LoanBrokerCoverWithdraw`, `LoanBrokerDelete`, `LoanBrokerSet`, `LoanDelete`, `LoanManage`, `LoanPay`, `LoanSet`, `SetFee`, `SponsorshipSet`, `SponsorshipTransfer`, `UNLModify`, `VaultClawback`, `VaultCreate`, `VaultDelete`, `VaultDeposit`, `VaultSet`, `VaultWithdraw`

## Xahau

The Xahau transaction types (`SetHook`, `Invoke`, `URIToken*`, `Remit`, ...) are in the `hardcastle/xahau_php` package, together with Xahau's own definitions; this library has none of them since 3.0.0.

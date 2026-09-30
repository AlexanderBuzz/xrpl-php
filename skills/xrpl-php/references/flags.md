# Flag constants

Generated from the flag classes. Values are those of rippled 3.4.0. Transaction flags are combined with `|` into the `Flags` field; `AccountSetAsfFlags` values go one at a time into `SetFlag` or `ClearFlag`; ledger entry flags are read from the `Flags` of an object the ledger returned. Every class has `has($flags, $flag)`, `parse($flags)` (names of the flags set) and `all()`.

```php
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\OfferCreateFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\AccountRootFlags;

'Flags' => OfferCreateFlags::tfSell | OfferCreateFlags::tfFillOrKill,
AccountRootFlags::has($accountData['Flags'], AccountRootFlags::lsfRequireDestTag);
AccountRootFlags::parse($accountData['Flags']); // ['lsfRequireDestTag', ...]
```

# Transaction flags

## AMMClawbackFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\AMMClawbackFlags`

| Constant | Value | |
|---|---|---|
| `tfClawTwoAssets` | `0x00000001` | Claw back both assets from the holder's LP tokens; the account has to issue both. |

## AMMDepositFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\AMMDepositFlags`

| Constant | Value | |
|---|---|---|
| `tfLPToken` | `0x00010000` | Deposit or withdraw both assets for exactly the LP token amount. |
| `tfSingleAsset` | `0x00080000` | Deposit or withdraw exactly Amount of one asset. |
| `tfTwoAsset` | `0x00100000` | Deposit or withdraw both assets, up to Amount and Amount2, at the pool's ratio. |
| `tfOneAssetLPToken` | `0x00200000` | Deposit or withdraw one asset for exactly the LP token amount. |
| `tfLimitLPToken` | `0x00400000` | Deposit or withdraw one asset at an effective price bounded by EPrice. |
| `tfTwoAssetIfEmpty` | `0x00800000` | Deposit both assets into an empty pool at the deposited ratio. |

## AMMWithdrawFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\AMMWithdrawFlags`

| Constant | Value | |
|---|---|---|
| `tfLPToken` | `0x00010000` | Deposit or withdraw both assets for exactly the LP token amount. |
| `tfWithdrawAll` | `0x00020000` | Withdraw everything the LP tokens are worth. |
| `tfOneAssetWithdrawAll` | `0x00040000` | Withdraw everything as a single asset. |
| `tfSingleAsset` | `0x00080000` | Deposit or withdraw exactly Amount of one asset. |
| `tfTwoAsset` | `0x00100000` | Deposit or withdraw both assets, up to Amount and Amount2, at the pool's ratio. |
| `tfOneAssetLPToken` | `0x00200000` | Deposit or withdraw one asset for exactly the LP token amount. |
| `tfLimitLPToken` | `0x00400000` | Deposit or withdraw one asset at an effective price bounded by EPrice. |

## AccountSetAsfFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\AccountSetAsfFlags`

| Constant | Value | |
|---|---|---|
| `asfRequireDest` | `0x00000001` | Require a DestinationTag on incoming payments. |
| `asfRequireAuth` | `0x00000002` | Require this account's authorization for others to hold its issued tokens. |
| `asfDisallowXRP` | `0x00000003` | XRP should not be sent to this account. Advisory, not enforced by the ledger. |
| `asfDisableMaster` | `0x00000004` | Disallow the master key pair. Needs a regular key or signer list first. |
| `asfAccountTxnID` | `0x00000005` | Track the ID of this account's most recent transaction in AccountTxnID. |
| `asfNoFreeze` | `0x00000006` | Permanently give up the ability to freeze trust lines or to global freeze. |
| `asfGlobalFreeze` | `0x00000007` | Freeze all tokens issued by this account. |
| `asfDefaultRipple` | `0x00000008` | Enable rippling on this account's trust lines by default. |
| `asfDepositAuth` | `0x00000009` | Require deposit authorization for incoming payments. |
| `asfAuthorizedNFTokenMinter` | `0x0000000A` | Let the account in NFTokenMinter mint NFTs on behalf of this one. |
| `asfDisallowIncomingNFTokenOffer` | `0x0000000C` | Block incoming NFToken offers. |
| `asfDisallowIncomingCheck` | `0x0000000D` | Block incoming Checks. |
| `asfDisallowIncomingPayChan` | `0x0000000E` | Block incoming payment channels. |
| `asfDisallowIncomingTrustline` | `0x0000000F` | Block incoming trust lines. |
| `asfAllowTrustLineClawback` | `0x00000010` | Allow clawback of tokens issued by this account. Cannot be cleared once set. |
| `asfAllowTrustLineLocking` | `0x00000011` | Allow tokens issued by this account to be held in escrow (TokenEscrow). |

## AccountSetFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\AccountSetFlags`

| Constant | Value | |
|---|---|---|
| `tfRequireDestTag` | `0x00010000` | Same as SetFlag asfRequireDest. |
| `tfOptionalDestTag` | `0x00020000` | Same as ClearFlag asfRequireDest. |
| `tfRequireAuth` | `0x00040000` | Same as SetFlag asfRequireAuth. |
| `tfOptionalAuth` | `0x00080000` | Same as ClearFlag asfRequireAuth. |
| `tfDisallowXRP` | `0x00100000` | Same as SetFlag asfDisallowXRP. |
| `tfAllowXRP` | `0x00200000` | Same as ClearFlag asfDisallowXRP. |

## BatchFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\BatchFlags`

| Constant | Value | |
|---|---|---|
| `tfAllOrNothing` | `0x00010000` | Apply all inner transactions, or none of them. |
| `tfOnlyOne` | `0x00020000` | Apply the first inner transaction that succeeds and stop. |
| `tfUntilFailure` | `0x00040000` | Apply the inner transactions in order until the first one fails. |
| `tfIndependent` | `0x00080000` | Apply every inner transaction, whatever the others do. |

## EnableAmendmentFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\EnableAmendmentFlags`

| Constant | Value | |
|---|---|---|
| `tfGotMajority` | `0x00010000` | The amendment reached majority support. |
| `tfLostMajority` | `0x00020000` | The amendment lost majority support. |

## GlobalFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\GlobalFlags`

| Constant | Value | |
|---|---|---|
| `tfInnerBatchTxn` | `0x40000000` | Marks an inner transaction of a Batch. Such a transaction carries no signature of its own. Batch is not active on Mainnet. |
| `tfFullyCanonicalSig` | `0x80000000` | Require a fully canonical signature. A no-op since the RequireFullyCanonicalSig amendment, kept for compatibility. |

## LoanManageFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\LoanManageFlags`

| Constant | Value | |
|---|---|---|
| `tfLoanDefault` | `0x00010000` | Mark the loan as defaulted. |
| `tfLoanImpair` | `0x00020000` | Mark the loan as impaired. |
| `tfLoanUnimpair` | `0x00040000` | Clear the impairment. |

## LoanPayFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\LoanPayFlags`

| Constant | Value | |
|---|---|---|
| `tfLoanOverpayment` | `0x00010000` | Allow, or make, a payment above the instalment due. |
| `tfLoanFullPayment` | `0x00020000` | Pay the loan off in full. |
| `tfLoanLatePayment` | `0x00040000` | Pay an overdue instalment. |

## LoanSetFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\LoanSetFlags`

| Constant | Value | |
|---|---|---|
| `tfLoanOverpayment` | `0x00010000` | Allow, or make, a payment above the instalment due. |

## MPTokenAuthorizeFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\MPTokenAuthorizeFlags`

| Constant | Value | |
|---|---|---|
| `tfMPTUnauthorize` | `0x00000001` | Holder: remove the MPToken. Issuer: revoke the holder's authorization. |

## MPTokenIssuanceCreateFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\MPTokenIssuanceCreateFlags`

| Constant | Value | |
|---|---|---|
| `tfMPTCanLock` | `0x00000002` | The issuer can lock balances. |
| `tfMPTRequireAuth` | `0x00000004` | Holders need the issuer's authorization. |
| `tfMPTCanEscrow` | `0x00000008` | Holders can place balances in escrow. |
| `tfMPTCanTrade` | `0x00000010` | Holders can trade balances on the DEX. |
| `tfMPTCanTransfer` | `0x00000020` | Holders can transfer balances to accounts other than the issuer. |
| `tfMPTCanClawback` | `0x00000040` | The issuer can claw back balances. |
| `tfMPTCanHoldConfidentialBalance` | `0x00000080` | Holders can convert balances into confidential ones. ConfidentialMPT is not active on Mainnet. |

## MPTokenIssuanceSetFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\MPTokenIssuanceSetFlags`

| Constant | Value | |
|---|---|---|
| `tfMPTLock` | `0x00000001` | Lock the issuance, or with Holder that holder's balance. |
| `tfMPTUnlock` | `0x00000002` | Unlock the issuance, or with Holder that holder's balance. |
| `tfMPTSetCanLock` | `0x00000004` | Set tfMPTCanLock after creation (DynamicMPT). |
| `tfMPTSetRequireAuth` | `0x00000008` | Set tfMPTRequireAuth after creation (DynamicMPT). |
| `tfMPTSetCanEscrow` | `0x00000010` | Set tfMPTCanEscrow after creation (DynamicMPT). |
| `tfMPTSetCanTrade` | `0x00000020` | Set tfMPTCanTrade after creation (DynamicMPT). |
| `tfMPTSetCanTransfer` | `0x00000040` | Set tfMPTCanTransfer after creation (DynamicMPT). |
| `tfMPTSetCanClawback` | `0x00000080` | Set tfMPTCanClawback after creation (DynamicMPT). |
| `tfMPTSetCanHoldConfidentialBalance` | `0x00000100` | Set tfMPTCanHoldConfidentialBalance after creation (DynamicMPT, ConfidentialMPT). |

## NFTokenCreateOfferFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\NFTokenCreateOfferFlags`

| Constant | Value | |
|---|---|---|
| `tfSellNFToken` | `0x00000001` | This is a sell offer; without it, a buy offer. |

## NFTokenMintFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\NFTokenMintFlags`

| Constant | Value | |
|---|---|---|
| `tfBurnable` | `0x00000001` | The issuer, or its authorized minter, can burn the token. |
| `tfOnlyXRP` | `0x00000002` | The token can only be bought or sold for XRP. |
| `tfTransferable` | `0x00000008` | The token can be transferred to others; otherwise only to and from the issuer. |
| `tfMutable` | `0x00000010` | The URI can be changed with NFTokenModify (DynamicNFT). |

## OfferCreateFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\OfferCreateFlags`

| Constant | Value | |
|---|---|---|
| `tfPassive` | `0x00010000` | Do not consume offers that exactly match this one; only cross offers of better quality. |
| `tfImmediateOrCancel` | `0x00020000` | Fill what can be filled immediately and cancel the rest; the offer is never placed in the ledger. |
| `tfFillOrKill` | `0x00040000` | Fill the whole offer immediately or cancel it. |
| `tfSell` | `0x00080000` | Exchange the entire TakerGets amount, even if that yields more than TakerPays. |
| `tfHybrid` | `0x00100000` | Place the offer in the open order book as well as in the permissioned book of DomainID (PermissionedDEX). |

## PaymentChannelClaimFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\PaymentChannelClaimFlags`

| Constant | Value | |
|---|---|---|
| `tfRenew` | `0x00010000` | Clear the channel's Expiration. |
| `tfClose` | `0x00020000` | Request to close the channel. |

## PaymentFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\PaymentFlags`

| Constant | Value | |
|---|---|---|
| `tfNoRippleDirect` | `0x00010000` | Do not use the default path; only use the paths in the Paths field. |
| `tfPartialPayment` | `0x00020000` | Deliver less than Amount if the full amount cannot be delivered, down to DeliverMin. |
| `tfLimitQuality` | `0x00040000` | Only use paths whose overall quality is at least Amount to SendMax. |
| `tfSponsorCreatedAccount` | `0x00080000` | The sender sponsors the reserve of the account this payment creates. Sponsorship is not active on Mainnet. |

## SponsorshipSetFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\SponsorshipSetFlags`

| Constant | Value | |
|---|---|---|
| `tfSponsorshipSetRequireSignForFee` | `0x00010000` |  |
| `tfSponsorshipClearRequireSignForFee` | `0x00020000` |  |
| `tfSponsorshipSetRequireSignForReserve` | `0x00040000` |  |
| `tfSponsorshipClearRequireSignForReserve` | `0x00080000` |  |
| `tfDeleteObject` | `0x00100000` | Delete the Sponsorship entry. |

## SponsorshipTransferFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\SponsorshipTransferFlags`

| Constant | Value | |
|---|---|---|
| `tfSponsorshipEnd` | `0x00010000` |  |
| `tfSponsorshipCreate` | `0x00020000` |  |
| `tfSponsorshipReassign` | `0x00040000` |  |

## TrustSetFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\TrustSetFlags`

| Constant | Value | |
|---|---|---|
| `tfSetfAuth` | `0x00010000` | Authorize the counterparty to hold tokens issued by this account. |
| `tfSetNoRipple` | `0x00020000` | Enable No Ripple on this trust line. |
| `tfClearNoRipple` | `0x00040000` | Disable No Ripple on this trust line. |
| `tfSetFreeze` | `0x00100000` | Freeze the trust line. |
| `tfClearFreeze` | `0x00200000` | Unfreeze the trust line. |
| `tfSetDeepFreeze` | `0x00400000` | Deep freeze the trust line: the counterparty can neither send nor receive the token. |
| `tfClearDeepFreeze` | `0x00800000` | Clear the deep freeze. |

## VaultCreateFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\VaultCreateFlags`

| Constant | Value | |
|---|---|---|
| `tfVaultPrivate` | `0x00010000` | Only accounts holding a credential accepted by the vault's domain can deposit. |
| `tfVaultShareNonTransferable` | `0x00020000` | Vault shares cannot be transferred. |

## XChainModifyBridgeFlags

`Hardcastle\XRPL_PHP\Models\Transaction\Flags\XChainModifyBridgeFlags`

| Constant | Value | |
|---|---|---|
| `tfClearAccountCreateAmount` | `0x00010000` | Clear the bridge's MinAccountCreateAmount. |

# Ledger entry flags

## AccountRootFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\AccountRootFlags`

| Constant | Value | |
|---|---|---|
| `lsfPasswordSpent` | `0x00010000` | The account has used its free SetRegularKey transaction. |
| `lsfRequireDestTag` | `0x00020000` | Set by AccountSet with asfRequireDestTag. |
| `lsfRequireAuth` | `0x00040000` | Set by AccountSet with asfRequireAuth. |
| `lsfDisallowXRP` | `0x00080000` | Set by AccountSet with asfDisallowXRP. |
| `lsfDisableMaster` | `0x00100000` | Set by AccountSet with asfDisableMaster. |
| `lsfNoFreeze` | `0x00200000` | Set by AccountSet with asfNoFreeze. |
| `lsfGlobalFreeze` | `0x00400000` | Set by AccountSet with asfGlobalFreeze. |
| `lsfDefaultRipple` | `0x00800000` | Set by AccountSet with asfDefaultRipple. |
| `lsfDepositAuth` | `0x01000000` | Set by AccountSet with asfDepositAuth. |
| `lsfDisallowIncomingNFTokenOffer` | `0x04000000` | Set by AccountSet with asfDisallowIncomingNFTokenOffer. |
| `lsfDisallowIncomingCheck` | `0x08000000` | Set by AccountSet with asfDisallowIncomingCheck. |
| `lsfDisallowIncomingPayChan` | `0x10000000` | Set by AccountSet with asfDisallowIncomingPayChan. |
| `lsfDisallowIncomingTrustline` | `0x20000000` | Set by AccountSet with asfDisallowIncomingTrustline. |
| `lsfAllowTrustLineLocking` | `0x40000000` | Set by AccountSet with asfAllowTrustLineLocking. |
| `lsfAllowTrustLineClawback` | `0x80000000` | Set by AccountSet with asfAllowTrustLineClawback. |

## CredentialFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\CredentialFlags`

| Constant | Value | |
|---|---|---|
| `lsfAccepted` | `0x00010000` | The subject accepted the credential. |

## DirectoryNodeFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\DirectoryNodeFlags`

| Constant | Value | |
|---|---|---|
| `lsfNFTokenBuyOffers` | `0x00000001` | The directory holds buy offers for an NFToken. |
| `lsfNFTokenSellOffers` | `0x00000002` | The directory holds sell offers for an NFToken. |

## LoanFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\LoanFlags`

| Constant | Value | |
|---|---|---|
| `lsfLoanDefault` | `0x00010000` | The loan is in default. |
| `lsfLoanImpaired` | `0x00020000` | The loan is impaired. |
| `lsfLoanOverpayment` | `0x00040000` | The loan allows overpayments. |

## MPTokenFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\MPTokenFlags`

| Constant | Value | |
|---|---|---|
| `lsfMPTLocked` | `0x00000001` | The balance, or the whole issuance, is locked. |
| `lsfMPTAuthorized` | `0x00000002` | The issuer authorized the holder. |
| `lsfMPTAMM` | `0x00000004` | The MPToken is held by an AMM. |

## MPTokenIssuanceFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\MPTokenIssuanceFlags`

| Constant | Value | |
|---|---|---|
| `lsfMPTLocked` | `0x00000001` | The balance, or the whole issuance, is locked. |
| `lsfMPTCanLock` | `0x00000002` | The issuer can lock balances. |
| `lsfMPTRequireAuth` | `0x00000004` | Holders need the issuer's authorization. |
| `lsfMPTCanEscrow` | `0x00000008` | Holders can place balances in escrow. |
| `lsfMPTCanTrade` | `0x00000010` | Holders can trade balances on the DEX. |
| `lsfMPTCanTransfer` | `0x00000020` | Holders can transfer balances to accounts other than the issuer. |
| `lsfMPTCanClawback` | `0x00000040` | The issuer can claw back balances. |
| `lsfMPTCanHoldConfidentialBalance` | `0x00000080` | Holders can convert balances into confidential ones. |

## NFTokenOfferFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\NFTokenOfferFlags`

| Constant | Value | |
|---|---|---|
| `lsfSellNFToken` | `0x00000001` | The offer is a sell offer. |

## OfferFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\OfferFlags`

| Constant | Value | |
|---|---|---|
| `lsfPassive` | `0x00010000` | The offer was placed with tfPassive. |
| `lsfSell` | `0x00020000` | The offer was placed with tfSell. |
| `lsfHybrid` | `0x00040000` | The offer sits in the open and in a permissioned order book. |

## RippleStateFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\RippleStateFlags`

| Constant | Value | |
|---|---|---|
| `lsfLowReserve` | `0x00010000` | The low account contributes to the owner reserve for this line. |
| `lsfHighReserve` | `0x00020000` | The high account contributes to the owner reserve for this line. |
| `lsfLowAuth` | `0x00040000` | The low account authorized the trust line. |
| `lsfHighAuth` | `0x00080000` | The high account authorized the trust line. |
| `lsfLowNoRipple` | `0x00100000` | The low account has No Ripple set on this line. |
| `lsfHighNoRipple` | `0x00200000` | The high account has No Ripple set on this line. |
| `lsfLowFreeze` | `0x00400000` | The low account froze the trust line. |
| `lsfHighFreeze` | `0x00800000` | The high account froze the trust line. |
| `lsfAMMNode` | `0x01000000` | The trust line belongs to an AMM. |
| `lsfLowDeepFreeze` | `0x02000000` | The low account deep froze the trust line. |
| `lsfHighDeepFreeze` | `0x04000000` | The high account deep froze the trust line. |

## SignerListFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\SignerListFlags`

| Constant | Value | |
|---|---|---|
| `lsfOneOwnerCount` | `0x00010000` | The signer list counts as one item toward the owner reserve. |

## SponsorshipFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\SponsorshipFlags`

| Constant | Value | |
|---|---|---|
| `lsfSponsorshipRequireSignForFee` | `0x00010000` |  |
| `lsfSponsorshipRequireSignForReserve` | `0x00020000` |  |

## VaultFlags

`Hardcastle\XRPL_PHP\Models\Ledger\Flags\VaultFlags`

| Constant | Value | |
|---|---|---|
| `lsfVaultPrivate` | `0x00010000` | Only accounts holding a credential accepted by the vault's domain can deposit. |

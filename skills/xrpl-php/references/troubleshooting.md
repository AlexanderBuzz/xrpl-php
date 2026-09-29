# Troubleshooting

## Reading a result

`submitAndWait()` returns a `TxResponse`; `getResult()['meta']['TransactionResult']` is the final result, `getResult()['validated']` is `true`, `getResult()['hash']` the transaction ID. `submit()` returns a `SubmitResponse` whose `getResult()['engine_result']` is preliminary and can still change.

| Prefix | Meaning | In the ledger? | Fee charged? |
|---|---|---|---|
| `tes` | success (`tesSUCCESS`) | yes | yes |
| `tec` | failed, claimed the fee | yes | yes |
| `ter` | retry, could succeed later (e.g. `terPRE_SEQ`, `terQUEUED`) | no | no |
| `tef` | failure, will not succeed as is (e.g. `tefPAST_SEQ`, `tefMAX_LEDGER`) | no | no |
| `tem` | malformed (e.g. `temBAD_AMOUNT`, `temDISABLED`, `temINVALID_FLAG`) | no | no |
| `tel` | local error of the node (e.g. `telINSUF_FEE_P`) | no | no |

Frequent codes and what to change:

- `tecUNFUNDED_PAYMENT`: the sender cannot cover amount plus reserve.
- `tecNO_DST_INSUF_XRP`: the destination does not exist and the payment is below the reserve.
- `tecDST_TAG_NEEDED`: destination requires a `DestinationTag`.
- `tecNO_LINE`, `tecPATH_DRY`: no trust line, or no path with enough liquidity, for an IOU payment.
- `tecNO_PERMISSION`: not allowed (e.g. clawback without `asfAllowTrustLineClawback`, or the wrong account).
- `tecINSUFFICIENT_RESERVE`, `tecINSUF_RESERVE_LINE`: the account needs more XRP for another object.
- `terNO_RIPPLE`: the issuer has not set `asfDefaultRipple`; needed before an AMM or third-party transfers work.
- `temDISABLED`: the amendment is not active on this network (Batch, Vault, Loan, Sponsorship, Confidential MPT on Mainnet).
- `temREDUNDANT`: the transaction changes nothing (e.g. a Payment to oneself).
- `tefPAST_SEQ`, `terPRE_SEQ`: `Sequence` is stale or ahead; autofill again from the current account state.
- `tefMAX_LEDGER`: `LastLedgerSequence` already passed when the node saw it; autofill and submit again.
- `telINSUF_FEE_P`: fee below what the node wants under load; raise `feeCushion` or `maxFeeXrp` on the client.

`Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions::getInstance()->mapValueToSpecificField('TransactionResult', $code)` turns a numeric code into its name.

## Exceptions

- `Exception` from `submitAndWait()`: "The latest ledger sequence N is greater than the transaction's LastLedgerSequence" means the transaction was never included; the message carries the preliminary result. A "Transaction must contain a LastLedgerSequence value" means the array was not autofilled.
- `ValidationException` from `Wallet::sign()`: the transaction already carries `TxnSignature` or `Signers`; sign the unsigned array.
- `Exception` from the codec, "Field X not found in definitions": a misspelled field, a field of another network, or a field of a newer rippled than the bundled definitions. "Unknown TransactionType: X": misspelled type (types are case sensitive, `AMMCreate` not `AmmCreate`).
- `ErrorResponse` from `syncRequest()` (not thrown): `getError()` is rippled's error, e.g. `actNotFound` (account does not exist on this network or ledger), `invalidParams`, `lgrNotFound`.
- `GuzzleHttp\Exception\RequestException` from `rawSyncRequest()`: HTTP-level failure; `$e->getResponse()` has the body. `examples/provoke-error.php` shows the handling.
- `XRPLFaucetException`: the faucet refused or timed out; Testnet faucets rate-limit, wait and retry.

## Testnet specifics

- The Testnet is reset from time to time; seeds from tutorials are then unfunded. `Wallet::generate()` plus `$client->fundWallet()` gives a fresh, funded wallet.
- Funding takes a few seconds; `fundWallet()` waits until the balance shows.
- Ledgers close every 3 to 5 seconds; `submitAndWait()` polls at that pace.
- Amendments differ per network. What works on Devnet may be `temDISABLED` on Mainnet; xrpl.org's "Known Amendments" page has the status.

## Encoding pitfalls

- Hex fields (`URI`, `Domain`, `MemoData`, `MemoType`, `MemoFormat`, `MPTokenMetadata`, `CredentialType`, `PublicKey`, `Signature`) take upper- or lowercase hex, never the raw string.
- `Amount` for XRP is a string of drops; an integer or float is rejected or misread.
- `TransferFee` units differ: 1/1000 of a percent on MPT issuances, 1/100,000 on NFTs; `TradingFee` on AMMs is 1/100,000.
- Time fields (`Expiration`, `FinishAfter`, `CancelAfter`) are seconds since the ripple epoch (2000-01-01), not Unix time.
- `Flags` values are 32-bit; ledger objects return them as an integer, decode with the matching `*Flags::parse()`.

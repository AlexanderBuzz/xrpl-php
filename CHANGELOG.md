# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html)..

## [Unreleased]

### Fixed
- The binary codec dropped the `ObjectEndMarker` of a nested object unless that
  object happened to be the last field of its parent. `StObject::fromJson()`
  wrote the marker once, after its loop, guarded by whichever field instance the
  loop had left behind. Fields are serialized in canonical order, so a nested
  object is last only as long as no field of a higher numbered type follows it -
  and where one does, the parser reads that field back as part of the nested
  object. Transaction metadata is the case on the XRP Ledger: every
  `ModifiedNode` carrying both `PreviousFields` and `FinalFields` encoded the
  second inside the first. `fromParser()` already wrote the marker per field,
  and `fromJson()` now does the same. Encodings that were correct before are
  unchanged, byte for byte.

## [2.3.0] - 2026-08-26

### Added
- `JsonRpcClient` exposes its collaborators through `getAutofiller()`,
  `getSubmitter()`, `getAccountReader()`, `getOrderbookReader()`,
  `getFeeCalculator()` and `getFaucet()`. A subclass overrides one of them to
  substitute its own, and the replacement reaches every path that uses it -
  including the indirect ones, where `Submitter` and `Autofiller` used to
  construct collaborators of their own.

## [2.2.0] - 2026-08-25

### Added
- Object form of the Sugar functions: `Autofiller`, `Submitter`, `AccountReader`,
  `OrderbookReader`, `FeeCalculator` and `Faucet` hold the client instead of taking
  it as a first argument. `JsonRpcClient` delegates to them and no longer imports a
  function from `Sugar`.
- `JsonRpcClient::autofill()` accepts `$signersCount`, which the multi-signing fee
  path needed but could not be reached from the client.
- `RpcMethodResponse`, a mock that serves rippled responses keyed by JSON-RPC method.
  The previous mock routed by URL path, which the client never varies, so nothing
  reaching rippled through the normal code path could be mocked.
- Tests: `FeeCalculationTest`, `SubmitTest`, `MathUtilitiesTest` and
  `SubmitAndWaitTest`. The last one runs against the Testnet, carries the group
  `integration-slow` and is excluded in CI; run it with
  `vendor/bin/phpunit --group integration-slow`.
- A Psalm baseline plus a configuration suited to a library, and Psalm in CI.

### Fixed
- `Sugar\getSignedTx()` returned the `tx_blob`/`hash` envelope of `Wallet::sign()`
  while every caller expects a transaction array, so `submit()` and `submitAndWait()`
  always failed with "Transaction must be signed" when given an unsigned transaction
  and a wallet - the reason both take a `$wallet` at all.
- The AccountDelete blocker check never ran, being guarded by
  `!isset($tx['TransactionType'])` rather than a comparison against AccountDelete,
  and would not have fired either, counting blockers as `$objects['length']` - a
  JavaScript idiom that is an undefined key in PHP.
- `DROPS_PER_XRP` was the float `1000000.0`, and `base_fee_xrp` arrives from rippled
  as a JSON number; both reached brick/math as floats.
- `MathUtilities` used `BigDecimal::getIntegralPart()` and `getFractionalPart()`,
  which brick/math 0.15 removes and 0.16 reintroduces with a different meaning.
  `exactlyDividedBy()` is renamed to `dividedByExact()`. The test suite runs without
  deprecations again.
- `AccountOffersResponse` imported `BaseRequest` while extending `BaseResponse`, so
  the class could not be autoloaded at all.
- `docker-compose.linux.yml` mounted `php.ini` twice and `xdebug.ini` not at all.

### Changed
- `JsonRpcClient::autofill()` no longer takes the transaction by reference. It was
  never written through, and the reference only forced callers to assign the array
  to a variable first. Existing calls keep working.
- The Sugar functions are marked `@deprecated` and delegate to the classes above.
  They emit no runtime warning yet; that follows once the object form has settled.
- `containers/php/` becomes `docker/`, and the three compose files become one.
  The platform differences move into `.env` (`DOCKER_UID`, `DOCKER_GID`) and an
  `extra_hosts` entry, so the same `xdebug.ini` works on Linux and macOS.
  `xdebug-mac.ini` and `xdebug-linux.ini` are gone; no compose file mounted them.
- `examples/custom_currency_codes.php` and `examples/xrpBalance.php` are renamed to
  kebab-case, matching every other example.
- Documentation: every class now has a description, and the share of documented
  public methods rises from 18 to 47 per cent. The contract the 29 serialized types
  share is described once on `SerializedType` rather than repeated per subclass.

### Removed
- The dead stubs `Sugar\formatBalances()`, `Sugar\getUpdatedBalance()` and
  `Sugar\getHttpOptions()`. Two of them raised a `TypeError` when called; none was
  referenced.

## [2.1.0] - 2026-08-24

### Added
- The binary codec works against definitions handed in from outside, so a package
  for another network can supply its own `definitions.json`. `Definitions::fromArray()`
  and `Definitions::fromFile()` build such a set, and `BinaryCodec`, `BinaryParser`,
  `Wallet` and `JsonRpcClient` all take an optional `Definitions` instance. Omitting
  it keeps the bundled XRP Ledger definitions, so existing code is unaffected.
  The definitions travel through the whole encode and decode, including nested
  objects and arrays, and never touch the shared default instance - one process can
  talk to both networks at once.
- `HashLedger::hashSignedTx()`, `Sugar\getLastLedgerSequence()` and
  `Sugar\isAccountDelete()` take an optional `Definitions` instance as well, and
  the call sites pass the ones of the client or wallet in hand.
- README section "Using your own definitions".

### Fixed
- `Wallet::sign()` encoded against the wallet's definitions but hashed the result
  through the default ones. Hashing decodes the blob to verify a signature is
  present, and decoding resolves every field, so signing a transaction carrying a
  field only the injected definitions know threw inside `sign()`.
- `HashLedger::hashSignedTx()` produces the hashed blob itself when handed an
  array. With the wrong definitions that yields a different blob and therefore a
  wrong hash, with nothing to indicate it.

## [2.0.0] - 2026-08-24

Brings the library to parity with rippled 3.3.0 / ripple-binary-codec 5.0.0. The
binary codec is now verified against the official `codec-fixtures.json` of
xrpl.js (34 transactions and 261 ledger objects, all encoding to the exact
reference binary and decoding back to the reference JSON).

### Fixed
- **XChain transactions could not be serialized at all.** `SerializedType::getTypeByName()`
  registered the bridge type as `XchainBridge`, while `definitions.json` spells it
  `XChainBridge`, so all eight XChain transaction types failed with
  `unsupported type XChainBridge`.
- **AMM transactions were silently serialized as Payments.** The model classes were
  named `AmmBid`, `AmmCreate`, … instead of `AMMBid`, `AMMCreate`, …, and an unknown
  transaction type name was cast to the ordinal `0`. They are renamed, and an
  unknown `TransactionType`, `LedgerEntryType`, `TransactionResult` or
  `PermissionValue` now raises an exception instead of falling back to `0`.
- **Xahau definitions shadowed XRPL definitions.** `hooksDefinitions.json` was merged
  with `array_merge`, so Xahau entries overwrote mainline entries that share an
  ordinal. Decoding produced `URITokenCreateSellOffer` for `XChainCreateBridge`,
  `ObjectID` for `AMMID`, `OfferID` for `DomainID`, `EscrowID` for `VaultID`,
  `Blob` for `DIDDocument` and `HookNamespaces` for `CredentialIDs`. Xahau entries
  are now only added where the XRP Ledger has no entry of that name.
  Encoding Xahau transactions is byte for byte unaffected. Decoding stays ambiguous
  for the ordinals both networks use: `URITokenMint`, `URITokenBurn`, `URITokenBuy`,
  `URITokenCreateSellOffer` and `URITokenCancelSellOffer` now decode to their XRPL
  counterparts (`XChainAddClaimAttestation`, `XChainAddAccountCreateAttestation`,
  `XChainModifyBridge`, `XChainCreateBridge`, `DIDSet`), and the Xahau `Blob` field
  decodes as `DIDDocument`. Only a network aware `Definitions` instance can resolve
  this; the previous behaviour resolved it the other way around and broke the XRPL.
- `Vector256::toJson()` returned a JSON encoded string instead of an array, so every
  ledger object with `Indexes`, `Hashes` or `Amendments` decoded incorrectly.
- `UnsignedInt64::fromJson()` parsed its input as base 10, which made hex values such
  as the `AssetPrice` of an `OracleSet` throw a `NumberFormatException`.
- Token amounts were rendered with a trailing `.0` (`"10000.0"` instead of `"10000"`).
- `NFTokenCreateOffer` declared `NFTokenId`, which does not exist in `definitions.json`;
  the field is `NFTokenID`. Creating an NFT offer was impossible either way.
- `PaymentChannelCreate` declared `ChancelAfter` instead of `CancelAfter`.
- `CheckChancel` is renamed to `CheckCancel`, which makes the class autoloadable and
  the transaction type usable.
- `AMMDelete` declared `Amount`/`Amount2` instead of `Asset`/`Asset2`, `AMMWithdraw`
  declared `LPTokenOut` instead of `LPTokenIn`, and `AMMDeposit` was missing `TradingFee`.
- `"Path"` was mapped to `Issue::class` in the type map.
- The Devnet JSON-RPC endpoint in `Networks` used the `wss://` scheme.
- `autofill()` did not know that `AMMCreate` pays one owner reserve as its
  transaction cost instead of the network fee, so every AMMCreate was rejected with
  `telINSUF_FEE_P`. The owner reserve is now used for `AccountDelete` and
  `AMMCreate` alike, and `maxFeeXrp` no longer caps it.
- The `EscrowFinish` fee calculation computed the fulfillment size as
  `strlen($tx['Fulfillment'] / 2)` instead of `strlen($tx['Fulfillment']) / 2`. The
  hex string was cast to a number, so the size was always 1 and the fee too low.

### Added
- **MPTokensV1** (live on Mainnet): `MPTokenIssuanceCreate`, `MPTokenIssuanceDestroy`,
  `MPTokenIssuanceSet`, `MPTokenAuthorize`. `Amount` and `Issue` now serialize MPT
  values (`{"mpt_issuance_id": …, "value": …}`).
- **Credentials** (live on Mainnet): `CredentialCreate`, `CredentialAccept`,
  `CredentialDelete`, plus `CredentialIDs` on `Payment`, `AccountDelete`,
  `EscrowFinish` and `PaymentChannelClaim`, and `AuthorizeCredentials` /
  `UnauthorizeCredentials` on `DepositPreauth`.
- **PermissionedDomains** (live on Mainnet): `PermissionedDomainSet`,
  `PermissionedDomainDelete`.
- **PermissionedDEX** (live on Mainnet): `DomainID` on `OfferCreate` and `Payment`.
- **AMMClawback** and **NFTokenModify** (both live on Mainnet).
- **Batch** and **DelegateSet** (amendments not yet enabled on Mainnet). `DelegateSet`
  accepts granular permission names such as `AccountDomainSet` as well as transaction
  level permissions.
- New serialized types `Hash192` (for `MPTokenIssuanceID` and `ShareMPTID`) and
  `Number` (STNumber, the 12 byte mantissa/exponent type used by the Vault and
  Lending Protocol objects).
- Missing common fields `NetworkID` and `Delegate` on `BaseTransaction`.
- Missing fields on existing models: `Clawback.Holder`, `NFTokenMint.Amount`/
  `Destination`/`Expiration`, `XChainClaim.DestinationTag`,
  `XChainAddAccountCreateAttestation.XChainAccountCreateCount`/`SignatureReward`.
- Tests: `CodecFixturesTest` (conformance against the xrpl.js reference fixtures),
  `TransactionRoundtripTest` (encode/decode and signing for every affected type),
  `LedgerObjectFixturesTest` (the new ledger object types, captured from Testnet
  because the reference fixtures predate the amendments), `DefinitionsMergeTest`
  (pins that Xahau definitions never shadow mainline ones), `NumberTest`,
  `Hash192Test` and MPT amount cases in `AmountTest`.
- Examples `examples/mptoken.php`, `examples/permissioned-domain.php`,
  `examples/amm-clawback.php` and `examples/nftoken-modify.php`. All four were run
  against Testnet; every transaction returned `tesSUCCESS`.

### Security
- The `guzzlehttp/guzzle` constraint `^7.4` allowed every release from 7.4.0 up to
  7.15.1, all of which carry open advisories - among them CVE-2026-69246 (high,
  noncanonical hosts bypassing host-based checks), fixed in 7.15.2. Consumers
  resolving the dependency tree could therefore end up on a vulnerable version.
  The constraint is raised to `^7.15.2`, the first release fixing all of them.
  `composer audit` is clean against the resulting tree.

### Changed
- `composer.json` declares `"php": "^8.2"`. It only had `config.platform`, which is
  a development setting and does not constrain installs. 8.2 is what CI tests.
- `brick/math` is narrowed from `>=0.11 <0.18` to `>=0.11 <0.15`. The library uses
  `BigDecimal::getIntegralPart()` and `getFractionalPart()`, which 0.15 removes and
  0.16 reintroduces with different semantics. Resolution is unchanged in practice,
  because `hardcastle/buffer` already caps brick/math at `^0.14` - the old upper
  bound claimed a compatibility that does not exist.
- **Breaking:** the JSON representation of a `UInt64` is now a 16 character hex
  string, as rippled and the reference SDKs produce it, instead of a decimal string.
  Fields `MaximumAmount`, `OutstandingAmount`, `MPTAmount` and `LockedAmount` stay
  base 10. `UnsignedInt64::fromJson()` expects hex; use `UnsignedInt64::fromBase10()`
  for decimal input.
- **Breaking:** the AMM model classes are renamed from `Amm*` to `AMM*`. The old
  names never produced a valid AMM transaction.
- `Sugar\fetchAccountDeleteFee()` is deprecated in favour of
  `Sugar\fetchOwnerReserveFee()`; the fee is not specific to AccountDelete. The old
  function delegates to the new one and keeps working.
- `definitions.json` is synchronized with ripple-binary-codec 5.0.0: added the fields
  `ReferenceHolding`, `TakerGetsMPT` and `TakerPaysMPT`, the results `temBAD_MPT` and
  `terLOCKED`, and renamed the types `UInt384`/`UInt512` to `Hash384`/`Hash512`.

### Notes
- `SingleAssetVault` and `LendingProtocol` are not enabled on Mainnet, so the
  `Vault*` and `Loan*` transaction models are deliberately not implemented yet. The
  codec supports their field types (`Number`, `Hash192`, MPT `Issue`), which is why
  the reference `VaultCreate` fixture passes.
- The two fee fixes above are not covered by a unit test: the fee path needs mocked
  `fee`, `server_state`, `account_info` and `ledger` responses, and the existing mock
  server routes by HTTP path rather than by RPC method. Both were verified against
  Testnet instead - the validated AMMCreate carries `Fee: 200000`, the owner reserve.
- The `ledgerData` group of the reference fixtures covers ledger headers, which this
  library does not model; it is excluded from the conformance test.

## [1.1.0] - 2026-03-09
### Added
- **Xahau/Hooks Support**:
  - Expanded `hooksDefinitions.json` with missing Xahau fields: `NetworkID`, `GovernanceFlags`, `Amount2`, `RewardAccumulator`, `RewardLgrFirst`, `RewardLgrLast`, `ImportSequence`, `ActiveAccounts`.
  - New transaction models: `GenesisMint`, `Import`, `ClaimReward`, `UNLReport`.

## [1.0.0] - 2026-03-09
### Added
- **Xahau/Hooks Support**:
    - Dedicated directory for Xahau-specific models: `src/Hooks/Models/Transaction/TransactionTypes/`.
    - New transaction models: `TicketCancel`, `URITokenMint`, `URITokenBurn`, `URITokenBuy`, `URITokenCreateSellOffer`, `URITokenCancelSellOffer`, `SetHook`, `Invoke`.
    - Automatic merging of Xahau-specific definitions from `hooksDefinitions.json`.
- **New Core XRPL Transaction Types**:
    - XChain (Cross-Chain Bridges): `XChainCreateBridge`, `XChainCommit`, `XChainClaim`, `XChainAccountCreateCommit`, `XChainAddAccountCreateAttestation`, `XChainAddClaimAttestation`, `XChainCreateClaimID`, `XChainModifyBridge`.
    - DID (Decentralized Identity): `DIDSet`, `DIDDelete`.
    - Oracles: `OracleSet`, `OracleDelete`.
- **New Serialized Types**:
    - Signed Integers: `Int32` and `Int64` (using 2's complement).
    - Large Bit-Width Unsigned Integers: `UInt96`, `UInt192`, `UInt384`, `UInt512`.
- **Enhanced Sugar Functions**:
    - `getBalances`: Now fetches both XRP and IOU balances with pagination support.
    - `getTransactions`: Fetches account history using `account_tx`.
    - `getOrderbook`: Helper for fetching formatted bids and asks.
    - `getFeeXrp`: Helper to calculate current network fees with cushion.
- **Testing**: Added `TransactionTypesTest.php` and `SignedIntTest.php` for comprehensive verification of new types.

### Changed
- **Modernization**:
    - Upgraded minimum PHP version requirement to `^8.2`.
    - Updated `Dockerfile` to use `php:8.2.27-fpm-alpine`.
    - Updated GitHub Actions `unit_test.yml` to run on PHP 8.2.
    - Performed project-wide refactoring using Rector (Constructor Property Promotion, Readonly properties).
- **Binary Codec**:
    - Replaced fragile MD5-based field lookup with a stable `$typeCode:$fieldCode` mapping.
    - Added strict input validation to `BinaryCodec::decode` to prevent misinterpretation of hex-encoded JSON.
- **Project Structure**:
    - Moved Xahau/Hooks specific logic to dedicated `src/Hooks` namespace.
    - Updated `composer.json` to include new sugar function files in the autoloader.

### Fixed
- **Deprecation Warnings**: Explicitly marked nullable parameters in `JsonRpcClient` to satisfy PHP 8.4+ requirements.
- **Type Correction**: Fixed `NFTokenMinter` field in `AccountSet` transaction to use `AccountId` instead of `Blob`.
- **Error Handling**: Improved error messages when field headers

## [0.10.0] - 2025-08-11
### Added
- USDC added to stablecoin support

### Changed
- RLUSD stablecoin method signature changed (was: `Stablecoin::getRLUSDAAmount`, now: `Stablecoin::RLUSD::getAmount`)

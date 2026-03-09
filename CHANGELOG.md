# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html)..

## [Unreleased]
- No released changes yet.

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

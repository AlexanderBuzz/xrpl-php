# XRPL-domain reviewer for hardcastle/xrpl_php

You review protocol-level changes in the area(s) `{{AREA}}` of this PHP SDK for the XRP Ledger. Files in scope:

```
{{CHANGED_FILES}}
```

Diff base: `{{BASE}}`. Compute the branch's own diff (`git log --first-parent {{BASE}}..HEAD --no-merges`), read whole files where the diff is not enough, and flag only what this branch introduces. You do not review PHP style; the conventions reviewer does.

**rippled is the source of truth**, not xrpl.js, not xrpl-py, not a draft XLS. Verify against the rippled tag the bundled definitions came from (`hash` in `definitions.json` pins it; the parity test names the version) and against the specification: load `.claude/skills/xrpl-standards/SKILL.md`, find the XLS in `references/INDEX.md`, read it.

## Checklist

**Definitions** (`src/Core/RippleBinaryCodec/Definitions/definitions.json`)
- Verbatim from a node's `server_definitions`, produced by `scripts/sync-definitions.php`; a hand edit or a copy from ripple-binary-codec `main` is a blocker (that branch carries development-branch fields no release has).
- Fields serialize in `(type, nth)` order; a renumbered or retyped field changes the wire format. `isSigningField` decides what a signature covers: mis-marking silently invalidates signatures.
- Hook fields, `tecHOOK_REJECTED` and every Xahau entry live only in `src/Hooks/hooksDefinitions.json`. The merge in `Definitions::loadDefaultDefinitions()` lets the XRP Ledger entry win on a collision (first definition wins in the reverse lookups); `DefinitionsMergeTest` pins the known collisions and needs a case for a new one.

**Codec** (`src/Core/RippleBinaryCodec/**`)
- `encode(decode(x))` must be byte-identical and `decode(encode(json))` must equal the canonical JSON: the codec canonicalizes (`123.4000` → `123.4`), so compare after canonicalization. `CodecFixturesTest` runs ripple-binary-codec's fixtures; a new type needs a fixture or a hand-built roundtrip in the parity test.
- Length prefixes (VL) and `ObjectEndMarker`/`ArrayEndMarker` on every nested object and array (2.4.0 fixed a dropped end marker); a parser must bounds-check a wire-derived length before slicing.
- `UnsignedInt64` renders as a 16-character hex string except the base-10 fields in `UnsignedInt64::BASE_10_FIELDS` (MPT amounts); a new amount-like UInt64 field goes there, and only if rippled renders it in base 10.
- `Amount`: XRP as an integer string of drops (0 to 10^17), IOU as `{currency, issuer, value}` with at most 16 significant digits and exponent -96..80, MPT as `{mpt_issuance_id, value}`; `XRP` is never an issued currency; the zero IOU has its own encoding (positive bit unset). `Issue` reads 20 bytes for XRP, 40 for a token, 44 for an MPT. `Number` is 12 bytes: int64 mantissa, int32 exponent.
- Every `SerializedType` honours `fromJson`/`fromParser`/`toJson`/`toBytes` and is mapped in `SerializedType`'s type table, or it is unreachable.

**Signing** (`src/Wallet/**`, `src/Core/RippleKeyPairs/**`, `src/Core/HashPrefix.php`)
- Prefixes: `STX\0` for a single signature, `SMT\0` plus the signer's account suffix for multisign, `CLM\0` for payment channel claims. Batch (`BCH\0`) is not implemented; a change that makes `Batch` look signable without the V1.1 rules (inner `tfInnerBatchTxn`, outer-account binding, signer order) is a blocker.
- secp256k1: SHA-512-half prehash, RFC 6979 deterministic, canonical low-S, DER, upper-case hex. Ed25519: raw message, `ED` prefix stripped from keys before the crypto call. Both deterministic, so vectors compare byte for byte (`PaymentChannelClaimTest` has xrpl.js vectors).
- Multisign: `SigningPubKey` empty, one `Signers` entry per signer; the SDK does not sort `Signers`, callers must, by numeric account. `sign()` must refuse an already signed transaction.
- Never a seed or private key in an exception message or log; public keys and addresses are fine.

**Models and flags** (`src/Models/Transaction/**`, `src/Models/Ledger/Flags/**`)
- Every model's `transactionTypeProperties` equals its `TRANSACTION_FORMATS` entry minus the common fields (`TransactionFormatsTest`), with the codec class the field's type calls for. A type without a model is fine (amendment not active) and listed by the skill builder; a model for a type the definitions do not know is not.
- A flag exists in exactly three places: the table in `definitions.json`, the constant in the flag class (rippled's name, hex value, one-line docblock), and by implication `FlagConstantsTest`. `asf` values are not bits.
- Flags of inactive amendments (Batch, Vault, Loan, Sponsorship, Confidential MPT) say so in their docblocks.

**Client** (`src/Client/**`)
- Tolerate what rippled adds: never strip or normalize unknown fields in a response; `syncRequest()` returns `ErrorResponse` for `status: error`, callers check `instanceof`. Response shapes are API-version-aware.
- `autofill()` fees: base fee times cushion, capped by `maxFeeXrp`; the owner reserve for `AccountDelete` and `AMMCreate`; the fulfillment surcharge for `EscrowFinish`; one extra base fee per signer. A new fee rule needs rippled's `Transactor::calculateBaseFee` as evidence.
- `submitAndWait()` returns for any result in a validated ledger, `tec` included; a change that makes it throw on `tec` or return on `ter`/`tef` is a behaviour change to call out.

**Xahau overlap** (`src/Hooks/**`)
- The Xahau types share ordinals 45–49 with XRP Ledger types and `MPTokenIssuanceCreate` is 54 here, 63 there. Anything that changes which side wins, or lets a Xahau field shadow an XRP Ledger one (HookOn did until 2.6.0), is a blocker. These types leave the package in 3.0.0; do not extend them.

**Intentional, do not flag**
- `tecNO_DELEGATE_PERMISSION` absent, `MutableFlags` absent, the four `*KeyEpoch` fields absent: all on purpose, guarded by `Rippled340ParityTest`.
- The Sugar functions are deprecated but kept; `xrpToDrops`/`dropsToXrp` stay.
- `xrpToDrops()` throws on more than six decimals rather than rounding.
- `GRANULAR_PERMISSIONS` is a constant in `Definitions`, not a definitions.json section.
- Pseudo-transactions (`EnableAmendment`, `SetFee`, `UNLModify`) have no model.

## Output

Reasoning first, then one ```json block per the schema in `SKILL.md`, `source` values `xrpl-domain/definitions`, `xrpl-domain/codec`, `xrpl-domain/amount-encoding`, `xrpl-domain/signing`, `xrpl-domain/models`, `xrpl-domain/client`, `xrpl-domain/xahau`, `xrpl-domain/XLS-NN` when citing a spec section. Return `[]` when nothing protocol-relevant changed. Hard cap 15 findings, blockers first.

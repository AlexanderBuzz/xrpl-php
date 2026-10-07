---
name: xrpl-php-sync-definitions
description: Refresh the bundled definitions.json from a rippled node's server_definitions, then bring the generated references, the parity test and the changelog in line. Use when rippled releases a new version or the user asks to sync, update or check the definitions.
disable-model-invocation: true
---

# Sync definitions from a node

The bundled `src/Core/RippleBinaryCodec/Definitions/definitions.json` is a verbatim snapshot of what a rippled node reports through `server_definitions`, digest included. It is never taken from ripple-binary-codec's `main` branch: that branch tracks the rippled development branch and carries fields no release has (it did in September 2026, and five of nine such fields then shipped in 3.4.0 while four did not). The node is the only source that cannot run ahead.

Everything below writes files and reports. It never commits, tags or pushes; the maintainer does that.

## 1. Fetch and compare

```bash
php scripts/sync-definitions.php                 # Mainnet, s1.ripple.com
php scripts/sync-definitions.php --node <url>     # another node, e.g. Devnet for a not yet released version
php scripts/sync-definitions.php --check          # report only, exit 1 if behind
```

The script prints the node's build version, the old and new digest, and per section what was added or removed. If it says "unchanged", stop and report that. Confirm the node runs the version the user wants to target: a Mainnet node reports the current release; Devnet may run a release candidate.

## 2. Verify every new entry against the rippled tag

The node is authoritative for its own version, but read the summary line by line and cross-check new names against the protocol macros of the matching rippled tag, so that the changelog can say what amendment they belong to:

```
https://raw.githubusercontent.com/XRPLF/rippled/<tag>/include/xrpl/protocol/detail/sfields.macro
https://raw.githubusercontent.com/XRPLF/rippled/<tag>/include/xrpl/protocol/detail/transactions.macro
https://raw.githubusercontent.com/XRPLF/rippled/<tag>/include/xrpl/protocol/detail/ledger_entries.macro
https://raw.githubusercontent.com/XRPLF/rippled/<tag>/include/xrpl/protocol/TER.h
https://raw.githubusercontent.com/XRPLF/rippled/<tag>/include/xrpl/protocol/TxFlags.h
https://raw.githubusercontent.com/XRPLF/rippled/<tag>/include/xrpl/protocol/LedgerFormats.h
```

For the specification behind a new transaction or ledger entry, load the `xrpl-standards` skill (`.agents/skills/xrpl-standards/SKILL.md`) and read the XLS file its `references/INDEX.md` names.

Hook fields (`Hook*`, `EmitGeneration`, `EmittedTxn`) and `tecHOOK_REJECTED` belong to Xahau and live only in `src/Hooks/hooksDefinitions.json`; if the node ever reports one, that is a Xahau node, not the XRP Ledger.

## 3. Regenerate and test

```bash
php skills/build.php
vendor/bin/phpunit --exclude-group integration
vendor/bin/psalm --config=psalm.xml --no-cache
```

Expected fallout and what to do with it:

- `Rippled<version>ParityTest` fails on the digest and the counts. Rename the class and file to the new rippled version if it changed, set the digest and counts, add cases for the new types, fields and result codes (one per family), and keep the guards for entries that must stay out (development-branch fields, Hook fields, `MutableFlags`, `tecNO_DELEGATE_PERMISSION`).
- `TransactionFormatsTest` fails for a model whose format gained a field: add the field to the model with the codec class its type calls for.
- `FlagConstantsTest` fails when a flag table gained an entry: add the constant to the flag class with a one-line docblock, and a class for a new type. `DefinitionsMergeTest` fails when a new XRP Ledger ordinal collides with a Xahau one: add the case; the XRP Ledger name wins.
- `SkillReferencesTest` fails until `php skills/build.php` has run.

Also add the base-10 UInt64 fields, if the release renders a new amount field in base 10 (see `UnsignedInt64::BASE_10_FIELDS`), and check `codec-fixtures.json` against the ripple-binary-codec release that matches the rippled version.

## 4. Record

Add a changelog entry under `## [Unreleased]` in the house style: what the new version is, what was added or removed per section, what changed for a caller, and which amendments the new types belong to and whether they are active on Mainnet. Update the `rippled X.Y.Z` mentions in `README.md`, `docs/flags.md` and the flag class docblocks (`Values are those of rippled X.Y.Z`).

## 5. Report

List: node and version, digest before and after, sections changed, models and classes touched, tests renamed, and anything left for the maintainer to decide (an ordinal that moved, a renamed field, a collision). Do not commit.

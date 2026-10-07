---
name: xrpl-php-review
description: Review the current branch's changes to hardcastle/xrpl_php against the XRPL protocol checklist (definitions, codec, signing, models, flags, Xahau overlap) and the repository's conventions. Use as /xrpl-php-review before opening a pull request, after fetching a branch to audit it, or when the user asks for a protocol-level review. Not a replacement for the built-in /code-review; it is the rule-based audit for this library.
disable-model-invocation: true
---

# XRPL review

A rule-based audit of what this branch changes, keyed to how this library breaks: a field with the wrong ordinal, a signing payload with the wrong prefix, an amount that lost precision, a model out of step with the definitions, another network's definitions leaking into or shadowing the XRP Ledger's. Findings are reported, never auto-fixed.

## 1. Scope

The branch's own work: its first-parent, non-merge commits since `main`. Not `git diff main...HEAD`, which would include commits merged in from other branches.

```bash
git log --first-parent main..HEAD --no-merges --reverse --format=%H          # the commits
git log --first-parent main..HEAD --no-merges --name-only --format= | sort -u  # the files
for sha in $(git log --first-parent main..HEAD --no-merges --reverse --format=%H); do git show --format= "$sha"; done
```

Ask (`AskUserQuestion`) only when it is ambiguous: a dirty tree (committed only, or with uncommitted and untracked changes), the current branch being `main` (last commit, last N, or cancel). Flags or a file list from the user resolve it without asking.

## 2. Classify

| Area | Paths |
|---|---|
| `codec` | `src/Core/RippleBinaryCodec/**`, `src/Core/HashPrefix.php`, `src/Core/MathUtilities.php`, `src/Core/RippleAddressCodec/**` |
| `signing` | `src/Wallet/**`, `src/Core/RippleKeyPairs/**`, `src/Utils/Hashes/**` |
| `models` | `src/Models/Transaction/**`, `src/Models/Ledger/Flags/**`, `src/Models/Common/**` |
| `client` | `src/Client/**`, `src/Sugar/**`, `src/Models/**` (request/response classes) |
| `networks` | `src/Core/RippleBinaryCodec/Definitions/Definitions.php`, `tests/Core/RippleBinaryCodec/Definitions/InjectableDefinitionsTest.php` (another network's definitions, injected; Xahau's come from `hardcastle/xahau_php`) |
| `skill` | `skills/**`, `.agents/**`, `.claude/**`, `.claude-plugin/**` |
| `tests`, `docs`, `other` | the rest |

## 3. Run the guards first

They are cheap, authoritative, and catch most cross-file drift before any reading:

```bash
vendor/bin/phpunit --exclude-group integration --filter 'Rippled340ParityTest|TransactionFormatsTest|FlagConstantsTest|InjectableDefinitionsTest|SkillReferencesTest|SyncDefinitionsTest|CodecFixturesTest|TransactionRoundtripTest'
vendor/bin/psalm --config=psalm.xml --no-cache
```

A failure here is a `blocker` finding with the test's message; do not reason about what the test already proved.

## 4. Single pass or fan-out

- **Single pass** when the diff is under 200 changed lines and touches one area: apply `prompts/xrpl-domain.md` (if the area is `codec`, `signing`, `models` or `networks`) and `prompts/php-conventions.md` yourself.
- **Fan-out** otherwise: one `general-purpose` subagent per touched area with `prompts/php-conventions.md`, plus **one** XRPL-domain subagent with `prompts/xrpl-domain.md` over the union of `codec`, `signing`, `models` and `networks` files, if any. Launch them in a single message. Substitute `{{AREA}}`, `{{CHANGED_FILES}}`, `{{BASE}}` in the prompts.

Each reviewer returns prose and then one ```json block with the findings (schema below). Take the last block; on a parse failure ask once for JSON only; if that fails too, keep the text as a single `concern` from `<reviewer>/unparseable`.

## 5. Cross-cutting

After the reviewers, on the merged findings and the diff:

1. **Dedup** by `(file, line, message)`.
2. **Changelog.** Any change under `src/` needs an entry under `## [Unreleased]` in `CHANGELOG.md` in this diff; otherwise `concern` / `cross-cutting/changelog`.
3. **Public API.** A removed or re-typed public method, constant or class under `src/` is breaking: `concern` / `cross-cutting/breaking` unless the changelog calls it out and the version plan allows it (3.0.0 is reserved for the Xahau split).
4. **Definitions provenance.** Any change to `definitions.json` not produced by `scripts/sync-definitions.php` (digest present, `--check` clean against the node) is a `blocker` / `cross-cutting/definitions-source`.
5. **Skill freshness.** If `src/` changed models, flags or request classes, `skills/build.php` output must be in the diff (the guard test says so).

## 6. Render

Severity filter if the user asked for one; per file at most 5 nits per reviewer, at most 20 findings overall with blockers and concerns never dropped; grouped by file, ordered blocker → concern → nit, then line. Print the report and stop. No fixes, no commits.

## Findings schema

```json
[
  {
    "file": "src/Core/RippleBinaryCodec/Types/Amount.php",
    "line": 132,
    "severity": "blocker | concern | nit",
    "source": "xrpl-domain/amount-encoding | xrpl-domain/signing | xrpl-domain/definitions | xrpl-domain/models | xrpl-domain/networks | php/conventions | php/tests | cross-cutting/<check>",
    "message": "one sentence naming the defect and its effect",
    "evidence": "the line or fact that shows it; a spec section or test name where one applies"
  }
]
```

Severity: `blocker` breaks signing, encoding, validation or interoperability, or fails a guard test; `concern` is a likely defect or a missing changelog/API note; `nit` is naming or docblock drift, used sparingly.

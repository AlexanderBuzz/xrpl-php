---
name: xrpl-php-release
description: Prepare a release of hardcastle/xrpl_php - changelog heading, skill version, regenerated references, checks, release branch, pull request and the release notes - without merging or tagging. Use as /xrpl-php-release <version>, e.g. /release 2.7.0.
disable-model-invocation: true
---

# Prepare a release

`/xrpl-php-release <version>` turns the `[Unreleased]` block into the release and gets everything that carries the version in line. It commits on a release branch, pushes it and opens the pull request for review. **It never merges, tags or pushes to `main`**; the maintainer merges the PR and creates the GitHub release with the notes this skill writes.

The argument is the version, `MAJOR.MINOR.PATCH`. Without one, stop and ask.

## 1. Preconditions

```bash
git status --short            # must be empty
git branch --show-current     # must be main
git fetch origin && git status -sb | head -1   # must not be behind origin/main
grep -n '^## \[' CHANGELOG.md | head -3
```

- `CHANGELOG.md` must start its entries with `## [Unreleased]` and that block must not be empty. If there is nothing to release, say so and stop.
- The version must be greater than the latest heading and follow SemVer for what the block contains: any `Breaking` section or `feat!` commit since the last tag (`git log <last>..main --format=%s | grep '!:'`) calls for a major unless the changelog entry itself argues why it is a minor (2.6.0 did, for a fix nobody could have relied on). Say what you found; the maintainer decides.
- `3.0.0` is reserved for removing the Xahau types once `hardcastle/xahau_php` is published. Do not propose it for anything else.

## 2. Branch and changelog

```bash
git checkout -b release-<version> main
```

In `CHANGELOG.md`, rename `## [Unreleased]` to `## [<version>] - <YYYY-MM-DD>` (today). Keep every entry's wording. Order the subsections `Added`, `Changed`, `Fixed`, `Removed`, and within them put the entries in reading order: what a consumer meets first, first. Do not add a lead paragraph; the house style keeps those for the release notes.

## 3. Everything that carries the version

- `skills/xrpl-php/SKILL.md` frontmatter: `sdk_version` = the version, `last_updated` = today. Bump `metadata.version` (patch for content edits, minor for new sections or references) only if the skill changed since the last release (`git log <last>..main -- skills/`); `.claude-plugin/plugin.json` and `.claude-plugin/marketplace.json` carry the same version as the skill, and a test checks that.
- `php skills/build.php` regenerates the references; the test suite fails if they are stale.
- `README.md` line "XRP Ledger / rippled version X.Y.Z compatible" and the flag docblocks say the rippled version, not the SDK version; touch them only when a definitions sync changed it.

## 4. Verify

```bash
vendor/bin/phpunit --exclude-group integration
vendor/bin/psalm --config=psalm.xml --no-cache
grep -c Unreleased CHANGELOG.md        # must print 0
```

Psalm always with `--no-cache`; the cache has hidden errors before.

## 5. Commit, push, pull request

Commit message: subject `task: release <version>`, body in the house style of the last release commits (`git log --format=%B -1 <last tag>`): why this is a minor or major, and the one thing a consumer has to know when upgrading, if there is one. No tooling attribution lines.

```bash
git push -u origin release-<version>
gh pr create --base main --head release-<version> --title "task: release <version>" --body-file <body>
```

PR body, as in #54 and #57: a Summary saying the block was renamed and how entries were ordered, the SemVer reasoning, "What consumers get" as bullets, an "Upgrading" line, and the sentence "After the merge: tag `<version>` on the merge commit (I do not tag). Packagist picks it up from the webhook." Test plan: changelog only, the grep, the suite result of the last content PR.

## 6. Release notes

Write `release-notes-<version>.md` in the repository root (gitignored) in the style of the 2.4.0 to 2.6.0 releases (`gh release view <last> --json body -q .body` shows the last one):

- one lead sentence naming the release's substance;
- `## Added` / `## Changed` / `## Fixed` with a bold lead-in per item and the consequence for the consumer, drawn from the changelog but written for a reader who has not seen it;
- `## Upgrading`: what to do, usually `composer update`, and what to check, as a before/after diff block where a rename is involved;
- `**Full Changelog**: https://github.com/AlexanderBuzz/xrpl-php/compare/<last>...<version>`.

Then print the command the maintainer runs after the merge:

```
gh release create <version> --target main --title <version> --notes-file release-notes-<version>.md
```

## 7. Report

PR number and link, the version reasoning, what carries the version and was updated, the test results, the release-notes path and the release command. Say explicitly that merging and tagging are left to the maintainer.

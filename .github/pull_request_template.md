## Summary

<!-- What changes, and why. For a fix: what was wrong, since when, what a caller saw. For a feature: why this shape. -->

## Changelog

- [ ] `CHANGELOG.md` has an entry under `## [Unreleased]`
- [ ] No consumer-visible change, no entry needed

## Checks

- [ ] `vendor/bin/phpunit ./tests --exclude-group integration --exclude-group integration-slow`
- [ ] `vendor/bin/psalm --config=psalm.xml --no-cache`
- [ ] Public API unchanged, or the break is named in the changelog and allowed by the version plan

## Test plan

<!-- How you verified it, and how a reviewer can. Name the Testnet run if there was one. -->

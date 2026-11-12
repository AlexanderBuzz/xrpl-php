# Security policy

## Reporting a vulnerability

Please do not report a security problem in a public issue, pull request or discussion. Report it
privately, in one of two ways:

- **GitHub:** open the [Security tab](https://github.com/AlexanderBuzz/xrpl-php/security) of this
  repository and choose "Report a vulnerability".
- **E-mail:** write to [info@ledger-direct.com](mailto:info@ledger-direct.com).

Please include the version of `hardcastle/xrpl_php`, what an attacker can do, and the steps to
reproduce it.

## What to expect

We confirm receipt within 5 business days and keep you informed while we investigate and fix the
problem. Please give us the time to publish a fix before you disclose it. There is no bug bounty.

## Supported versions

| Version | Supported |
|---|---|
| 3.x | yes |
| older than 3.0 | no |

Security fixes go into the latest 3.x release. Please check that the problem still occurs there.

## Scope

In scope: this SDK as published on Packagist (`hardcastle/xrpl_php`), including the agent skill
under `skills/`.

Out of scope: the XRP Ledger and rippled themselves, third-party wallets, and the public test
networks and faucets. A vulnerability in a dependency belongs to its maintainers; tell us as well if
this package is affected by it. Vulnerabilities in the PHP runtime are covered in the README's
Security section.

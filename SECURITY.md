# Security policy

Security matters to us: xrpl-php signs transactions on the XRP Ledger. No matter how careful we are,
vulnerabilities can still slip through, and we are grateful for every report that helps us find
them.

## Supported versions

| Version | Supported |
|---|---|
| 3.x | yes |
| older than 3.0 | no |

Security fixes go into the latest 3.x release. Please check that the problem still occurs there.

## Reporting a vulnerability

Please do not report a security problem in a public issue, pull request or discussion. Report it
privately, in one of two ways:

- **GitHub:** open the [Security tab](https://github.com/AlexanderBuzz/xrpl-php/security) of this
  repository and choose "Report a vulnerability".
- **E-mail:** write to [info@ledger-direct.com](mailto:info@ledger-direct.com).

Please include the version of `hardcastle/xrpl_php`, what an attacker can do, and the steps to
reproduce it.

## Responsible disclosure

We confirm receipt within 5 business days, investigate the report privately and keep you informed
while we fix it. Please do not disclose the vulnerability until a fix is published and users have
had time to upgrade. Test only against your own installations and on test networks, never against
other people's systems or with funds on a main network, and refrain from anything that puts our
users, the project or its maintainers at risk.

## Scope

In scope: this SDK as published on Packagist (`hardcastle/xrpl_php`), including the agent skill
under `skills/`.

Out of scope: the XRP Ledger and rippled themselves, third-party wallets, and the public test
networks and faucets. A vulnerability in a dependency belongs to its maintainers; tell us as well if
xrpl-php is affected by it. Vulnerabilities in the PHP runtime are covered in the README's
Security section.

## Thank you

We appreciate every responsible report and credit you in the security advisory, unless you prefer
not to be named.

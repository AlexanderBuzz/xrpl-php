# Migrating to 3.0.0

3.0.0 removes one thing: Xahau. Nothing else that was public in 2.7.0 changed,
so for an application that talks to the XRP Ledger the migration is
`composer update`.

## If you used the Xahau types from this library

Until 2.7.0 this library shipped twelve Xahau transaction models under
`Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes` and merged a
Xahau definitions file into its own, resolving the ordinals the two networks
reuse in favour of the XRP Ledger. That worked for the classic types both
networks share and for encoding the Xahau-only ones, and silently did not for
everything the XRP Ledger added after Xahau forked: `MPTokenIssuanceCreate` is
54 on the XRP Ledger and 63 on Xahau, and a Xahau `URITokenMint` decoded as
`XChainAddClaimAttestation`.

Xahau now has its own package, built on this one:

```console
composer require hardcastle/xahau_php
```

| 2.x | 3.0.0 with `hardcastle/xahau_php` |
|---|---|
| `new JsonRpcClient('xahau_testnet')` | `Xahau::client(Xahau::TESTNET)` or `new XahauClient('xahau_testnet')` |
| `Wallet::fromSeed($seed)` for a Xahau account | `Xahau::wallet($seed)` |
| `new BinaryCodec()` for Xahau bytes | `Xahau::codec()` |
| `Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\SetHook` | `Hardcastle\Xahau_PHP\Models\Transaction\TransactionTypes\SetHook` (same for `Invoke`, `Import`, `ClaimReward`, `GenesisMint`, `UNLReport`, `URIToken*`) |
| `TicketCancel` from `Hooks` | Xahau has no such transaction; the class is gone |
| `Definitions::getInstance()` knowing `HookOn`, `URITokenMint`, `tecHOOK_REJECTED` | `Xahau::definitions()`; the default instance is the XRP Ledger's alone |
| `DefaultFaucets` for `hooks-testnet-v2` | `XahauFaucet`, through `$client->getFaucet()` |

What the package adds beyond the move: `Remit`, `SetRemarks`, `Cron` and
`CronSet`, which had no model anywhere; `NetworkID` filled in by autofill; the
fee asked of the node, because a Xahau transaction's cost depends on the hooks
it sets off; and the testnet faucet, whose host this library could not infer.

## If you injected your own definitions

Nothing changes. `Definitions::fromFile()`, `fromArray()` and the
`Definitions` parameter on `BinaryCodec`, `Wallet` and `JsonRpcClient` are the
seam the Xahau package uses, and they stay. The only behaviour that moved is
the default: `Definitions::getInstance()` is the bundled XRP Ledger file and
nothing else, where it used to carry the Xahau entries on top.

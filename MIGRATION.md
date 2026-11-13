# Migrating to 4.0.0

## If you checked syncRequest() for a status-500 ErrorResponse

`JsonRpcClient::syncRequest()` throws a `GuzzleHttp\Exception\GuzzleException`
whenever no complete response arrives (connection failure or drop, timeout, a
body cut off). Until 3.x some of these came back as an `ErrorResponse` with
status 500 and the error "RequestException - could not get response", others
were thrown already. An HTTP error status from the node is unchanged: an
`ErrorResponse` with that status.

| 3.x | 4.0.0 |
|---|---|
| `if ($r instanceof ErrorResponse && $r->getStatusCode() === 500) { retry(); }` | `try { $r = $client->syncRequest($req); } catch (GuzzleException) { retry(); }` |

Guzzle 8 is allowed next to Guzzle 7 (`^7.15.2 \|\| ^8.0`); Composer picks it
unless your project holds Guzzle 7 or its psr7 2 / promises 2.

---

# Migrating to 3.0.0

3.0.0 removes one thing: Xahau. Nothing else that was public in 2.7.0 changed,
so for an application that talks to the XRP Ledger the migration is the
version constraint: a `^2.x` constraint does not admit a major, so

```console
composer require hardcastle/xrpl_php:^3.0
```

and nothing in the code.

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
composer require hardcastle/xahau-php
```

| 2.x | 3.0.0 with `hardcastle/xahau-php` |
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
seam the Xahau package uses, and they stay, as does the environment variable
`XRPL_PHP_DEFINITIONS_FILE_PATH`, which points the default instance at another
file. The only behaviour that moved is the default: `Definitions::getInstance()`
is the bundled XRP Ledger file (or the file the variable names) and nothing
else, where it used to carry the Xahau entries on top.

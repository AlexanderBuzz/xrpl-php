# Request classes

Generated from the request classes. Each one maps to a rippled API method; its constructor parameters are the method's params in camelCase (sent in snake_case). Pass an instance to `JsonRpcClient::syncRequest()` for a response object, or to `request()` for a promise; anything not covered goes through `rawSyncRequest()`.

```php
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;

$response = $client->syncRequest(new AccountInfoRequest(account: $address, ledgerIndex: 'validated'));
$accountData = $response->getResult()['account_data'];
```

`syncRequest()` returns the matching `*Response` (with `getResult()`, `getStatus()`, `getWarnings()`) or an `ErrorResponse` (with `getError()`, `getStatusCode()`); check with `instanceof` before reading the result.

# Account

## AccountChannelsRequest (`account_channels`)

`Hardcastle\XRPL_PHP\Models\Account\AccountChannelsRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$destinationAccount` | `?string` | `NULL` |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |

## AccountCurrenciesRequest (`account_currencies`)

`Hardcastle\XRPL_PHP\Models\Account\AccountCurrenciesRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$strict` | `?bool` | `NULL` |

## AccountInfoRequest (`account_info`)

`Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$queue` | `?bool` | `NULL` |
| `$signer_lists` | `?bool` | `NULL` |
| `$strict` | `?bool` | `NULL` |

## AccountLinesRequest (`account_lines`)

`Hardcastle\XRPL_PHP\Models\Account\AccountLinesRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$peer` | `?string` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |

## AccountNftsRequest (`account_nfts`)

`Hardcastle\XRPL_PHP\Models\Account\AccountNftsRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |

## AccountObjectsRequest (`account_objects`)

`Hardcastle\XRPL_PHP\Models\Account\AccountObjectsRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$type` | `?string` | `NULL` |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$deletionBlockersOnly` | `?bool` | `NULL` |
| `$marker` | `mixed` | `NULL` |

## AccountOffersRequest (`account_offers`)

`Hardcastle\XRPL_PHP\Models\Account\AccountOffersRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |
| `$strict` | `?bool` | `NULL` |

## AccountTxRequest (`account_tx`)

`Hardcastle\XRPL_PHP\Models\Account\AccountTxRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$ledgerIndexMin` | `?int` | `NULL` |
| `$ledgerIndexMax` | `?int` | `NULL` |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$binary` | `?bool` | `NULL` |
| `$forward` | `?bool` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |
| `$strict` | `?bool` | `NULL` |

## GatewayBalancesRequest (`gateway_balances`)

`Hardcastle\XRPL_PHP\Models\Account\GatewayBalancesRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$strict` | `?bool` | `NULL` |
| `$hotwallet` | `mixed` | `NULL` |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |

## NorippleCheckRequest (`noripple_check`)

`Hardcastle\XRPL_PHP\Models\Account\NorippleCheckRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `string` | required |
| `$role` | `string` | required |
| `$transactions` | `?bool` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |

# Clio

## LedgerRequest (`ledger`)

`Hardcastle\XRPL_PHP\Models\Clio\LedgerRequest`

| Parameter | Type | Default |
|---|---|---|
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$transactions` | `?bool` | `false` |
| `$expand` | `?bool` | `false` |
| `$ownerFunds` | `?bool` | `false` |
| `$binary` | `?bool` | `false` |
| `$queue` | `?bool` | `false` |
| `$diff` | `?bool` | `false` |

## NftHistoryRequest (`nft_history`)

`Hardcastle\XRPL_PHP\Models\Clio\NftHistoryRequest`

| Parameter | Type | Default |
|---|---|---|
| `$nftId` | `string` | required |
| `$ledgerIndexMin` | `?int` | `NULL` |
| `$ledgerIndexMax` | `?int` | `NULL` |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$binary` | `?bool` | `false` |
| `$forward` | `?bool` | `false` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |

## NftInfoRequest (`nft_info`)

`Hardcastle\XRPL_PHP\Models\Clio\NftInfoRequest`

| Parameter | Type | Default |
|---|---|---|
| `$nftId` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |

## ServerInfoRequest (`server_info`)

`Hardcastle\XRPL_PHP\Models\Clio\ServerInfoRequest`

No parameters.

# Ledger

## LedgerClosedRequest (`ledger_closed`)

`Hardcastle\XRPL_PHP\Models\Ledger\LedgerClosedRequest`

| Parameter | Type | Default |
|---|---|---|
| `$id` | `string|int` | required |

## LedgerCurrentRequest (`ledger_current`)

`Hardcastle\XRPL_PHP\Models\Ledger\LedgerCurrentRequest`

| Parameter | Type | Default |
|---|---|---|
| `$id` | `string|int` | required |

## LedgerDataRequest (`ledger_data`)

`Hardcastle\XRPL_PHP\Models\Ledger\LedgerDataRequest`

| Parameter | Type | Default |
|---|---|---|
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$binary` | `?bool` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |

## LedgerEntryRequest (`ledger_entry`)

`Hardcastle\XRPL_PHP\Models\Ledger\LedgerEntryRequest`

| Parameter | Type | Default |
|---|---|---|
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$binary` | `?bool` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |

## LedgerRequest (`ledger`)

`Hardcastle\XRPL_PHP\Models\Ledger\LedgerRequest`

| Parameter | Type | Default |
|---|---|---|
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$full` | `?bool` | `false` |
| `$accounts` | `?bool` | `false` |
| `$transactions` | `?bool` | `false` |
| `$expand` | `?bool` | `false` |
| `$ownerFunds` | `?bool` | `false` |
| `$binary` | `?bool` | `false` |
| `$queue` | `?bool` | `false` |

# PathOrderbook

## AmmInfoRequest (`amm_info`)

`Hardcastle\XRPL_PHP\Models\PathOrderbook\AmmInfoRequest`

| Parameter | Type | Default |
|---|---|---|
| `$account` | `?string` | required |
| `$amm_account` | `?string` | required |
| `$asset` | `array|string|null` | `NULL` |
| `$asset2` | `array|string|null` | `NULL` |

## BookOffersRequest (`book_offers`)

`Hardcastle\XRPL_PHP\Models\PathOrderbook\BookOffersRequest`

| Parameter | Type | Default |
|---|---|---|
| `$takerGets` | `array` | required |
| `$takerPays` | `array` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$number` | `?int` | `NULL` |
| `$taker` | `?string` | `NULL` |

## DepositAuthorizedRequest (`deposit_authorized`)

`Hardcastle\XRPL_PHP\Models\PathOrderbook\DepositAuthorizedRequest`

| Parameter | Type | Default |
|---|---|---|
| `$sourceAccount` | `string` | required |
| `$destinationAccount` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |

## NftBuyOffersRequest (`nft_buy_offers`)

`Hardcastle\XRPL_PHP\Models\PathOrderbook\NftBuyOffersRequest`

| Parameter | Type | Default |
|---|---|---|
| `$sourceAccount` | `string` | required |
| `$nftId` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |

## NftSellOffersRequest (`nft_sell_offers`)

`Hardcastle\XRPL_PHP\Models\PathOrderbook\NftSellOffersRequest`

| Parameter | Type | Default |
|---|---|---|
| `$nftId` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |
| `$limit` | `?int` | `NULL` |
| `$marker` | `mixed` | `NULL` |

## RipplePathFindRequest (`ripple_path_find`)

`Hardcastle\XRPL_PHP\Models\PathOrderbook\RipplePathFindRequest`

| Parameter | Type | Default |
|---|---|---|
| `$sourceAccount` | `string` | required |
| `$destinationAccount` | `string` | required |
| `$destinationAmount` | `array|string` | required |
| `$sendMax` | `array|string|null` | `NULL` |
| `$sourceCurrencies` | `?array` | `NULL` |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |

# PaymentChannel

## ChannelAuthorizeRequest (`channel_authorize`)

`Hardcastle\XRPL_PHP\Models\PaymentChannel\ChannelAuthorizeRequest`

| Parameter | Type | Default |
|---|---|---|
| `$channelId` | `string` | required |
| `$amount` | `string` | required |
| `$seed` | `?string` | required |
| `$seedHex` | `?string` | required |
| `$passphrase` | `?string` | required |
| `$keyType` | `?string` | required |

## ChannelVerifyRequest (`channel_verify`)

`Hardcastle\XRPL_PHP\Models\PaymentChannel\ChannelVerifyRequest`

| Parameter | Type | Default |
|---|---|---|
| `$channelId` | `string` | required |
| `$amount` | `string` | required |
| `$publicKey` | `string` | required |
| `$signature` | `string` | required |

# ServerInfo

## FeeRequest (`fee`)

`Hardcastle\XRPL_PHP\Models\ServerInfo\FeeRequest`

No parameters.

## ManifestRequest (`manifest`)

`Hardcastle\XRPL_PHP\Models\ServerInfo\ManifestRequest`

| Parameter | Type | Default |
|---|---|---|
| `$publicKey` | `string` | required |

## ServerInfoRequest (`server_info`)

`Hardcastle\XRPL_PHP\Models\ServerInfo\ServerInfoRequest`

No parameters.

## ServerStateRequest (`server_state`)

`Hardcastle\XRPL_PHP\Models\ServerInfo\ServerStateRequest`

No parameters.

# Transaction

## SubmitMultisignedRequest (`submit_multisigned`)

`Hardcastle\XRPL_PHP\Models\Transaction\SubmitMultisignedRequest`

| Parameter | Type | Default |
|---|---|---|
| `$txJson` | `string` | required |
| `$failHard` | `bool` | `false` |

## SubmitRequest (`submit`)

`Hardcastle\XRPL_PHP\Models\Transaction\SubmitRequest`

| Parameter | Type | Default |
|---|---|---|
| `$txBlob` | `string` | required |
| `$failHard` | `bool` | `false` |

## TransactionEntryRequest (`transaction_entry`)

`Hardcastle\XRPL_PHP\Models\Transaction\TransactionEntryRequest`

| Parameter | Type | Default |
|---|---|---|
| `$txHash` | `string` | required |
| `$ledgerHash` | `?string` | `NULL` |
| `$ledgerIndex` | `?string` | `NULL` |

## TxHistoryRequest (`tx_history`)

`Hardcastle\XRPL_PHP\Models\Transaction\TxHistoryRequest`

| Parameter | Type | Default |
|---|---|---|
| `$start` | `int` | required |

## TxRequest (`tx`)

`Hardcastle\XRPL_PHP\Models\Transaction\TxRequest`

| Parameter | Type | Default |
|---|---|---|
| `$transaction` | `string` | required |
| `$binary` | `?bool` | `NULL` |

# Utility

## JsonRequest (`json`)

`Hardcastle\XRPL_PHP\Models\Utility\JsonRequest`

| Parameter | Type | Default |
|---|---|---|
| `$serializedJson` | `string` | required |

## PingRequest (`ping`)

`Hardcastle\XRPL_PHP\Models\Utility\PingRequest`

No parameters.

## RandomRequest (`random`)

`Hardcastle\XRPL_PHP\Models\Utility\RandomRequest`

No parameters.

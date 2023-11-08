---
layout: documentation
title: JSON RPC Client
current_menu: client
---

# JSON RPC Client

### Creating a Client

To instantiate a client, you have to create a new JsonRpcClient object and provide the URL of the [Network](networks.md) 
you want to use as a constructor parameter:

```php
use use XRPL_PHP\Client\JsonRpcClient;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");
```

### Method requests and raw requests

XRPL_PHP offers most of the standardized [Public API Methods](https://xrpl.org/public-api-methods.html) 
and [Admin API Methods](https://xrpl.org/admin-api-methods.html). In most use cases you create a request method and
pass it to the clients `syncReuqest()` or `asyncRequest()` methods.

```php
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Utility\PingRequest;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$pingRequest = new PingRequest();

$pingResponse = $client->syncRequest($pingRequest);

$result = $pingResponse->getResult();

print_r($result);
```

You can also use the `rawRequest()` method if you need more control over the request, e.g. using a currently not implemented method:

```php
use XRPL_PHP\Client\JsonRpcClient;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$body = json_encode([
    "method" => "server_info",
    "params" => [
        ["api_version" => 1]
    ]
]);

$response = $client->rawSyncRequest('POST', '', $body);

$content = $response->getBody()->getContents();

print_r($content);
```

### Synchronous and Asynchronous requests

XRPL_PHP supports both synchronous and asynchronous request flows. 
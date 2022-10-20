<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

/*
 * example for using JSON-RPC via local rippled instance via docker:
 * docker run -d -p 5005:5005 -it natenichols/rippled-standalone:latest
 *
 * $client = new JsonRpcClient("http://host.docker.internal:5005");
 */

$body = json_encode([
    "method" => "server_info",
    "params" => [
        ["api_version" => 1]
    ]
]);

$response = $client->rawSyncRequest('GET', '', $body);

$content = $response->getBody()->getContents();

print_r($content);

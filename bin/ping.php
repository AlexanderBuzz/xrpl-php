<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;

$client = new JsonRpcClient("http://s.altnet.rippletest.net:51233");

$body = json_encode([
    "method" => "server_info",
    "params" => [
        ["api_version" => 1]
    ]
]);

$response = $client->request('GET', '', $body);

$content = $response->getBody()->getContents();

print_r($content);


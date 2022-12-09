<?php

require __DIR__ . '/../../vendor/autoload.php';

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountObjectsRequest;
use XRPL_PHP\Test\MockRippled\MockRippledResponse;

const PORT = 50267;
$server = new MockWebServer(PORT);
$server->start();

$body1 = json_encode([
    "method" => "server_info",
    "params" => [
        ["api_version" => 1]
    ]
]);

$body2 = json_encode([
    "method" => "server_info",
    "params" => [
        ["api_version" => 2]
    ]
]);
$rippledRes1 = new MockRippledResponse($body1);
$rippledRes2 = new MockRippledResponse($body2);

print_r(PHP_EOL . "RippledRes1:" . PHP_EOL);
print_r($rippledRes1->getRef() . PHP_EOL);
print_r(PHP_EOL . "RippledRes2:" . PHP_EOL);
print_r($rippledRes2->getRef() . PHP_EOL);

$url = $server->setResponseOfPath(
    $rippledRes1->getRef(),
    $rippledRes1
);
echo $server->getUrlOfResponse($rippledRes1) . PHP_EOL . PHP_EOL;

$url = $server->setResponseOfPath(
    $rippledRes2->getRef(),
    $rippledRes2
);
echo $server->getUrlOfResponse($rippledRes2) . PHP_EOL . PHP_EOL;

$client = new JsonRpcClient($server->getServerRoot());

$response1 = $client->rawSyncRequest('POST', $rippledRes1->getRef(), $body1);
$content = $response1->getBody()->getContents();
print_r($content . PHP_EOL);

$response1 = $client->rawSyncRequest('POST', $rippledRes2->getRef(), $body2);
$content = $response1->getBody()->getContents();
print_r($content . PHP_EOL);

$server->stop();
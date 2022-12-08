<?php

/**
 * Example handling an error response in raw mode
 */

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\ErrorResponse;

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$faultyBody =  json_encode([
    //"method" => "server_info", //We provoke an error by leaving out the server method, so rippled does not know what to do
    "params" => [
        ["api_version" => 1]
    ]
]);

try {
    $response = $client->rawSyncRequest('POST', '', $faultyBody);
} catch (Exception $e) {
    $response = $e->getResponse();
    $statusCode = $response->getStatusCode();
    $reason = $response->getReasonPhrase();
    $error = trim($response->getBody()->getContents());
    $xrplErrorResponse = new ErrorResponse(null, $statusCode, $error);

    print_r('--- Rippled ErrorResponse: ---');
    print_r('--- Status: ' . $xrplErrorResponse->getStatusCode() . ' Error: ' . $xrplErrorResponse->getError() . ' ---' . PHP_EOL);
}


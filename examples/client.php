<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Utility\PingRequest;

/**
 * This script can be used with the examples from
 * https://live-xrpl.pantheonsite.io/course/code-with-the-xrpl/lesson/create-accounts-and-send-xrp/
 *
 * Note that the TesNet gets reset regularly, so the given addresses may be
 * out of date by the time you are using this example. Just generate new ones
 * by using the above link
 *
 * Purpose: Show a basic interaction with the ledger with sync and async requests
 */

$testnetStandbyAccountSeed = 'sEd7r9n11TmibXPBNL3zEGE3aNcof9R';
$testnetStandbyAccountAddress = 'raKXrkYfbh4Uzqc481jTXbaKsWnW5XRMjp';

/*
 * example for using JSON-RPC via local rippled instance via docker:
 * docker run -d -p 5005:5005 -it natenichols/rippled-standalone:latest
 *
 * $client = new JsonRpcClient("http://host.docker.internal:5005");
 */
$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

// Perform a request using a request "method"
$pingRequest = new PingRequest();
$pingResponse = $client->syncRequest($pingRequest);
$result = $pingResponse->getResult();
print_r($result);

// Perform a "raw" request
$body = json_encode([
    "method" => "server_info",
    "params" => [
        ["api_version" => 1]
    ]
]);
$response = $client->rawSyncRequest('POST', '', $body);
$content = $response->getBody()->getContents();
print_r($content);
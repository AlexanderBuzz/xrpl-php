<?php

/**
 * Basic example using the client in RequestObject / ResponseObject  request mode
 */

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Methods\PingRequest;

/**
 * Purpose: Show the most basic interaction with the ledger using a method request
 */

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

/*
 * example for using JSON-RPC via local rippled instance via docker:
 * docker run -d -p 5005:5005 -it natenichols/rippled-standalone:latest
 *
 * $client = new JsonRpcClient("http://host.docker.internal:5005");
 */

$pingRequest = new PingRequest();

/* @var $pingResponse \XRPL_PHP\Models\Methods\PingResponse */
$pingResponse = $client->syncRequest($pingRequest);

$result = $pingResponse->getResult();

print_r($result);


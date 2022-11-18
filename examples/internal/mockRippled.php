<?php

require __DIR__ . '/../../vendor/autoload.php';

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountObjectsRequest;
use XRPL_PHP\Test\MockRippled\RippledResponse;

$server = new MockWebServer();
$server->start();

$json = '{"result":{"info":{"build_version":"1.9.4","complete_ledgers":"31815163-32549701","hostid":"HECK","initial_sync_duration_us":"190470657","io_latency_ms":1,"jq_trans_overflow":"0","last_close":{"converge_time_s":2,"proposers":6},"load_factor":1,"network_id":1,"peer_disconnects":"457","peer_disconnects_resources":"5","peers":109,"pubkey_node":"n9L2HuFXqzmRFctApnyTzcVukKSw3KfsvrrBUKQsk8Z3s3yRH4cj","server_state":"full","server_state_duration_us":"56017986726","state_accounting":{"connected":{"duration_us":"184371568","transitions":"2"},"disconnected":{"duration_us":"1088714","transitions":"2"},"full":{"duration_us":"56017986726","transitions":"1"},"syncing":{"duration_us":"5010345","transitions":"1"},"tracking":{"duration_us":"27","transitions":"1"}},"time":"2022-Nov-03 10:42:27.755289 UTC","uptime":56208,"validated_ledger":{"age":1,"base_fee_xrp":1e-05,"hash":"063623504CC87BA5DC782D92EEF2979C7A0C29513106B7D44F279DDD653A4C2A","reserve_base_xrp":10,"reserve_inc_xrp":2,"seq":32549701},"validation_quorum":5},"status":"success"}}';
$rippledRes = new RippledResponse('server_info', json_decode($json, true));
$server->setDefaultResponse($rippledRes);
echo $server->getUrlOfResponse($rippledRes);

$client = new JsonRpcClient($server->getServerRoot());

$body = json_encode([
    "method" => "server_info",
    "params" => [
        ["api_version" => 1]
    ]
]);

$response = $client->rawSyncRequest('POST', '', $body);

$content = $response->getBody()->getContents();

print_r($content);

$server->stop();
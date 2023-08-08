<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Integration;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Utility\PingRequest;

/**
 * Let's test against a real ledger, for now the Testnet. Inspiration from xrpl.py:
 *
 * Integration Tests
 * To run integration tests, you'll need a standalone rippled node running with WS port 6006
 * and JSON RPC port 5005. You can run a docker container for this:
 *
 * docker run -p 5005:5005 -p 6006:6006 -it natenichols/rippled-standalone:latest
 */
final class BasicIntegrationTest extends TestCase
{
    private const TESTNET_URL = "https://s.altnet.rippletest.net:51234";

    /** @psalm-suppress PropertyNotSetInConstructor */
    private JsonRpcClient $client;

    public function setUp(): void
    {
        $this->client = new JsonRpcClient(self::TESTNET_URL);
    }

    public function testPing(): void
    {
        $pingRequest = new PingRequest();

        $body = json_encode($pingRequest->getBody());

        $response = $this->client->rawSyncRequest('POST', '', $body);
        $content = (string) $response->getBody();

        $this->assertEquals(
            ["result" => ["status" => "success"]],
            json_decode($content, true)
        );
    }

    /*
    public function testTx(): void
    {
        //From: https://testnet.xrpl.org/transactions/06DF196953B57AD17A9DF16AE22D4C466F78AAC07369B5E0F64780CA903BAC8D
        $txRequest = new TxRequest(transaction: "06DF196953B57AD17A9DF16AE22D4C466F78AAC07369B5E0F64780CA903BAC8D");

        $body = json_encode($txRequest->getBody());

        $response = $this->client->rawSyncRequest('POST', '', $body);
        $content = (string) $response->getBody();
        $status = json_decode($content, true)['result']['status'];

        $this->assertEquals(
            "success",
            $status
        );
    }
    */
}
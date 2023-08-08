<?php

namespace XRPL_PHP\Test\Client;

use donatj\MockWebServer\MockWebServer;
use Exception;
use PHPUnit\Framework\TestCase;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Test\MockRippled\MockRippledResponse;

class JsonRpcClientTest extends TestCase
{
    private const PORT = 50267;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private MockWebServer $server;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private JsonRpcClient $client;

    public function setUp(): void
    {
        $this->server = new MockWebServer(self::PORT);
        $this->server->start();

        $this->client = new JsonRpcClient($this->server->getServerRoot());
    }

    public function testRawRequest(): void
    {
        $reqResPair = $this->getRequestResponsePair('serverInfo');
        $request = new MockRippledResponse($reqResPair['request'], $reqResPair['response']);

        $this->server->setResponseOfPath(
            $request->getRef(),
            $request
        );

        $response = $this->client->rawRequest('POST', $request->getRef(), $reqResPair['request'])->wait();
        $content = $response->getBody()->getContents();

        $this->assertEquals($reqResPair['response'], $content);
    }

    public function testRawSyncRequest(): void
    {
        $reqResPair = $this->getRequestResponsePair('serverInfo');
        $request = new MockRippledResponse($reqResPair['request'], $reqResPair['response']);

        $this->server->setResponseOfPath(
            $request->getRef(),
            $request
        );

        $response = $this->client->rawSyncRequest('POST', $request->getRef(), $reqResPair['request']);
        $content = $response->getBody()->getContents();

        $this->assertEquals($reqResPair['response'], $content);
    }

    public function tearDown(): void
    {
        $this->server->stop();
    }

    /**
     * @param string $method
     * @return array
     * @throws Exception
     */
    private function getRequestResponsePair(string $method): array
    {
        if (!ctype_alnum($method)) {
            throw new Exception('Method identifier can only contain alphanumercial characters.');
        }

        //TODO: Check if files exist, otherwise throw "fixture not found error"
        $requestJson = file_get_contents(__DIR__ . "/../MockRippled/Fixtures/Requests/{$method}.json");
        $responseJson = file_get_contents(__DIR__ . "/../MockRippled/Fixtures/Responses/{$method}.json");

        return [
            'method' => $method,
            'request' => $requestJson,
            'response' => $responseJson
        ];
    }
}
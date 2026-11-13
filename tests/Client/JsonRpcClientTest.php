<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Client;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use Exception;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Exception\GuzzleException;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;
use Hardcastle\XRPL_PHP\Models\ServerInfo\ServerInfoRequest;
use Hardcastle\XRPL_PHP\Test\MockRippled\MockRippledResponse;

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

    /**
     * A node that answers with an HTTP error status: Guzzle throws, and the
     * client turns the response it carries into an ErrorResponse. Guzzle 7
     * kept that response on every RequestException, Guzzle 8 only on a
     * ResponseException; both majors must give the same result.
     */
    public function testAnHttpErrorFromTheNodeBecomesAnErrorResponse(): void
    {
        $this->server->setDefaultResponse(new Response('node is overloaded', [], 503));

        $response = $this->client->syncRequest(new ServerInfoRequest());

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('node is overloaded', $response->getError());
    }

    /**
     * The connection closes before the node sends a byte. Guzzle 7 reports
     * that as a RequestException, Guzzle 8 as a NetworkException; with both,
     * syncRequest() throws instead of returning a made-up response.
     */
    public function testAConnectionClosedWithoutAnAnswerThrows(): void
    {
        $client = $this->clientOfBrokenNode(50268, 'drop');

        $this->expectException(GuzzleException::class);
        $client->syncRequest(new ServerInfoRequest());
    }

    /**
     * The node sends status 200 and part of the body, then stalls past the
     * timeout. Guzzle 8 hands the cut-off response along (ResponseTimeout-
     * Exception); it must not be read as a result.
     */
    public function testAResponseCutOffByTheTimeoutThrows(): void
    {
        $client = $this->clientOfBrokenNode(50269, 'cut');

        $this->expectException(GuzzleException::class);
        $client->syncRequest(new ServerInfoRequest());
    }

    /** @var resource|null */
    private $brokenNode = null;

    private function clientOfBrokenNode(int $port, string $mode): JsonRpcClient
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/Fixtures/broken-node.php', (string) $port, $mode],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );
        $this->assertIsResource($process, 'the broken node did not start');
        $this->brokenNode = $process;
        for ($i = 0; $i < 50; $i++) {
            $probe = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($probe !== false) {
                fclose($probe);
                break;
            }
            usleep(100_000);
        }

        return new JsonRpcClient("http://127.0.0.1:{$port}", timeout: 1.0);
    }

    public function tearDown(): void
    {
        if (is_resource($this->brokenNode)) {
            proc_terminate($this->brokenNode);
            proc_close($this->brokenNode);
        }
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
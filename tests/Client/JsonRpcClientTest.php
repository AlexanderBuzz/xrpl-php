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
     * that as a ConnectException (cURL error 52), Guzzle 8 as a
     * NetworkException; with both, syncRequest() throws instead of returning
     * a made-up response.
     */
    public function testAConnectionClosedWithoutAnAnswerThrows(): void
    {
        $client = $this->clientOfBrokenNode(50268, 'drop');

        $this->assertThrowsAfterReachingTheNode($client);
    }

    /**
     * The node sends status 200 and part of the body, then stalls past the
     * timeout. Guzzle 8 hands the cut-off response along (ResponseTimeout-
     * Exception); it must not be read as a result.
     */
    public function testAResponseCutOffByTheTimeoutThrows(): void
    {
        $client = $this->clientOfBrokenNode(50269, 'cut');

        $this->assertThrowsAfterReachingTheNode($client);
    }

    /**
     * A refused connection is a GuzzleException too, so expecting one alone
     * would also pass if the broken node never got the request. The exception
     * class cannot tell the two apart (Guzzle 7 reports a timeout and an empty
     * reply as a ConnectException), so the node reports each request it gets.
     */
    private function assertThrowsAfterReachingTheNode(JsonRpcClient $client): void
    {
        try {
            $client->syncRequest(new ServerInfoRequest());
            $this->fail('syncRequest() returned instead of throwing');
        } catch (GuzzleException) {
            $this->assertStringContainsString(
                'request',
                (string) file_get_contents($this->brokenNodeLog),
                'the request never reached the broken node'
            );
        }
    }

    /** @var resource|null */
    private $brokenNode = null;

    private string $brokenNodeLog = '';

    private function clientOfBrokenNode(int $port, string $mode): JsonRpcClient
    {
        $this->brokenNodeLog = (string) tempnam(sys_get_temp_dir(), 'broken-node');
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/Fixtures/broken-node.php', (string) $port, $mode],
            [1 => ['file', $this->brokenNodeLog, 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );
        $this->assertIsResource($process, 'the broken node did not start');
        $this->brokenNode = $process;
        $listening = false;
        for ($i = 0; $i < 50 && !$listening; $i++) {
            $probe = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($probe !== false) {
                fclose($probe);
                $listening = true;
            } else {
                usleep(100_000);
            }
        }
        $this->assertTrue($listening, "the broken node never listened on port {$port}");

        return new JsonRpcClient("http://127.0.0.1:{$port}", timeout: 1.0);
    }

    public function tearDown(): void
    {
        if (is_resource($this->brokenNode)) {
            proc_terminate($this->brokenNode);
            proc_close($this->brokenNode);
        }
        if ($this->brokenNodeLog !== '' && is_file($this->brokenNodeLog)) {
            unlink($this->brokenNodeLog);
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
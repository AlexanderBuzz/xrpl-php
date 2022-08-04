<?php declare(strict_types=1);

namespace XRPL_PHP\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

use Psr\Http\Message\ResponseInterface;

class JsonRpcClient
{
    private Client $restClient;

    private string $connectionUrl;

    private float $timeout = 3.0;

    public function __construct(string $connectionUrl)
    {
        $this->connectionUrl = $connectionUrl;

        $stack = HandlerStack::create(new CurlHandler());

        $this->restClient = new Client(
            [
                'base_uri' => $this->connectionUrl,
                'handler' => $stack,
                'timeout' => $this->timeout,
            ]
        );
    }

    public function request(string $method, string $resource = '', string $body = null): ResponseInterface
    {
        $request = new Request(
            $method,
            $resource,
            ['Content-Type' => 'application/json'],
            $body
        );

        //TODO: Handle exceptions properly, handle Promise properly;
        $response = $this->restClient->send($request);

        //$response = $this->restClient->sendAsync($request, ['handler' => null]);

        return $response;

    }

    public function autofill()
    {

    }

    public function connect()
    {

    }

    public function disconnect()
    {

    }

    public function fundWallet()
    {

    }

    public function getBalances()
    {

    }

    public function getLedgerIndex()
    {

    }

    public function getOrderbook()
    {

    }

    public function getXrpBalance()
    {

    }

    public function prepareTransaction()
    {

    }

    public function submit()
    {

    }

    public function submitAndWait()
    {

    }
}